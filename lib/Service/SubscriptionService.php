<?php

namespace OCA\DavPush\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\DavPush\Errors\SubscriptionNotFound;

use OCA\DavPush\Db\Subscription;
use OCA\DavPush\Db\SubscriptionMapper;

class SubscriptionService {
	public function __construct(
		private SubscriptionMapper $mapper
	) {
	}

	public function findAll(string $collectionName): array {
		return $this->mapper->findAll($collectionName);
	}

	public function findAllByUser(string $userId): array {
		return $this->mapper->findAllByUser($userId);
	}

	private function handleException(Exception $e): void {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new SubscriptionNotFound($e->getMessage());
		} else {
			throw $e;
		}
	}

	public function find(string $userId, int $id) {
		try {
			return $this->mapper->find($userId, $id);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	public function create(string $userId, string $collectionName, string $transport, int $expirationTimestamp, ?int $creationTimestamp = null) {
		$subscription = new Subscription();
		$subscription->setUserId($userId);
		$subscription->setCollectionName($collectionName);
		$subscription->setTransport($transport);
		$subscription->setCreationTimestamp($creationTimestamp ?? time());
		$subscription->setExpirationTimestamp($expirationTimestamp);
		$subscription = $this->mapper->insert($subscription);

		return $subscription;
	}

	public function update(string $userId, int $id, ?int $expirationTimestamp, mixed $data) {
		try {
			$subscription = $this->mapper->find($userId, $id);
			
			if (!is_null($expirationTimestamp)) {
				$subscription->setExpirationTimestamp($expirationTimestamp);
			}

			if (!is_null($data)) {
				$subscription->setData(json_encode($data));
			}

			return $this->mapper->update($subscription);
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}

	public function delete(string $userId, int $id) {
		try {
			$subscription = $this->mapper->find($userId, $id);
			$this->mapper->delete($subscription);
			return $subscription;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
}