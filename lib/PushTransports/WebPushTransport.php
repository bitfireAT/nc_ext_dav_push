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
use Sabre\Xml\Service;

class WebPushTransport extends Transport {
	protected $id = "web-push";

	public function registerSubscription($options) {
		$pushResource = False;

		foreach($options as $option) {
			if ($option["name"] == "{DAV:Push}push-resource") {
				$pushResource = $option["value"];
			}
		}

		if($pushResource) {
			return [
				'success' => True,
				'response' => "",
				'data' => [ "pushResource" => $pushResource ],
			];
		} else {
			return [
				'success' => False,
				'error' => "push resource not provided",
			];
		}
	}

	public function notify(string $userId, string $collectionName, $data) {
		$xmlService = new Service();

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
		$result = file_get_contents($data["pushResource"], false, $context);
	}
}
