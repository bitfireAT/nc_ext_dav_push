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

    public function findByPushResource(string $userId, string $collectionName, string $pushResource): ?WebPushSubscription {
        try {
			return $this->mapper->findByPushResource($userId, $collectionName, $pushResource);
		} catch (Exception $e) {
			$this->handleException($e);
		}
    }

	public function create(int $subscriptionId, string $pushResource): WebPushSubscription {
		$webPushSubscription = new WebPushSubscription();
		$webPushSubscription->setSubscriptionId($subscriptionId);
		$webPushSubscription->setPushResource($pushResource);
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