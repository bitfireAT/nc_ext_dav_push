<?php

declare(strict_types=1);

namespace OCA\DavPush\Migration;

use Closure;
use OCP\DB\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version001Date20240515221000 extends SimpleMigrationStep {
	public const SUBSCRIPTIONS_TABLE = "dav_push_subscriptions";

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable(self::SUBSCRIPTIONS_TABLE)) {
			$table = $schema->createTable(self::SUBSCRIPTIONS_TABLE);

			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 200,
			]);
			$table->addColumn('collection_name', Types::STRING, [
				'notnull' => true,
				'length' => 100,
			]);
			$table->addColumn('data', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('creation_timestamp', Types::BIGINT, [
				'notnull' => true,
			]);
			$table->addColumn('expiration_timestamp', Types::BIGINT, [
				'notnull' => true,
			]);

			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}
}