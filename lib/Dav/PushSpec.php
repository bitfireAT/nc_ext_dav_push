<?php

declare(strict_types=1);

/**
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

namespace OCA\DavPush\Dav;

abstract class PushSpec {

	public const PUSH_PREFIX = '{https://bitfire.at/webdav-push}';

	public const PUSH_MESSAGE = self::PUSH_PREFIX . 'push-message';

	public const PROPERTY_PUSH_RESOURCE = self::PUSH_PREFIX . 'push-resource';
	public const PROPERTY_TOPIC = self::PUSH_PREFIX . 'topic';
	public const PROPERTY_TRANSPORTS = self::PUSH_PREFIX . 'transports';

}
