<?php

namespace OCA\DavPush\Command\Transport;

use OCP\DB\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\DavPush\Command\BaseCommand;

class ListTransports extends BaseCommand {
	protected function configure(): void {
		$this
			->setName('dav-push:transports:list')
			->setDescription('List all available dav push transports');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$transports = $this->transportManager->getTransports();

            $result = [];

            foreach($transports as $id => $transport) {
                $result[] = [
                    "Id" => $id,
                    "Class" => $transport::class,
                ];
            }

			$this->writeTableInOutputFormat($input, $output, $result);
			return 0;
		} catch (Exception $e) {
			$output->writeln("<error>Exception \"{$e->getMessage()}\" at {$e->getFile()} line {$e->getLine()}</error>");
			return 1;
		}
	}
}