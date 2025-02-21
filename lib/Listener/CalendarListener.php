<?php

/**
 * @copyright bitfire web engineering GmbH <info@bitfire.at>
 *
 * @author bitfire web engineering GmbH <info@bitfire.at>
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

namespace OCA\DavPush\Listener;

use OCP\IConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectRestoredEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarObjectMovedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;

use Psr\Log\LoggerInterface;

use OCA\DavPush\Service\SubscriptionService;
use OCA\DavPush\Transport\TransportManager;
use OCA\DavPush\Helper\ErrorHandlingHelper;

class CalendarListener implements IEventListener {

	public function __construct(
		private LoggerInterface $logger,
		private SubscriptionService $subscriptionService,
		private TransportManager $transportManager,
		private ErrorHandlingHelper $errorHandlingHelper,
		private $userId,
	) {}

	public function handle(Event $event): void {
		if (($event instanceOf CalendarObjectCreatedEvent) || ($event instanceOf CalendarObjectDeletedEvent) ||
			($event instanceOf CalendarObjectUpdatedEvent) || ($event instanceOf CalendarObjectMovedToTrashEvent) ||
			($event instanceOf CalendarObjectRestoredEvent)) {
			$this->notifyAllSubscriptionsToResource("calendar", $event->getCalendarData()['id'], $event->getCalendarData()['{http://sabredav.org/ns}sync-token']);
		}
		
		if($event instanceOf CalendarObjectMovedEvent) {
			$this->notifyAllSubscriptionsToResource("calendar", $event->getSourceCalendarData()['id'], $event->getSourceCalendarData()['{http://sabredav.org/ns}sync-token']);
			$this->notifyAllSubscriptionsToResource("calendar", $event->getTargetCalendarData()['id'], $event->getTargetCalendarData()['{http://sabredav.org/ns}sync-token']);
		}
	}

	private function notifyAllSubscriptionsToResource(string $resourceType, int $resourceId, string $syncToken): void {
		$subscriptions = $this->subscriptionService->findAll($resourceType, $resourceId);

		$this->errorHandlingHelper->convertErrorsToExceptions(function () use ($subscriptions, $resourceType, $resourceId, $syncToken) {
			foreach($subscriptions as $subscription) {
				// TODO: The subscription was able to be registered and has not expired yet, that means the user had access to the resource as of recently,
				//        but we need to check if that is actually still the case here

				$transport = $this->transportManager->getTransport($subscription->getTransport());
	
				try {
					$transport->notify($subscription->getId(), $subscription->getUserId(), $resourceType, $resourceId, $syncToken);
					$this->subscriptionService->update($subscription->getUserId(), $subscription->getId(), failCounter: 0);
				} catch (\Throwable $e) {
					$this->logger->error("transport " .  $subscription->getTransport() . " failed to deliver notification to subscription " . $subscription->getId() . ". error message: " . $e->getMessage());
					$this->subscriptionService->update($subscription->getUserId(), $subscription->getId(), failCounter: $subscription->getFailCounter() + 1);
				}
			}
		});
	}
}
