<?php

declare(strict_types=1);

namespace OCA\DavPush\Migration;

/**
 * @copyright 2024 Jonathan Treffler <mail@jonathan-treffler.de>
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

class Version003Date20250302224500 extends SimpleMigrationStep {
    public const SUBSCRIPTIONS_TABLE = "dav_push_subscriptions";
	public const WEBPUSH_SUBSCRIPTIONS_TABLE = "dav_push_subscriptions_webpush";

	public function __construct(
		private IDBConnection $connection,
	) {}

    public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable(self::WEBPUSH_SUBSCRIPTIONS_TABLE);

        // true, except if this migration has already been run (which should never happen)
        if(!$table->hasColumn('client_public_key')) {
            // clear all subscriptions without message encryption (must have been created before alpha 3)

            $qb = $this->connection->getQueryBuilder();
            $qb->delete(self::SUBSCRIPTIONS_TABLE)
                ->where($qb->expr()->eq('transport', $qb->createNamedParameter("web-push")))
                ->executeStatement();
            
            $qb = $this->connection->getQueryBuilder();
            $qb->delete(self::WEBPUSH_SUBSCRIPTIONS_TABLE)
                ->executeStatement();
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

        $table = $schema->getTable(self::WEBPUSH_SUBSCRIPTIONS_TABLE);

        if(!$table->hasColumn('client_public_key_type')) {
            $table->addColumn('client_public_key_type', Types::STRING, [
                'notnull' => true,
                'length' => 20,
            ]);
        }

        if(!$table->hasColumn('client_public_key')) {
            $table->addColumn('client_public_key', Types::STRING, [
                'notnull' => true,
                'length' => 500, // way more than needed for p256dh, should be enough for any future types
            ]);
        }

        if(!$table->hasColumn('auth_secret')) {
            $table->addColumn('auth_secret', Types::STRING, [
                'notnull' => true,
                'length' => 100,
            ]);
        }

		return $schema;
	}
}