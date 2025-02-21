<?php

// SPDX-FileCopyrightText: bitfire web engineering GmbH <info@bitfire.at>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\DavPush\AppInfo;

use OCA\DavPush\Listener\SabrePluginAddListener;
use OCA\DavPush\Listener\ResourceListener;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;

use OCA\DAV\Events\SabrePluginAddEvent;

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

class Application extends App implements IBootstrap {
	public const APP_ID = 'dav_push';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(SabrePluginAddEvent::class, SabrePluginAddListener::class);

		$context->registerEventListener(CalendarObjectCreatedEvent::class, ResourceListener::class);
		$context->registerEventListener(CalendarObjectMovedToTrashEvent::class, ResourceListener::class);
		$context->registerEventListener(CalendarObjectRestoredEvent::class, ResourceListener::class);
		$context->registerEventListener(CalendarObjectDeletedEvent::class, ResourceListener::class);
		$context->registerEventListener(CalendarObjectUpdatedEvent::class, ResourceListener::class);
		$context->registerEventListener(CalendarObjectMovedEvent::class, ResourceListener::class);

		$context->registerEventListener(CardCreatedEvent::class, ResourceListener::class);
		$context->registerEventListener(CardUpdatedEvent::class, ResourceListener::class);
		$context->registerEventListener(CardDeletedEvent::class, ResourceListener::class);
		$context->registerEventListener(CardMovedEvent::class, ResourceListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}