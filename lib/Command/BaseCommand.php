<?php

namespace OCA\DavPush\Command;

use OC\Core\Command\Base;
use OCP\IDateTimeFormatter;

use OCA\DavPush\Service\SubscriptionService;
use OCA\DavPush\Transport\TransportManager;
use OCA\DavPush\Interface\TableSerializable;

abstract class BaseCommand extends Base {

	public function __construct(
		private readonly IDateTimeFormatter $dateTimeFormatter,
		protected readonly SubscriptionService $subscriptionService,
		protected readonly TransportManager $transportManager,
	) {
		parent::__construct();
	}

	protected function formatTableSerializable(TableSerializable $serializable, ?array $params = null): array {
		return $serializable->tableSerialize($params);
	}

	protected function formatTableSerializables(array $serializables, ?array $params = null): array {
		$result = [];
		foreach($serializables as $serializable) {
			$result[] = $serializable->tableSerialize($params);
		}
		return $result;
	}
}