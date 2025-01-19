<?php

namespace OCA\DavPush\Command\Subscription;

use OCP\DB\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\DavPush\Command\BaseCommand;

class ListSubscriptions extends BaseCommand {
	protected function configure(): void {
		$this
			->setName('dav-push:subscriptions:list')
            ->addArgument('user-id', InputArgument::REQUIRED, 'filter by user id')
			->setDescription('List all push subscriptions of user');
            
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
            $userId = $input->getArgument('user-id');

			$subscriptions = $this->subscriptionService->findAllByUser($userId);

			$this->writeTableInOutputFormat($input, $output, $this->formatTableSerializables($subscriptions));
			return 0;
		} catch (Exception $e) {
			$output->writeln("<error>Exception \"{$e->getMessage()}\" at {$e->getFile()} line {$e->getLine()}</error>");
			return 1;
		}
	}
}