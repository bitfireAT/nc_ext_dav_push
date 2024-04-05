<?php

// SPDX-FileCopyrightText: bitfire web engineering GmbH <info@bitfire.at>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\DavPush\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;

use OCA\DAV\Events\SabrePluginAddEvent;

use OCA\DavPush\Listener\SabrePluginAddListener;

class Application extends App implements IBootstrap {
	public const APP_ID = 'dav_push';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(SabrePluginAddEvent::class, SabrePluginAddListener::class);
    }

    public function boot(IBootContext $context): void {
    }
}