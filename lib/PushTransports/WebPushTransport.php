<?php

declare(strict_types=1);

/**
 * @copyright 2024 Jonathan Treffler <mail@jonathan-treffler.de>
 *
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
 * 
 * @author Lukas Reschke <lukas@owncloud.com>
 * 
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DavPush\PushTransports;

use OCA\DavPush\AppInfo\Application;
use OCA\DavPush\Dav\PushSpec;
use OCA\DavPush\Transport\Transport;
use OCA\DavPush\Service\WebPushSubscriptionService;
use OCA\DavPush\Errors\WebPushSubscriptionNotFound;
use OCA\DavPush\Vendor\Minishlink\WebPush\WebPush;
use OCA\DavPush\Vendor\Minishlink\WebPush\VAPID;
use OCA\DavPush\Vendor\Minishlink\WebPush\Subscription;
use OCA\DavPush\Vendor\GuzzleHttp\RequestOptions;
use OCA\DavPush\Vendor\GuzzleHttp\HandlerStack;
use OCA\DavPush\Http\Client\DnsPinMiddleware;

use OCP\IConfig;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\ICertificateManager;


use Sabre\Xml\Service;
use Psr\Log\LoggerInterface;

class WebPushTransport extends Transport {
	private const VALID_CLIENT_PUBLIC_KEY_TYPES = ["p256dh"];

	protected $id = "web-push";

	private string $vapidPublicKey;
	private string $vapidPrivateKey;
	private WebPush $webPush;

	public function __construct(
		private readonly WebPushSubscriptionService $webPushSubscriptionService,
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly IURLGenerator $URLGenerator,
		private readonly ICertificateManager $certificateManager,
		private readonly DnsPinMiddleware $dnsPinMiddleware,
		private readonly LoggerInterface $logger,
	) {}

	public function getAdditionalInformation() {
		return [
			PushSpec::PROPERTY_VAPID_PUBLIC_KEY => $this->getVapidPublicKey(),
		];
	}

	/* Get VAPID public key (or generate it if it does not exist yet) */
	private function getVapidPublicKey() {
		if(!isset($this->vapidPublicKey)) {
			$this->vapidPublicKey = $this->appConfig->getValueString(
				Application::APP_ID,
				'web_push_vapid_public_key',
				lazy: true,
			);
		}

		if($this->vapidPublicKey === '') {
			$this->generateVapidKeys();
		}

		return $this->vapidPublicKey;
	}

	/* Get VAPID private key (or generate it if it does not exist yet) */
	private function getVapidPrivateKey() {
		if(!isset($this->vapidPrivateKey)) {
			$this->vapidPrivateKey = $this->appConfig->getValueString(
				Application::APP_ID,
				'web_push_vapid_private_key',
				lazy: true,
			);
		}

		if($this->vapidPrivateKey === '') {
			$this->generateVapidKeys();
		}

		return $this->vapidPrivateKey;
	}

	/**
	 * Generate new VAPID keys
	 * Overwrites previous keys!
	 * Only call if keys have not been generated yet
	 */
	private function generateVapidKeys(): void {
		$this->logger->info("No VAPID keys for this nextcloud instance found, generating them now. This should only happen once in the lifetime of the instance!");

		[
			"publicKey" => $publicKey,
			"privateKey" => $privateKey,
		] = VAPID::createVapidKeys();

		$this->appConfig->setValueString(
			Application::APP_ID,
			'web_push_vapid_public_key',
			$publicKey,
			lazy: true,
			sensitive: true
		);

		$this->appConfig->setValueString(
			Application::APP_ID,
			'web_push_vapid_private_key',
			$privateKey,
			lazy: true,
			sensitive: true
		);

		$this->vapidPublicKey = $publicKey;
		$this->vapidPrivateKey = $privateKey;
	}

	private function parseOptions(array $options): array {
		$result = [];

		foreach($options as $option) {
			if ($option["name"] == PushSpec::PROPERTY_PUSH_RESOURCE) {
				$result["pushResource"] = $option["value"];
			} else if ($option["name"] == PushSpec::PROPERTY_CLIENT_PUBLIC_KEY) {
				$result["clientPublicKeyType"] = $option["attributes"]["type"];
				$result["clientPublicKey"] = $option["value"];
			} else if ($option["name"] == PushSpec::PROPERTY_AUTH_SECRET) {
				$result["authSecret"] = $option["value"];
			}
		}

		return $result;
	}

	public function validateOptions($options): array {
		[
			"pushResource" => $pushResource,
			"clientPublicKeyType" => $clientPublicKeyType,
			"clientPublicKey" => $clientPublicKey,
			"authSecret" => $authSecret,
		] = $this->parseOptions($options);

		$valid = True;

		$errors = [];

		if(!(isset($pushResource) && $this->validPushResource($pushResource))) {
			$valid = False;
			$errors[] = ["push resource not provided"];
		}

		if(!(isset($clientPublicKeyType) && $this->validClientPublicKeyType($clientPublicKeyType))) {
			$valid = False;
			$errors[] = ["invalid client public key type"];
		}

		if(!isset($clientPublicKey) || $clientPublicKey == "") {
			$valid = False;
			$errors[] = ["invalid client public key"];
		}

		if(!isset($authSecret) || $authSecret == "") {
			$valid = False;
			$errors[] = ["invalid auth secret"];
		}

		return [
			"valid" => $valid,
			"errors" => $errors,
		];
	}

	private function validPushResource(string $url): bool {
		return (str_starts_with($url, 'https://') && filter_var($url, FILTER_VALIDATE_URL) !== false);
	}

	private function validClientPublicKeyType(string $type) {
		return in_array($type, self::VALID_CLIENT_PUBLIC_KEY_TYPES);
	}

	public function registerSubscription($subsciptionId, $options) {
		[
			"pushResource" => $pushResource,
			"clientPublicKeyType" => $clientPublicKeyType,
			"clientPublicKey" => $clientPublicKey,
			"authSecret" => $authSecret,
		] = $this->parseOptions($options);

		$this->webPushSubscriptionService->create(
			$subsciptionId,
			$pushResource,
			$clientPublicKeyType,
			$clientPublicKey,
			$authSecret,
		);

		return [
			'success' => True,
			'errors' => [],
			'responseStatus' => null, // use default
			'response' => "",
			'unsubscribeLink' => null, // use default
		];
	}

	/**
	* Encodes data with base64url
	* @param string $string The data to encode.
	* @return string The encoded data, as a string.
	*/
	private function base64url_encode($string) {
		$base64 = base64_encode($string);
		return rtrim(strtr($base64, '+/', '-_'), '=');
   	}

	private function getGuzzleProxyConfiguration() {
		$proxyHost = $this->config->getSystemValueString('proxy', '');

		if ($proxyHost === '') {
			return null;
		}

		$proxyUserPwd = $this->config->getSystemValueString('proxyuserpwd', '');
		if ($proxyUserPwd !== '') {
			$proxyHost = $proxyUserPwd . '@' . $proxyHost;
		}

		$proxy = [
			'http' => $proxyHost,
			'https' => $proxyHost,
		];

		$proxyExclude = $this->config->getSystemValue('proxyexclude', []);
		if ($proxyExclude !== [] && $proxyExclude !== null) {
			$proxy['no'] = $proxyExclude;
		}

		return $proxy;
	}

	public function notify(int $subscriptionId, string $userId, string $resourceType, int $resourceId, ?string $syncToken) {
		$xmlService = new Service();

		$webPushSubscription = $this->webPushSubscriptionService->findBySubscriptionId($subscriptionId);

		$topic = $resourceType . "-" . $resourceId;

		$contentUpdate = [];
		if (isset($syncToken)) {
			$contentUpdate["{DAV:}sync-token"] = $syncToken;
		}

		$content = $xmlService->write(PushSpec::PUSH_MESSAGE, [
			PushSpec::PROPERTY_TOPIC => $topic,
			PushSpec::PUSH_CONTENT_UPDATE => $contentUpdate
		]);

		if(!isset($this->webPush)) {
			$guzzleHandler = HandlerStack::create();
			
			if ($this->config->getSystemValueBool('dns_pinning', true)) {
				$guzzleHandler->push($this->dnsPinMiddleware->addDnsPinning());
			}

			$allowLocalAddress = $this->config->getSystemValueBool('allow_local_remote_servers', false);

			$httpClientOptions = [
				RequestOptions::ALLOW_REDIRECTS => false,
				RequestOptions::VERIFY => $this->certificateManager->getAbsoluteBundlePath(),
				"handler" => $guzzleHandler,
				"nextcloud" => [
					"allow_local_address" => $allowLocalAddress,
				],
			];

			$proxyConfiguration = $this->getGuzzleProxyConfiguration();

			if(isset($proxyConfiguration)) {
				$httpClientOptions[RequestOptions::PROXY] = $proxyConfiguration;
			}

			$this->webPush = new WebPush(
				auth: [
					'VAPID' => [
						'subject' => $this->URLGenerator->getBaseUrl(),
						'publicKey' => $this->getVapidPublicKey(),
						'privateKey' => $this->getVapidPrivateKey(),
					],
				],
				timeout: 10,
				clientOptions: $httpClientOptions,
			);
		}

		$report = $this->webPush->sendOneNotification(
			Subscription::create([
            	"endpoint" => $webPushSubscription->getPushResource(),
				"keys" => [
					"p256dh" => $webPushSubscription->getClientPublicKey(),
					"auth" => $webPushSubscription->getAuthSecret(),
				],
				"contentEncoding" => "aes128gcm", // TODO: should not be hardcoded, this is another property the DAV Client should send on registration
			]),
			$content,
			[
				"topic" => $this->base64url_encode(sha1($topic, true)),
			],
		);

		$this->logger->debug(json_encode([
			"isSuccess" => $report->isSuccess(),
			"request" => $report->getRequest()->getRequestTarget(),
			"response" => $report->getResponse()?->getStatusCode(),
		]));

		if (!$report->isSuccess()) {
			throw new \RuntimeException($report->getReason());
		}
	}

	public function getSubscriptionIdFromOptions(string $userId, string $resourceType, int $resourceId, $options): ?int {
		['pushResource' => $pushResource] = $this->parseOptions($options);

		try {
			return $this->webPushSubscriptionService->findByPushResource($userId, $resourceType, $resourceId, $pushResource)->getSubscriptionId();
		} catch (WebPushSubscriptionNotFound $e) {
			return null;
		}
	}

	public function updateSubscription($subsciptionId, $options) {
		// there are no options which can be edited -> NOOP
		return [
			'success' => True,
			'errors' => [],
			'response' => "",
		];
	}

	public function deleteSubscription($subsciptionId) {
		$this->webPushSubscriptionService->deleteBySubscriptionId($subsciptionId);
	}
}
