<?php

namespace OCA\DavPush\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class WebPushSubscriptionMapper extends QBMapper {	
	public const TABLENAME = 'dav_push_subscriptions_webpush';

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
	public function findByPushResource(string $pushResource): WebPushSubscription {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLENAME)
			->where($qb->expr()->eq('push_resource', $qb->createNamedParameter($pushResource)));
		
		return $this->findEntity($qb);
	}
}