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

class WebhookTransport extends Transport {
	protected $id = "webhook";

	public function registerSubscription($options) {
		$url = False;

		foreach($options as $option) {
			if($option["name"] == "{DAV:Push}endpoint") {
				$url = $option["value"];
			}
		}

		if($url) {
			return [
				'success' => True,
				'response' => "",
				'data' => [ "url" => $url ],
			];
		} else {
			return [
				'success' => False,
				'error' => "webhook url not provided",
			];
		}
	}

	public function notify(string $userId, string $collectionName, $data) {
		$options = [
			'http' => [
				'method' => 'POST',
				'content' => "Collection " . $collectionName . "has been changed",
			],
		];

		$context = stream_context_create($options);
		$result = file_get_contents($data["url"], false, $context);
	}
}