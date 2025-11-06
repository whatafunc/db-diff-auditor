<?php

namespace DbDiffAuditor\Command;

use DbDiffAuditor\DbDiffAuditor;
use Symfony\Component\Console\Command\Command;
use Dotenv\Dotenv;

abstract class BaseCommand extends Command
{
    protected function getAuditor(): DbDiffAuditor
    {
        $dotenv = Dotenv::createImmutable(DB_DIFF_AUDITOR_PROJECT_ROOT);
        $dotenv->load();

        $config = [
            'connection' => [
                'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'database' => $_ENV['DB_NAME'] ?? 'mydatabase',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'port' => $_ENV['DB_PORT'] ?? 3306,
            ],
            'snapshot_path' => $_ENV['DB_SNAPSHOT_PATH'] ?? '.db-snapshots',
            'ignore_tables' => isset($_ENV['DB_IGNORE_TABLES']) ? explode(',', $_ENV['DB_IGNORE_TABLES']) : [],
        ];

        return new DbDiffAuditor($config);
    }
}