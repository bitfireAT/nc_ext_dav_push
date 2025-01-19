<?php

namespace OCA\DavPush\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

use OCA\DavPush\Interface\TableSerializable;

class Subscription extends Entity implements JsonSerializable, TableSerializable {
	protected $userId;
	protected $collectionName;
	protected $transport;
	protected $creationTimestamp;
	protected $expirationTimestamp;
	protected $failCounter;

	public function __construct() {
		$this->addType('creationTimestamp','integer');
		$this->addType('expirationTimestamp','integer');
		$this->addType('failCounter','integer');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'userId' => $this->userId,
			'collectionName' => $this->collectionName,
			'transport' => $this->transport,
			'creationTimestamp' => $this->creationTimestamp,
			'expirationTimestamp' => $this->expirationTimestamp,
			'failCounter' => $this->failCounter,
		];
	}

	public function tableSerialize(?array $params = null): array {
		return [
			'Id' => $this->id,
			'User Id' => $this->userId,
			'DAV Collection Name' => $this->collectionName,
			'Transport' => $this->transport,
			'Creation Timestamp' => $this->creationTimestamp,
			'Expiration Timestamp' => $this->expirationTimestamp,
			'Fail Counter' => $this->failCounter,
		];
	}
}