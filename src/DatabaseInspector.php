<?php

namespace DbDiffAuditor;

use PDO;
use PDOException;

class DatabaseInspector

{
    // PHP8 style properties
    // private PDO $pdo;
    // private string $driver;

    // PHP7 style properties
    /** @var PDO */
    private $pdo;
    /** @var string */
    private $driver;
    public function __construct(array $config)
    {
        $this->driver = $config['driver'] ?? 'mysql';
        
        try {
            $dsn = "{$this->driver}:host={$config['host']};dbname={$config['database']}";
            if (isset($config['port'])) {
                $dsn .= ";port={$config['port']}";
            }
            
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getTables(): array
    {
        if ($this->driver === 'mysql') {
            $stmt = $this->pdo->query("SHOW TABLES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($this->driver === 'pgsql') {
            $stmt = $this->pdo->query("                SELECT tablename FROM pg_tables 
                WHERE schemaname = 'public'
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        throw new \RuntimeException("Unsupported driver: {$this->driver}");
    }
    
    public function getTableStructure(string $table): array
    {
        if ($this->driver === 'mysql') {
            return $this->getMySQLTableStructure($table);
        } elseif ($this->driver === 'pgsql') {
            return $this->getPostgreSQLTableStructure($table);
        }
        
        throw new \RuntimeException("Unsupported driver: {$this->driver}");
    }
    
    private function getMySQLTableStructure(string $table): array
    {
        // Get columns
        $columns = $this->pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get indexes
        $indexes = $this->pdo->query("SHOW INDEXES FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get foreign keys
        $foreignKeys = $this->pdo->query("            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '$table' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get table creation SQL for reference
        $createTable = $this->pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        
        return [
            'columns' => $columns,
            'indexes' => $this->normalizeIndexes($indexes),
            'foreign_keys' => $foreignKeys,
            'create_sql' => $createTable['Create Table'] ?? null,
        ];
    }
    
    private function getPostgreSQLTableStructure(string $table): array
    {
        // Get columns
        $columns = $this->pdo->query("            SELECT 
                column_name as \"Field\",
                data_type || 
                CASE WHEN character_maximum_length IS NOT NULL 
                     THEN '(' || character_maximum_length || ')' 
                     ELSE '' END as \"Type\",
                is_nullable as \"Null\",
                column_default as \"Default\"
            FROM information_schema.columns
            WHERE table_name = '$table'
            ORDER BY ordinal_position
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get indexes
        $indexes = $this->pdo->query("            SELECT 
                i.relname as \"Key_name\",
                a.attname as \"Column_name\",
                ix.indisunique as \"Non_unique\"
            FROM pg_class t
            JOIN pg_index ix ON t.oid = ix.indrelid
            JOIN pg_class i ON i.oid = ix.indexrelid
            JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
            WHERE t.relname = '$table'
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'columns' => $columns,
            'indexes' => $this->normalizeIndexes($indexes),
            'foreign_keys' => [],
            'create_sql' => null,
        ];
    }
    
    private function normalizeIndexes(array $indexes): array
    {
        $normalized = [];
        foreach ($indexes as $index) {
            $key = $index['Key_name'] ?? $index['Column_name'];
            if (!isset($normalized[$key])) {
                $normalized[$key] = [
                    'name' => $key,
                    'columns' => [],
                    'unique' => !($index['Non_unique'] ?? true),
                ];
            }
            $normalized[$key]['columns'][] = $index['Column_name'];
        }
        return array_values($normalized);
    }
    
    public function introspectAll(array $ignoreTables = []): array
    {
        $schema = [
            'version' => '1.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'driver' => $this->driver,
            'tables' => [],
        ];
        
        foreach ($this->getTables() as $table) {
            if (in_array($table, $ignoreTables)) {
                continue;
            }
            $schema['tables'][$table] = $this->getTableStructure($table);
        }
        
        return $schema;
    }
}
