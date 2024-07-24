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
			if ($option["name"] == "{DAV:Push}push-resource") {
				$result["pushResource"] = $option["value"];
			}
		}

		return $result;
	}

	public function validateOptions($options): array {
		['pushResource' => $pushResource] = $this->parseOptions($options);

		// TODO: check if string is valid URL

		if(isset($pushResource) && $pushResource !== '') {
			return [
				'success' => True,
			];
		} else {
			return [
				'success' => False,
				'errors' => ["push resource not provided"]
			];
		}
	}

	public function registerSubscription($subsciptionId, $options) {
		['pushResource' => $pushResource] = $this->parseOptions($options);

		$this->webPushSubscriptionService->create($subsciptionId, $pushResource);

		return [
			'success' => True,
			'response' => "",
		];
	}

	public function notify(string $userId, string $collectionName, int $subscriptionId) {
		$xmlService = new Service();

		$pushResource = $this->webPushSubscriptionService->findBySubscriptionId($subscriptionId)->getPushResource();

		$content = $xmlService->write('{DAV:Push}push-message', [
			'{DAV:Push}topic' => $collectionName,
		]);

		$options = [
			'http' => [
				'method' => 'POST',
				'content' => $content,
			],
		];

		$context = stream_context_create($options);
		$result = file_get_contents($pushResource, false, $context);
	}

	public function getSubscriptionIdFromOptions($options): ?int {
		['pushResource' => $pushResource] = $this->parseOptions($options);

		try {
			return $this->webPushSubscriptionService->findByPushResource($pushResource)->getSubscriptionId();
		} catch (WebPushSubscriptionNotFound $e) {
			return null;
		}
	}

	public function updateSubscription($subsciptionId, $options) {
		// there are no options which can be edited -> NOOP
		return [
			'success' => True,
			'response' => "",
		];
	}
}
