<?php

declare(strict_types=1);

namespace OCA\DavPush\Transport;

use OCP\EventDispatcher\IEventDispatcher;

use OCA\DavPush\Event\RegisterTransportsEvent;
use OCA\DavPush\PushTransports\WebPushTransport;

class TransportManager {
	/**
	 * @var Transport[]
	 */
	private array $transports = [];

	public function __construct(IEventDispatcher $dispatcher) {
		// register integrated transports
		$this->registerTransport(new WebPushTransport());

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

	public function registerTransport(Transport $transport): self {
		$this->transports[] = $transport;
		return $this;
	}
}