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

namespace OCA\DavPush\Transport;

use OCP\EventDispatcher\IEventDispatcher;

use OCA\DavPush\Events\RegisterTransportsEvent;
use OCA\DavPush\PushTransports\WebPushTransport;
use OCA\DavPush\PushTransports\WebhookTransport;

class TransportManager {
	private array $transports = [];

	public function __construct(IEventDispatcher $dispatcher) {
		// register integrated transports
		$this->registerTransport(new WebPushTransport());
		$this->registerTransport(new WebhookTransport());

		// register transports provided by other apps
		$event = new RegisterTransportsEvent($this);
		$dispatcher->dispatchTyped($event);
	}

	/**
	 * @return Transport[]
	 */
	public function getTransports(): array {
		return $this->transports;
	}

	/**
	 * @return Transport
	 */
	public function getTransport($id): ?Transport {
		return $this->transports[$id];
	}

	public function registerTransport(Transport $transport): self {
		$this->transports[$transport->getId()] = $transport;
		return $this;
	}
}