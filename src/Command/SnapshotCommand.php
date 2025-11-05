<?php

namespace DbDiffAuditor\Command;

use DbDiffAuditor\DbDiffAuditor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SnapshotCommand extends BaseCommand
{
    protected static $defaultName = 'db:snapshot';

    protected function configure()
    {
        $this->setDescription('Creates a new snapshot of the database schema.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('📸 Creating new snapshot...');

        try {
            $auditor = $this->getAuditor();
            $filename = $auditor->createSnapshot("Manual snapshot");
            $output->writeln("✓ Created snapshot file: $filename");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("✗ Error creating snapshot: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
