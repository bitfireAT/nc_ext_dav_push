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
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version001Date20240710115500 extends SimpleMigrationStep {
	public const WEBPUSH_SUBSCRIPTIONS_TABLE = "dav_push_subscriptions_webpush";

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable(self::WEBPUSH_SUBSCRIPTIONS_TABLE)) {
			$table = $schema->createTable(self::WEBPUSH_SUBSCRIPTIONS_TABLE);

			$table->addColumn('subscription_id', Types::INTEGER, [
				'notnull' => true,
			]);
			$table->addColumn('push_resource', Types::STRING, [
				'notnull' => true,
				'length' => 3000,
			]);

			$table->setPrimaryKey(['subscription_id']);
		}

		return $schema;
	}
}