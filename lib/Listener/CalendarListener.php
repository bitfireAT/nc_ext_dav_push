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
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Promise;

use OCA\DavPush\Service\SubscriptionService;
use OCA\DavPush\Transport\TransportManager;

class CalendarListener implements IEventListener {

    public function __construct(
		private LoggerInterface $logger,
		private SubscriptionService $subscriptionService,
		private TransportManager $transportManager,
		private $userId,
	) {}

    public function handle(Event $event): void {
        if (!($event instanceOf CalendarObjectCreatedEvent) && !($event instanceOf CalendarObjectDeletedEvent) &&
            !($event instanceOf CalendarObjectUpdatedEvent)) {
            return;
        }

		$collectionName = $event->getCalendarData()['uri'];
		$subscriptions = $this->subscriptionService->findAll($collectionName);

		$logger = $this->logger;

		$notificationPromises = (function () use ($collectionName, $subscriptions, $logger): \Generator {
			foreach($subscriptions as $subscription) {
				$transport = $this->transportManager->getTransport($subscription->getTransport());

				yield $transport->notify($subscription->getUserId(), $collectionName, $subscription->getId())
					->then(function($result) use ($subscription, $logger) {
						$logger->debug("transport " . $subscription->getTransport() . " successfully delivered notification for subscription " . $subscription->getId());
					}, function ($reason) use ($subscription, $logger) {
						$logger->error("transport " . $subscription->getTransport() . " failed to deliver notification to subscription " . $subscription->getId() . " with error message: " . $reason);
						return true;
					});
			}
		})();

		try {
			$responses = Promise\Utils::settle($notificationPromises)->wait(false);
		} catch (\Exception $e) {
			$logger->error("HOW DID WE GET HERE; THIS IS NOT HOW THIS IS SUPPOSED TO WORK " . $e->getMessage());
		}
    }
}
