<?php

namespace DbDiffAuditor\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getOldSchema(): array
    {
        return [
            'tables' => [
                'users' => [
                    'columns' => [
                        ['Field' => 'id', 'Type' => 'int', 'Null' => 'NO'],
                        ['Field' => 'name', 'Type' => 'varchar(255)', 'Null' => 'NO'],
                    ],
                    'indexes' => [],
                ],
            ],
        ];
    }

    protected function getNewSchemaWithAddedColumn(): array
    {
        $schema = $this->getOldSchema();
        $schema['tables']['users']['columns'][] = ['Field' => 'email', 'Type' => 'varchar(255)', 'Null' => 'NO'];
        return $schema;
    }

    protected function getNewSchemaWithAddedIndex(): array
    {
        $schema = $this->getOldSchema();
        $schema['tables']['users']['indexes'][] = [
            'name' => 'id_index',
            'columns' => ['id'],
            'unique' => false,
        ];
        return $schema;
    }
}
