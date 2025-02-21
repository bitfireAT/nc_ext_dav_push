<?php

declare(strict_types=1);

/**
 * @copyright 2024 Jonathan Treffler <mail@jonathan-treffler.de>
 *
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
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

use OCA\DavPush\Dav\PushSpec;
use OCA\DavPush\Transport\Transport;
use OCA\DavPush\Service\WebPushSubscriptionService;
use OCA\DavPush\Errors\WebPushSubscriptionNotFound;

use Sabre\Xml\Service;

class WebPushTransport extends Transport {
	protected $id = "web-push";

	public function __construct(
		private WebPushSubscriptionService $webPushSubscriptionService,
	) {}

	private function parseOptions(array $options): array {
		$result = [];

		foreach($options as $option) {
			if ($option["name"] == PushSpec::PROPERTY_PUSH_RESOURCE) {
				$result["pushResource"] = $option["value"];
			}
		}

		return $result;
	}

	public function validateOptions($options): array {
		['pushResource' => $pushResource] = $this->parseOptions($options);

		if(isset($pushResource) && $this->validPushResource($pushResource)) {
			return [
				'valid' => True,
				'errors' => [],
			];
		} else {
			return [
				'valid' => False,
				'errors' => ["push resource not provided"]
			];
		}
	}

	private function validPushResource(string $url): bool {
		return (str_starts_with($url, 'https://') && filter_var($url, FILTER_VALIDATE_URL) !== false);
	}

	public function registerSubscription($subsciptionId, $options) {
		['pushResource' => $pushResource] = $this->parseOptions($options);

		$this->webPushSubscriptionService->create($subsciptionId, $pushResource);

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

	public function notify(int $subscriptionId, string $userId, string $resourceType, int $resourceId, ?string $syncToken) {
		$xmlService = new Service();

		$pushResource = $this->webPushSubscriptionService->findBySubscriptionId($subscriptionId)->getPushResource();

		$props = [];

		$topic = $resourceType . "-" . $resourceId;

		$props[PushSpec::PROPERTY_TOPIC] = $topic;

		if(isset($syncToken)) {
			$props["{DAV:}sync-token"] = $syncToken;
		}

		$content = $xmlService->write(PushSpec::PUSH_MESSAGE, [
			'{DAV:}propstat' => [
				'{DAV:}prop' => $props,
			],
		]);

		$options = [
			'http' => [
				'method' => 'POST',
				'content' => $content,
				'header' => [
					'Content-Type: application/xml; charset="UTF-8"',
					'Topic: ' . $this->base64url_encode(sha1($topic, true)),
				],
			],
		];

		$context = stream_context_create($options);
		$result = file_get_contents($pushResource, false, $context);
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
