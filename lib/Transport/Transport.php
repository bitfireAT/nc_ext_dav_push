<?php

declare(strict_types=1);

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

namespace OCA\DavPush\Transport;

abstract class Transport {
	protected $id;

	public function getId() {
		return $this->id;
	}

	public function getAdditionalInformation() {
		return [];
	}

	// Transport must return whether the provided options are valid.
	// This is called before registering a subscription and decides wether to proceed.
	/* Must return an array of the shape:
		[
			valid: bool,
			errors: ?array[string], // should be human readable
		]
	*/
	abstract public function validateOptions($options): array;

	// Transport must save any options (with association to subsciptionId) it needs later and do any init logic neccessary.
	/* Must return an array of the shape:
		[
			success: bool,
			errors: ?array[string], // should be human readable
			responseStatus: ?int, // http response code to the registration
			response: array, // will be converted to xml and used as the response to the registration
			unsubscribeLink: ?string, // overwrites unsubscribe link, transport needs to handle cleanup of entry in db itself
		]
	*/
	abstract public function registerSubscription($subsciptionId, $options);

	// Transport needs to be able to map subscription options back to a subscription id.
	// API Requests to create and update a subscription are the same, therefore if a subscription id is associated with the given options the subscription is updated, otherwise a new subscription is added.
	// Which option(s) uniquely identify a subscription is implementation specific.
	abstract public function getSubscriptionIdFromOptions($options): ?int;

	// Change mutable options of the subscription (if any exist)
	abstract public function updateSubscription($subsciptionId, $options);

	abstract public function notify(string $userId, string $collectionName, $data);
}