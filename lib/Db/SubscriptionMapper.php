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
	 * @param string $collectionName
	 * @return Subscription[]
	 */
	public function findAll(string $collectionName): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLENAME)
			->where($qb->expr()->eq('collection_name', $qb->createNamedParameter($collectionName)));
		
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
}