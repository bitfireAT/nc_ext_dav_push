<?php

declare(strict_types=1);

/**
 * @copyright 2024 Jonathan Treffler <mail@jonathan-treffler.de>
 *
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
 * @author Christopher Ng <chrng8@gmail.com>
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

use OCP\IUser;
use OCP\IUserSession;

use OCP\AppFramework\Db\DoesNotExistException;
use OCA\DAV\CalDAV\Calendar;

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class ServiceDetectionPlugin extends ServerPlugin {

	public function __construct(
		private IUserSession $userSession,
		private TransportManager $transportManager,
	) {
	}

	public function initialize(Server $server): void {
		$server->on('propFind', [$this, 'propFind']);
	}

	public function propFind(PropFind $propFind, INode $node) {
		if (count(array_intersect([PushSpec::PROPERTY_PUSH_TRANSPORTS, PushSpec::PROPERTY_PUSH_TOPIC], $propFind->getRequestedProperties())) == 0) {
			return;
		}

		if (!($node instanceof Calendar)) {
			return;
		}

		$propFind->handle(
			PushSpec::PROPERTY_PUSH_TRANSPORTS,
			function () use ($node) {
				//$user = $this->userSession->getUser();
				//if (!($user instanceof IUser)) {
				//	return [];
				//}

				$transports = $this->transportManager->getTransports();
				
				$result = [];
				
				foreach($transports as $transport) {
					$result[] = [
						(PushSpec::PROPERTY_PUSH_TRANSPORT) => [
							(PushSpec::PUSH_PREFIX . $transport->getId()) => $transport->getAdditionalInformation(),
						]
					];
				}

				//throw new \Exception( "\$result = " . json_encode($result) );

				return $result;
			},
		);

		$propFind->handle(
			PushSpec::PROPERTY_PUSH_TOPIC,
			//function () use ($node) {
				//$user = $this->userSession->getUser();
				//if (!($user instanceof IUser)) {
				//	return [];
				//}

			//	return "test-return-push";
			//},
			$node->getName()
		);
	}
}
