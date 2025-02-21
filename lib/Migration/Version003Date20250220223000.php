<?php

declare(strict_types=1);

namespace OCA\DavPush\Migration;

/**
 * @copyright 2025 Jonathan Treffler <mail@jonathan-treffler.de>
 *
 * @author Jonathan Treffler <mail@jonathan-treffler.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Closure;
use OCP\DB\Types;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

use Psr\Log\LoggerInterface;

class Version003Date20250220223000 extends SimpleMigrationStep {
	public const SUBSCRIPTIONS_TABLE = "dav_push_subscriptions";

    public function __construct(
		private IDBConnection $connection,
        private LoggerInterface $logger,
	) {}

    public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
        $table = $schema->getTable(self::SUBSCRIPTIONS_TABLE);

        // this migration was made specifically to allow a migration path from alpha version 0.0.2 to 0.0.3 (even though our alpha releases generally do not guarantee a migration path)
        // not needed for instances that started on a later version and therefore never had the collection_name column
        if($table->hasColumn("collection_name")) {
            $this->logger->debug("dav_push was initially installed on this instance before alpha version 0.0.3, all subscriptions will be cleared to provide a migration path to more modern versions");
            // clear all subscriptions created with the old way of referencing calendars
            $this->connection
                ->getQueryBuilder()
                ->delete(self::SUBSCRIPTIONS_TABLE)
                ->executeStatement();
        } else {
            $this->logger->debug("dav_push was initially installed on this instance after alpha version 0.0.3, migration Version003Date20250220223000 is not needed and wil NOOP");
        }
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
        $table = $schema->getTable(self::SUBSCRIPTIONS_TABLE);

        if($table->hasColumn("collection_name")) {
            // Either calendar or addressbook
            $table->addColumn('resource_type', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
            $table->addColumn('resource_id', Types::BIGINT, [
                'notnull' => true,
				'length' => 11,
				'unsigned' => true,
            ]);
            $table->dropColumn('collection_name');
        }

		return $schema;
	}
}