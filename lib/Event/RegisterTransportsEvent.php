<?php

declare(strict_types=1);

namespace OCA\DavPush\Event;

use OCP\EventDispatcher\Event;

use OCA\DavPush\Transport\TransportManager;

/**
 * This event is triggered during the initialization of DAV Push.
 * Use it to register external push transports.
 */
class RegisterTransportsEvent extends Event {

	/** @var TransportManager */
	private $transportManager;

	public function __construct(TransportManager $transportManager) {
		parent::__construct();
		$this->transportManager = $transportManager;
	}

	public function getTransportManager(): TransportManager {
		return $this->transportManager;
	}
}