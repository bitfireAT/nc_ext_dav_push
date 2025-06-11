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

	/**
	 * @param string resourceType
	 * @param int resourceId
	 * @return Subscription[]
	 */
	public function findAll(string $resourceType, int $resourceId): array {
		return $this->mapper->findAll($resourceType, $resourceId);
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

	public function create(string $userId, string $resourceType, int $resourceId, string $transport, int $expirationTimestamp, ?int $creationTimestamp = null) {
		$subscription = new Subscription();
		$subscription->setUserId($userId);
		$subscription->setResourceType($resourceType);
		$subscription->setResourceId($resourceId);
		$subscription->setTransport($transport);
		$subscription->setCreationTimestamp($creationTimestamp ?? time());
		$subscription->setExpirationTimestamp($expirationTimestamp);
		$subscription = $this->mapper->insert($subscription);

		return $subscription;
	}

	public function update(string $userId, int $id, ?int $expirationTimestamp = null, ?int $failCounter = null) {
		try {
			$subscription = $this->mapper->find($userId, $id);
			
			if (!is_null($expirationTimestamp)) {
				$subscription->setExpirationTimestamp($expirationTimestamp);
			}

			if (!is_null($failCounter)) {
				$subscription->setFailCounter($failCounter);
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

	/** remove all subscriptions, that are expired or have failed to deliver notifications too often */
	public function cleanupAll(): int {
		return $this->mapper->cleanupAll();
	}
}