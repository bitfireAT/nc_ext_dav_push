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
use OCP\IRequest;
use OCP\IURLGenerator;

use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectRestoredEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarObjectMovedEvent;

use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardMovedEvent;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

use OCA\DavPush\Service\SubscriptionService;
use OCA\DavPush\Transport\TransportManager;
use OCA\DavPush\Helper\ErrorHandlingHelper;

class ResourceListener implements IEventListener {

	public function __construct(
		private LoggerInterface $logger,
		private SubscriptionService $subscriptionService,
		private TransportManager $transportManager,
		private ErrorHandlingHelper $errorHandlingHelper,
		private ContainerInterface $container,
		private IURLGenerator $URLGenerator,
		private $userId,
	) {}

	public function handle(Event $event): void {
		$dontNotifySubscriptions = [];

		try {
			$request = $this->container->get(IRequest::class);

			if (isset($request)) {
				$urlPrefix = $this->URLGenerator->getAbsoluteURL("/apps/dav_push/subscriptions/");

				$dontNotifyHeader = $request->getHeader("Push-Dont-Notify");

				$dontNotifyUrls = explode(",", $dontNotifyHeader);

				foreach($dontNotifyUrls as $dontNotifyUrl) {
					$dontNotifyUrlTrimmed = trim($dontNotifyUrl, " \"");

					if($dontNotifyUrlTrimmed === "*") {
						// no notifications need to be sent out
						$this->logger->info("Skipping all push subscriptions");
						return;
					} else if(str_starts_with($dontNotifyUrlTrimmed, $urlPrefix)) {
						$ignoreSubscriptionId = substr($dontNotifyUrlTrimmed, strlen($urlPrefix));

						if(ctype_digit($ignoreSubscriptionId)) {
							$dontNotifySubscriptions[] = (int) $ignoreSubscriptionId;
						}
					} else if ($dontNotifyUrlTrimmed !== "") {
						$this->logger->info("Invalid Push-Dont-Notify url " . json_encode($dontNotifyUrlTrimmed));
					}
				}
			}
		} catch (\Throwable $e) {
			$dontNotifySubscriptions = [];
		}		
		
		if (!empty($dontNotifySubscriptions)) {
			$this->logger->info("Skipping push subscriptions: " . join(", ", $dontNotifySubscriptions));
		}

		if (($event instanceOf CalendarObjectCreatedEvent) || ($event instanceOf CalendarObjectDeletedEvent) ||
			($event instanceOf CalendarObjectUpdatedEvent) || ($event instanceOf CalendarObjectMovedToTrashEvent) ||
			($event instanceOf CalendarObjectRestoredEvent)) {
			$this->notifyAllSubscriptionsToResource("calendar", $event->getCalendarId(), $event->getCalendarData()['{http://sabredav.org/ns}sync-token'], $dontNotifySubscriptions);
		}
		
		if($event instanceOf CalendarObjectMovedEvent) {
			$this->notifyAllSubscriptionsToResource("calendar", $event->getSourceCalendarId(), $event->getSourceCalendarData()['{http://sabredav.org/ns}sync-token'], $dontNotifySubscriptions);
			$this->notifyAllSubscriptionsToResource("calendar", $event->getTargetCalendarId(), $event->getTargetCalendarData()['{http://sabredav.org/ns}sync-token'], $dontNotifySubscriptions);
		}

		if (($event instanceOf CardCreatedEvent) || ($event instanceOf CardUpdatedEvent) || ($event instanceOf CardDeletedEvent)) {
			$this->notifyAllSubscriptionsToResource("addressbook", $event->getAddressBookId(), $event->getAddressBookData()['{http://sabredav.org/ns}sync-token'], $dontNotifySubscriptions);
		}

		if($event instanceOf CardMovedEvent) {
			$this->notifyAllSubscriptionsToResource("addressbook", $event->getSourceAddressBookId(), $event->getSourceAddressBookData()['{http://sabredav.org/ns}sync-token'], $dontNotifySubscriptions);
			$this->notifyAllSubscriptionsToResource("addressbook", $event->getTargetAddressBookId(), $event->getTargetAddressBookData()['{http://sabredav.org/ns}sync-token'], $dontNotifySubscriptions);
		}
	}

	private function notifyAllSubscriptionsToResource(string $resourceType, int $resourceId, string $syncToken, array $ignoreNotificationIds): void {
		$subscriptions = $this->subscriptionService->findAll($resourceType, $resourceId);

		$this->errorHandlingHelper->convertErrorsToExceptions(function () use ($subscriptions, $resourceType, $resourceId, $syncToken, $ignoreNotificationIds) {
			foreach($subscriptions as $subscription) {
				// Check whether this subscription should be ignored
				if (in_array($subscription->getId(), $ignoreNotificationIds)) {
					continue;
				}

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
