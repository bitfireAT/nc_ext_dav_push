<?php

namespace OCA\DavPush\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class WebPushSubscriptionMapper extends QBMapper {	
	public const TABLENAME = 'dav_push_subscriptions_webpush';
	public const SUBSCRIPTIONS_TABLENAME = "dav_push_subscriptions";

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLENAME, WebPushSubscription::class);
	}

	/**
	 * @param int $subscriptionId
	 * @return Entity|WebPushSubscription
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function findBySubscriptionId(int $subscriptionId): WebPushSubscription {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLENAME)
			->where($qb->expr()->eq('subscription_id', $qb->createNamedParameter($subscriptionId, IQueryBuilder::PARAM_INT)));
		
		return $this->findEntity($qb);
	}

	/**
	 * @param string $pushResource
	 * @return Entity|WebPushSubscription
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function findByPushResource(string $userId, string $resourceType, int $resourceId, string $pushResource): WebPushSubscription {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('webpush.*')
			->from(self::TABLENAME, 'webpush');

		$qb->innerJoin('webpush', self::SUBSCRIPTIONS_TABLENAME, 'subscription', $qb->expr()->eq('webpush.subscription_id', 'subscription.id'));

		$qb->where($qb->expr()->eq('webpush.push_resource', $qb->createNamedParameter($pushResource)))
			->andWhere($qb->expr()->eq('subscription.user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('subscription.resource_type', $qb->createNamedParameter($resourceType)))
			->andWhere($qb->expr()->eq('subscription.resource_id', $qb->createNamedParameter($resourceId, IQueryBuilder::PARAM_INT)));
		
		return $this->findEntity($qb);
	}

	/**
	 * Deletes an entity from the table
	 *
	 * @param WebPushSubscription $entity the entity that should be deleted
	 * @psalm-param WebPushSubscription $entity the entity that should be deleted
	 * @return WebPushSubscription the deleted entity
	 * @psalm-return WebPushSubscription the deleted entity
	 * @throws \Exception
	 */
	public function delete(Entity $entity): Entity {
		$qb = $this->db->getQueryBuilder();

		$qb->delete(self::TABLENAME)
			->where(
				$qb->expr()->eq('subscription_id', $qb->createNamedParameter($entity->getSubscriptionId(), IQueryBuilder::PARAM_INT))
			);
		$qb->executeStatement();
		return $entity;
	}
}