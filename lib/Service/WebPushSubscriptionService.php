<?php

namespace OCA\DavPush\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\DavPush\Errors\WebPushSubscriptionNotFound;

use OCA\DavPush\Db\WebPushSubscription;
use OCA\DavPush\Db\WebPushSubscriptionMapper;

class WebPushSubscriptionService {
	public function __construct(
		private WebPushSubscriptionMapper $mapper
	) {
	}

	private function handleException(Exception $e): void {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new WebPushSubscriptionNotFound($e->getMessage());
		} else {
			throw $e;
		}
	}

    public function findBySubscriptionId(int $subscriptionId): ?WebPushSubscription {
        try {
			return $this->mapper->findBySubscriptionId($subscriptionId);
		} catch (Exception $e) {
			$this->handleException($e);
		}
    }

    public function findByPushResource(string $userId, string $resourceType, int $resourceId, string $pushResource): ?WebPushSubscription {
        try {
			return $this->mapper->findByPushResource($userId, $resourceType, $resourceId, $pushResource);
		} catch (Exception $e) {
			$this->handleException($e);
		}
    }

	/**
	 * Delete all webpush subscription table entries, that do not have a corresponding entry
	 * in the main subscriptions table
	 * (The only known origin for such entries is a bug in alpha versions of the app)
	 * @return int
	*/
	public function deleteOrphanedSubscriptions(): int {
		$entities = $this->mapper->findOrphanedSubscriptions();

		foreach($entities as $entity) {
			$this->mapper->delete($entity);
		}

		return count($entities);
	}

	public function create(int $subscriptionId, string $pushResource, string $clientPublicKeyType, string $clientPublicKey, string $authSecret): WebPushSubscription {
		$webPushSubscription = new WebPushSubscription();
		$webPushSubscription->setSubscriptionId($subscriptionId);
		$webPushSubscription->setPushResource($pushResource);
		$webPushSubscription->setClientPublicKeyType($clientPublicKeyType);
		$webPushSubscription->setClientPublicKey($clientPublicKey);
		$webPushSubscription->setAuthSecret($authSecret);

		$webPushSubscription = $this->mapper->insert($webPushSubscription);

		return $webPushSubscription;
	}

	public function deleteBySubscriptionId(int $subscriptionId): ?WebPushSubscription {
		try {
			$webPushSubscription = $this->mapper->findBySubscriptionId($subscriptionId);
			$this->mapper->delete($webPushSubscription);
			return $webPushSubscription;
		} catch (Exception $e) {
			$this->handleException($e);
		}
	}
}