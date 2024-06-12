<?php

// SPDX-FileCopyrightText: bitfire web engineering GmbH <info@bitfire.at>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\DavPush\AppInfo;

use OCA\DavPush\Listener\SabrePluginAddListener;
use OCA\DavPush\Listener\CalendarListener;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'dav_push';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(SabrePluginAddEvent::class, SabrePluginAddListener::class);
		$context->registerEventListener(CalendarObjectCreatedEvent::class, CalendarListener::class);
        $context->registerEventListener(CalendarObjectDeletedEvent::class, CalendarListener::class);
        $context->registerEventListener(CalendarObjectUpdatedEvent::class, CalendarListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}