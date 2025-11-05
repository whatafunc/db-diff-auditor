<?php

namespace DbDiffAuditor\Command;

use DbDiffAuditor\DbDiffAuditor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends BaseCommand
{
    protected static $defaultName = 'db:check';

    protected function configure()
    {
        $this->setDescription('Compares the database with the latest snapshot and generates changes.sql.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('🔍 Checking for database changes...');

        try {
            $auditor = $this->getAuditor();
            $changes = $auditor->getChanges();

            if (empty($changes)) {
                $output->writeln('✓ No changes detected.');
            } else {
                $output->writeln('⚠️  Changes detected:');
                foreach ($changes as $change) {
                    $output->writeln("  [{$change['type']}] {$change['table']}");
                    $output->writeln("  {$change['sql']}");
                }

                $sql = $auditor->exportChanges($changes);
                file_put_contents('changes.sql', $sql);
                $output->writeln('✓ Exported SQL changes to changes.sql');
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("✗ Error checking changes: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
