<?php

namespace DbDiffAuditor;

class DiffGenerator
{	
    // PHP8 style properties
    // private string $driver;

    // PHP7 style properties
    /** @var string */
    private $driver;

    public function __construct(string $driver = 'mysql')
    {
        $this->driver = $driver;
    }
    
    public function compare(array $oldSchema, array $newSchema): array
    {
        $changes = [];
        
        $oldTables = $oldSchema['tables'] ?? $oldSchema;
        $newTables = $newSchema['tables'] ?? $newSchema;
        
        // Check for new tables
        foreach ($newTables as $table => $structure) {
            if (!isset($oldTables[$table])) {
                $changes[] = [
                    'type' => 'create_table',
                    'table' => $table,
                    'sql' => $this->generateCreateTable($table, $structure),
                ];
            }
        }
        
        // Check for dropped tables
        foreach ($oldTables as $table => $structure) {
            if (!isset($newTables[$table])) {
                $changes[] = [
                    'type' => 'drop_table',
                    'table' => $table,
                    'sql' => $this->quoteIdentifier($table, 'DROP TABLE %s;'),
                ];
            }
        }
        
        // Check for modified tables
        foreach ($newTables as $table => $newStructure) {
            if (isset($oldTables[$table])) {
                $tableChanges = $this->compareTableStructure(
                    $table,
                    $oldTables[$table],
                    $newStructure
                );
                $changes = array_merge($changes, $tableChanges);
            }
        }
        
        return $changes;
    }
    
    private function compareTableStructure(string $table, array $old, array $new): array
    {
        $changes = [];
        
        // Compare columns
        $oldColumns = $this->indexByField($old['columns'], 'Field');
        $newColumns = $this->indexByField($new['columns'], 'Field');
        
        // New columns
        foreach ($newColumns as $colName => $colDef) {
            if (!isset($oldColumns[$colName])) {
                $changes[] = [
                    'type' => 'add_column',
                    'table' => $table,
                    'column' => $colName,
                    'sql' => $this->quoteIdentifier($table, 
                        "ALTER TABLE %s ADD COLUMN " . $this->formatColumn($colName, $colDef) . ";"
                    ),
                ];
            }
        }
        
        // Dropped columns
        foreach ($oldColumns as $colName => $colDef) {
            if (!isset($newColumns[$colName])) {
                $changes[] = [
                    'type' => 'drop_column',
                    'table' => $table,
                    'column' => $colName,
                    'sql' => $this->quoteIdentifier($table, 
                        "ALTER TABLE %s DROP COLUMN " . $this->quoteIdentifier($colName) . ";"
                    ),
                ];
            }
        }
        
        // Modified columns
        foreach ($newColumns as $colName => $newDef) {
            if (isset($oldColumns[$colName])) {
                $oldDef = $oldColumns[$colName];
                if ($this->columnChanged($oldDef, $newDef)) {
                    $action = $this->driver === 'mysql' ? 'MODIFY' : 'ALTER';
                    $changes[] = [
                        'type' => 'modify_column',
                        'table' => $table,
                        'column' => $colName,
                        'sql' => $this->quoteIdentifier($table,
                            "ALTER TABLE %s $action COLUMN " . $this->formatColumn($colName, $newDef) . ";"
                        ),
                    ];
                }
            }
        }
        
        // Compare indexes (simplified)
        $changes = array_merge($changes, $this->compareIndexes($table, $old, $new));
        
        return $changes;
    }
    
    private function compareIndexes(string $table, array $old, array $new): array
    {
        $changes = [];
        $oldIndexes = $this->indexByField($old['indexes'] ?? [], 'name');
        $newIndexes = $this->indexByField($new['indexes'] ?? [], 'name');
        
        // New indexes
        foreach ($newIndexes as $indexName => $indexDef) {
            if (!isset($oldIndexes[$indexName]) && $indexName !== 'PRIMARY') {
                $unique = $indexDef['unique'] ? 'UNIQUE ' : '';
                $columns = implode(', ', array_map([$this, 'quoteIdentifier'], $indexDef['columns']));
                $changes[] = [
                    'type' => 'add_index',
                    'table' => $table,
                    'index' => $indexName,
                    'sql' => $this->quoteIdentifier($table,
                        "CREATE {$unique}INDEX " . $this->quoteIdentifier($indexName) . " ON %s ($columns);"
                    ),
                ];
            }
        }
        
        // Dropped indexes
        foreach ($oldIndexes as $indexName => $indexDef) {
            if (!isset($newIndexes[$indexName]) && $indexName !== 'PRIMARY') {
                $changes[] = [
                    'type' => 'drop_index',
                    'table' => $table,
                    'index' => $indexName,
                    'sql' => $this->driver === 'mysql' 
                        ? $this->quoteIdentifier($table, "ALTER TABLE %s DROP INDEX " . $this->quoteIdentifier($indexName) . ";")
                        : "DROP INDEX " . $this->quoteIdentifier($indexName) . ";",
                ];
            }
        }
        
        return $changes;
    }
    
    private function columnChanged(array $old, array $new): bool
    {
        return $old['Type'] !== $new['Type']
            || $old['Null'] !== $new['Null']
            || ($old['Default'] ?? null) !== ($new['Default'] ?? null);
    }
    
    private function formatColumn(string $name, array $def): string
    {
        $sql = $this->quoteIdentifier($name) . ' ' . $def['Type'];
        
        if (($def['Null'] ?? 'YES') === 'NO') {
            $sql .= ' NOT NULL';
        }
        
        if (isset($def['Default']) && $def['Default'] !== null) {
            $sql .= " DEFAULT " . $this->quoteValue($def['Default']);
        }
        
        return $sql;
    }
    
    private function generateCreateTable(string $table, array $structure): string
    {
        // If we have the original CREATE TABLE SQL, use it
        if (!empty($structure['create_sql'])) {
            return $structure['create_sql'] . ';';
        }
        
        // Otherwise, build it from structure
        $columns = [];
        foreach ($structure['columns'] as $col) {
            $columns[] = '  ' . $this->formatColumn($col['Field'], $col);
        }
        
        $sql = "CREATE TABLE " . $this->quoteIdentifier($table) . " (
";
        $sql .= implode(",\n", $columns);
        $sql .= "\n);";
        
        return $sql;
    }
    
    private function indexByField(array $items, string $field): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $key = $item[$field] ?? null;
            if ($key !== null) {
                $indexed[$key] = $item;
            }
        }
        return $indexed;
    }
    
    private function quoteIdentifier(string $identifier, ?string $template = null): string
    {
        $quote = $this->driver === 'mysql' ? '`' : '"';
        $quoted = $quote . $identifier . $quote;
        return $template ? sprintf($template, $quoted) : $quoted;
    }
    
    private function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_numeric($value)) {
            return $value;
        }
        return "'" . addslashes($value) . "'";
    }
}
