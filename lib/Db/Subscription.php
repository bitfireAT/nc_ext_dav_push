<?php

namespace OCA\DavPush\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

use OCA\DavPush\Interface\TableSerializable;

class Subscription extends Entity implements JsonSerializable, TableSerializable {
	protected $userId;
	protected $resourceType;
	protected $resourceId;
	protected $transport;
	protected $creationTimestamp;
	protected $expirationTimestamp;
	protected $failCounter;

	public function __construct() {
		$this->addType('resourceId','integer');
		$this->addType('creationTimestamp','integer');
		$this->addType('expirationTimestamp','integer');
		$this->addType('failCounter','integer');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'userId' => $this->userId,
			'resourceType' => $this->resourceType,
			'resourceId' => $this->resourceId,
			'transport' => $this->transport,
			'creationTimestamp' => $this->creationTimestamp,
			'expirationTimestamp' => $this->expirationTimestamp,
			'failCounter' => $this->failCounter,
		];
	}

	public function tableSerialize(?array $params = null): array {
		return [
			'Id' => $this->id,
			'User ID' => $this->userId,
			'Resource Type' => $this->resourceType,
			'Resource ID' => $this->resourceId,
			'Transport' => $this->transport,
			'Creation Timestamp' => $this->creationTimestamp,
			'Expiration Timestamp' => $this->expirationTimestamp,
			'Fail Counter' => $this->failCounter,
		];
	}
}