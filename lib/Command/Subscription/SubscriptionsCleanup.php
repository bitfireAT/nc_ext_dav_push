<?php

namespace OCA\DavPush\Command\Subscription;

use OCP\DB\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\DavPush\Command\BaseCommand;

class SubscriptionsCleanup extends BaseCommand {
	protected function configure(): void {
		$this
			->setName('dav-push:subscriptions:cleanup')
			->setDescription('Delete all expired and failing subscriptions')
			->setHelp('This is also done automatically on a schedule, so normally you don\'t need to run this manually');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$result = $this->subscriptionService->cleanup();

			$output->writeln("done. deleted " . $result . " expired or failing subscriptions");

			return 0;
		} catch (Exception $e) {
			$output->writeln("<error>Exception \"{$e->getMessage()}\" at {$e->getFile()} line {$e->getLine()}</error>");
			return 1;
		}
	}
}