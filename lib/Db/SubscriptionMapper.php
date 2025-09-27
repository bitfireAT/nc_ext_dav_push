<?php

namespace OCA\DavPush\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class SubscriptionMapper extends QBMapper {	
	public const TABLENAME = 'dav_push_subscriptions';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLENAME, Subscription::class);
	}

	/**
	 * @param string $userId
	 * @param string $id
	 * @return Entity|Subscription
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function find(string $userId, int $id): Subscription {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLENAME)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		
		return $this->findEntity($qb);
	}

	/**
	 * @param string resourceType
	 * @param int resourceId
	 * @return Subscription[]
	 */
	public function findAll(string $resourceType, int $resourceId): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLENAME)
			->where($qb->expr()->eq('resource_type', $qb->createNamedParameter($resourceType)))
			->andWhere($qb->expr()->eq('resource_id', $qb->createNamedParameter($resourceId, IQueryBuilder::PARAM_INT)));
		
		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @return Subscription[]
	 */
	public function findAllByUser(string $userId): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLENAME)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		
		return $this->findEntities($qb);
	}

	/**
	 * @param int $maxFails maximum number of successive subscription notification delivery failures still considered acceptable
	 * @param ?int $limit
	 * @return Subscription[]
	 */
	public function findAllToCleanUp(int $maxFails = 5, ?int $limit = null): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLENAME)
			->where($qb->expr()->orX(
				$qb->expr()->gt('fail_counter', $qb->createNamedParameter($maxFails, IQueryBuilder::PARAM_INT)),
				$qb->expr()->lt('expiration_timestamp', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT)),
			));

		if(isset($limit)) {
			$qb->setMaxResults($limit);
		}
		
		return $this->findEntities($qb);
	}
}