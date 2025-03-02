<?php

namespace OCA\DavPush\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class WebPushSubscription extends Entity implements JsonSerializable {
	protected $subscriptionId;
	protected $pushResource;
	protected $clientPublicKeyType;
	protected $clientPublicKey;
	protected $authSecret;

	public function __construct() {
		$this->addType('subscriptionId','integer');
	}

	public function jsonSerialize(): array {
		return [
			'subscriptionId' => $this->subscriptionId,
			'pushResource' => $this->pushResource,
			'clientPublicKeyType' => $this->clientPublicKeyType,
			'clientPublicKey' => $this->clientPublicKey,
			'authSecret' => $this->authSecret,
		];
	}
}