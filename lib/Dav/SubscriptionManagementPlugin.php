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

use OCP\IUserSession;

use OCP\AppFramework\Http;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class SubscriptionManagementPlugin extends ServerPlugin {

	public const PUSH_PREFIX = '{DAV:Push}';
	public const PUSH_REGISTER = self::PUSH_PREFIX . "push-register";
	public const PUSH_SUBSCRIPTION = self::PUSH_PREFIX . "subscription";
	public const PUSH_EXPIRES = self::PUSH_PREFIX . "expires";

	/**
	 * Reference to SabreDAV server object.
	 *
	 * @var \Sabre\DAV\Server
	 */
	protected $server;

	public function __construct(
		private IUserSession $userSession,
	) {
	}

	public function initialize(Server $server): void {
		$this->server = $server;

		$this->server->on('method:POST', [$this, 'httpPost']);
	}

	public function httpPost(RequestInterface $request, ResponseInterface $response) {
		// Only handle xml
		$contentType = (string) $request->getHeader('Content-Type');
		if (!(str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml'))) {
			return;
		}

		$requestBody = $request->getBodyAsString();

		// If this request handler could not deal with this POST request, it
		// will return 'null' and other plugins get a chance to handle the
		// request.
		//
		// However, we already requested the full body. This is a problem,
		// because a body can only be read once. This is why we preemptively
		// re-populated the request body with the existing data.
		$request->setBody($requestBody);

		$parameters = $this->server->xml->parse($requestBody, $request->getUrl(), $documentType);

		if($documentType == self::PUSH_REGISTER) {
			$errors = [];

			$subscriptionParameterIncluded = False;

			$subscriptionType = "";
			$subscriptionOptions = [];
			$subscriptionExpires = False;

			foreach($parameters as $parameter) {
				if($parameter["name"] == self::PUSH_SUBSCRIPTION && !$subscriptionParameterIncluded) {
					$subscriptionParameterIncluded = True;
					
					if(sizeof($parameter["value"]) == 1) {
						$subscriptionType = $parameter["value"][0]["name"];
						$subscriptionOptions = $parameter["value"][0]["value"];
					} else {
						$errors[] = "only one subscription allowed";
					}
				} elif($parameter["name"] == self::PUSH_EXPIRES && !$subscriptionExpires) {
					$subscriptionExpires = $parameter["value"];
				}
			}

			if(!$subscriptionParameterIncluded) {
				$errors[] = "no subscription included";
			}

			if(sizeof($errors) == 0) {
				$response->setStatus(Http::STATUS_CREATED);

				//$response->setBody($subscriptionType);
				//$response->setBody(json_encode($parameters));

				// pass data to applicable transport class
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