<?php

namespace OCA\DavPush\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Subscription extends Entity implements JsonSerializable {
	protected $userId;
	protected $collectionName;
	protected $transport;
	protected $data;
	protected $creationTimestamp;
	protected $expirationTimestamp;

	public function __construct() {
		$this->addType('creationTimestamp','integer');
		$this->addType('expirationTimestamp','integer');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'userId' => $this->userId,
			'collectionName' => $this->collectionName,
			'transport' => $this->transport,
			'data' => $this->data,
			'creationTimestamp' => $this->creationTimestamp,
			'expirationTimestamp' => $this->expirationTimestamp
		];
	}
}