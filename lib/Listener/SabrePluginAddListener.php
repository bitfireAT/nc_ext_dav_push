<?php

declare(strict_types=1);

namespace OCA\DavPush\Listener;

use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\DavPush\Dav\ServiceDetectionPlugin;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

use Psr\Container\ContainerInterface;

class SabrePluginAddListener implements IEventListener {
	public function __construct(private ContainerInterface $container) {}

	public function handle(Event $event): void {
		if ($event instanceof SabrePluginAddEvent) {
            $serviceDetectionPlugin = $this->container->get(ServiceDetectionPlugin::class);

            $event->getServer()->addPlugin($serviceDetectionPlugin);
        }
	}
}