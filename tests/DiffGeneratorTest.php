<?php

namespace DbDiffAuditor\Tests;

use DbDiffAuditor\DiffGenerator;

class DiffGeneratorTest extends TestCase
{
    public function testCompareDetectsAddedColumn()
    {
        $oldSchema = $this->getOldSchema();
        $newSchema = $this->getNewSchemaWithAddedColumn();

        $differ = new DiffGenerator('mysql');
        $changes = $differ->compare($oldSchema, $newSchema);

        $this->assertCount(1, $changes);
        $this->assertEquals('add_column', $changes[0]['type']);
        $this->assertEquals('users', $changes[0]['table']);
        $this->assertEquals('email', $changes[0]['column']);
    }

    public function testCompareDetectsAddedIndex()
    {
        $oldSchema = $this->getOldSchema();
        $newSchema = $this->getNewSchemaWithAddedIndex();

        $differ = new DiffGenerator('mysql');
        $changes = $differ->compare($oldSchema, $newSchema);

        $this->assertCount(1, $changes);
        $this->assertEquals('add_index', $changes[0]['type']);
        $this->assertEquals('users', $changes[0]['table']);
        $this->assertEquals('id_index', $changes[0]['index']);
    }
}