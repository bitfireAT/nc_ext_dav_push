<?php

declare(strict_types=1);

/**
 * @copyright 2024 Jonathan Treffler <mail@jonathan-treffler.de>
 *
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\DavPush\Dav;

use OCA\DavPush\Dav\PushSpec;
use OCA\DavPush\Transport\TransportManager;
use OCA\DavPush\Db\Subscription;
use OCA\DavPush\Service\SubscriptionService;

use OCP\IUserSession;
use OCP\IURLGenerator;
use OCP\IDBConnection;
use OCP\AppFramework\Http;
use OCP\AppFramework\Db\TTransactional;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class SubscriptionManagementPlugin extends ServerPlugin {
	use TTransactional;

	public const PUSH_PREFIX = '{https://bitfire.at/webdav-push}';
	public const PUSH_REGISTER = self::PUSH_PREFIX . "push-register";
	public const PUSH_SUBSCRIPTION = self::PUSH_PREFIX . "subscription";
	public const PUSH_EXPIRES = self::PUSH_PREFIX . "expires";

	public const IMF_FIXDATE_FORMAT = "D, d M Y H:i:s O+";

	/**
	 * Reference to SabreDAV server object.
	 *
	 * @var \Sabre\DAV\Server
	 */
	protected $server;

	public function __construct(
		private IUserSession $userSession,
		private TransportManager $transportManager,
		private IURLGenerator $URLGenerator,
		private SubscriptionService $subscriptionService,
		private IDBConnection $db,
	) {
	}

	public function initialize(Server $server): void {
		$this->server = $server;

		$this->server->on('method:POST', [$this, 'httpPost']);
	}

	private function parsePushRegisterElement(array $subElements) {
		$errors = [];

		$subscriptionElementIncluded = False;

		$parsedExpiresElement = null;

		foreach($subElements as $parameter) {
			if($parameter["name"] == self::PUSH_SUBSCRIPTION) {
				if(!$subscriptionElementIncluded) {
					$subscriptionElementIncluded = True;
				
					$parsedSubscriptionElement = $this->parseSubscriptionElement($parameter["value"]);
					$errors = array_merge($errors, $parsedSubscriptionElement["errors"]);
				} else {
					$errors[] = "more than one subscriptions at a time are not allowed";
				}
			} elseif($parameter["name"] == self::PUSH_EXPIRES && !isset($subscriptionExpires)) {
				$parsedExpiresElement = $this->parseExpiresElement($parameter["value"]);
			}
		}

		if(!$subscriptionElementIncluded) {
			$errors[] = "no subscription included";
		}

		if(sizeof($errors) === 0) {
			return [
				"errors" => [],
				"subscriptionType" => str_replace(PushSpec::PUSH_PREFIX, "", $parsedSubscriptionElement["type"]),
				"subscriptionOptions" => $parsedSubscriptionElement["options"],
				"requestedSubscriptionExpiration" => $parsedExpiresElement,
			];
		} else {
			return [
				"errors" => $errors,
				"subscriptionType" => null,
				"subscriptionOptions" => null,
				"requestedSubscriptionExpiration" => null,
			];
		}
	}

	private function parseSubscriptionElement(array $subElements): array {
		// ensure subscription element only has one child element
		if(sizeof($subElements) == 1) {
			// parse child element
			$type = $subElements[0]["name"];
			$type = str_replace('{'.PushSpec::PUSH_PREFIX.'}', '', $type);
			$type = preg_replace('/-subscription$/', '', $type);

			$options = $subElements[0]["value"];

			return [
				"errors" => [],
				"type" => $type,
				"options" => $options,
			];
		} else {
			return [
				"errors" => [
					"only one subscription allowed",
				],
				"type" => null,
				"options" => null,
			];
		}
	}

	private function parseExpiresElement(string $elementValue): int {
		return \DateTime::createFromFormat(self::IMF_FIXDATE_FORMAT, $elementValue)->getTimestamp();
	}

	private function getSubscriptionExpirationTimestamp(?int $preferredExpirationTimestamp): int {
		$inOneWeekTimestamp = strtotime("+1 week");

		if(isset($preferredExpirationTimestamp)) {
			if($preferredExpirationTimestamp < time()) {
				// expiration timestamp in the past was requested, returning default (one week)
				return $inOneWeekTimestamp;
			} else if($preferredExpirationTimestamp > $inOneWeekTimestamp) {
				// requested expiration is further in the future than one week, clamping to one week
				return $inOneWeekTimestamp;
			} else {
				return $preferredExpirationTimestamp;
			}
		} else {
			// expiration was not set, default to in one week
			return $inOneWeekTimestamp;
		}
	}

	public function httpPost(RequestInterface $request, ResponseInterface $response) {
		// Only handle xml
		$contentType = (string) $request->getHeader('Content-Type');
		if (!(str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml'))) {
			return;
		}

		$node = $this->server->tree->getNodeForPath($this->server->getRequestUri());

		$requestBody = $request->getBodyAsString();

		// If this request handler could not deal with this POST request, it
		// will return 'null' and other plugins get a chance to handle the
		// request.
		//
		// However, we already requested the full body. This is a problem,
		// because a body can only be read once. This is why we preemptively
		// re-populated the request body with the existing data.
		$request->setBody($requestBody);

		$requestBodyXML = $this->server->xml->parse($requestBody, $request->getUrl(), $documentType);

		// only react to post request if top level xml element is <push-register>
		if($documentType == self::PUSH_REGISTER) {
			$errors = [];

			[
				"errors" => $parseErrors,
				"subscriptionType" => $subscriptionType,
				"subscriptionOptions" => $subscriptionOptions,
				"requestedSubscriptionExpiration" => $requestedSubscriptionExpiration,
			] = $this->parsePushRegisterElement($requestBodyXML);
			

			if(sizeof($parseErrors) > 0) {
				$errors = array_merge($errors, $parseErrors);
			} else {
				$subscriptionExpires = $this->getSubscriptionExpirationTimestamp($requestedSubscriptionExpiration);
				
				$transport = $this->transportManager->getTransport($subscriptionType);

				if(is_null($transport)) {
					$errors[] = $subscriptionType . " transport does not exist";
				} else {
					[
						'valid' => $validateSuccess,
						'errors' => $validateErrors,
					] = $transport->validateOptions($subscriptionOptions);
	
					if(!$validateSuccess) {
						if(isset($validateErrors) && !empty($validateErrors)) {
							$errors = array_merge($errors, $validateErrors);
						} else {
							$errors[] = "options validation error";
						}
					} else {
						$user = $this->userSession->getUser();
	
						$existingSubscriptionId = $transport->getSubscriptionIdFromOptions($user->getUID(), $node->getName(), $subscriptionOptions);
	
						if(!is_int($existingSubscriptionId)) {
							try {
								// create new subscription entry in db and register with transport. If the transport register fails roll back db transaction
								$this->atomic(function () use ($user, $node, $subscriptionType, $subscriptionExpires, $subscriptionOptions, &$subscription, $transport, &$errors, &$responseStatus, &$responseContent, &$unsubscribeLink) {
									$subscription = $this->subscriptionService->create($user->getUID(), $node->getName(), $subscriptionType, $subscriptionExpires);

									[
										'success' => $registerSuccess,
										'errors' => $registerErrors,
										'responseStatus' => $responseStatus,
										'response' => $responseContent,
										'unsubscribeLink' => $unsubscribeLink,
									] = $transport->registerSubscription($subscription->getId(), $subscriptionOptions);
			
									$responseStatus = $responseStatus ?? Http::STATUS_CREATED;
			
									if(!$registerSuccess) {
										if(isset($registerErrors) && !empty($registerErrors)) {
											$errors = array_merge($errors, $registerErrors);
										} else {
											$errors[] = "registration error";
										}
									}
								}, $this->db);
							} catch (\Exception $e) {
								// return error after rollback
								$errors[] = "registration error";
							}
							
						} else {
							// implicitly checks if subscription found by transport is really owned by correct user
							$subscription = $this->subscriptionService->find($user->getUID(), $existingSubscriptionId);
							
							// check if subscription found by transport is really for correct collection
							if($subscription->getCollectionName() !== $node->getName()) {
								$errors[] = "subscription update error";
							} else {
								[
									'success' => $updateSuccess,
									'errors' => $updateErrors,
									'response' => $responseContent,
								] =	$transport->updateSubscription($subscription->getId(), $subscriptionOptions);
	
								if(!$updateSuccess) {
									if(isset($updateErrors) && !empty($updateErrors)) {
										$errors = array_merge($errors, $updateErrors);
									} else {
										$errors[] = "subscription update error";
									}
								} else {
									$subscription = $this->subscriptionService->update($user->getUID(), $subscription->getId(), $subscriptionExpires);
		
									$responseStatus = Http::STATUS_CREATED;
								}
							}
						}
					}
				}
			}

			if(sizeof($errors) == 0) {
				$response->setStatus($responseStatus);
				
				// generate default unsubscribe link, unless transport requested a custom url
				$unsubscribeLink = $unsubscribeLink ?? $this->URLGenerator->getAbsoluteURL("/apps/dav_push/subscriptions/" . $subscription->getId());
				$response->setHeader("Location", $unsubscribeLink);

				$response->setHeader("Expires", date(self::IMF_FIXDATE_FORMAT, $subscription->getExpirationTimestamp()));

				$xml = $this->server->xml->write(self::PUSH_REGISTER, $responseContent);
				$response->setBody($xml);
			} else {
				$response->setStatus(Http::STATUS_BAD_REQUEST);

				$errorsXML = [];

				foreach($errors as $error) {
					$errorsXML[] = [
						"name" => "error",
						"value" => $error
					];
				}

				$xml = $this->server->xml->write(
					'{DAV:}error',
					$errorsXML
				);

				$response->setBody($xml);
			}

			return false;
		}
	}
}
