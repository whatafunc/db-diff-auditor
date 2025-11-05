<?php

namespace DbDiffAuditor\Command;

use DbDiffAuditor\DiffGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompareLastCommand extends Command
{
    protected static $defaultName = 'db:compare-last';

    protected function configure()
    {
        $this->setDescription('Compares the last two snapshots.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('🧾 Comparing last two snapshots...');

        $snapshots = glob('.db-snapshots/*.json');
        usort($snapshots, function($a, $b) {
            return filemtime($a) <=> filemtime($b);
        });

        if (count($snapshots) < 2) {
            $output->writeln('✗ Need at least two snapshots to compare.');
            return Command::FAILURE;
        }

        $latest = array_pop($snapshots);
        $previous = array_pop($snapshots);

        try {
            $snap1 = json_decode(file_get_contents($previous), true);
            $snap2 = json_decode(file_get_contents($latest), true);

            if (!$snap1 || !$snap2) {
                throw new \Exception("Failed to read one of the snapshots: $previous or $latest");
            }

            $differ = new DiffGenerator($snap1['schema']['driver'] ?? 'mysql');
            $changes = $differ->compare($snap1['schema'], $snap2['schema']);

            if (empty($changes)) {
                $output->writeln('✓ No differences between the last two snapshots.');
            } else {
                $output->writeln('⚠️  Differences found between snapshots:');
                foreach ($changes as $change) {
                    $output->writeln("  [{$change['type']}] {$change['table']}");
                    $output->writeln("  {$change['sql']}");
                }
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("✗ Error comparing snapshots: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
