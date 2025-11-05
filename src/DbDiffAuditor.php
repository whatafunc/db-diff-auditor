<?php

namespace DbDiffAuditor;

class DbDiffAuditor
{
    // PHP8 style properties
    //private array $config;
    // private DatabaseInspector $inspector;
    // private SnapshotManager $snapshots;
    // private DiffGenerator $differ;

    // PHP7 style properties
    /** @var array */
    private $config;
    /** @var DatabaseInspector */
    private $inspector;
    /** @var SnapshotManager */
    private  $snapshots;
    /** @var DiffGenerator */
    private $differ;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->inspector = new DatabaseInspector($config['connection']);
        $this->snapshots = new SnapshotManager($config['snapshot_path'] ?? '.db-snapshots');
        $this->differ = new DiffGenerator($config['connection']['driver'] ?? 'mysql');
    }
    
    public function createSnapshot(?string $message = null): string
    {
        $schema = $this->inspector->introspectAll($this->config['ignore_tables'] ?? []);
        return $this->snapshots->create($schema, $message);
    }
    
    public function getChanges(): array
    {
        $current = $this->inspector->introspectAll($this->config['ignore_tables'] ?? []);
        $last = $this->snapshots->getLatest();
        
        if (!$last) {
            throw new 	tamente("No snapshots found. Create one first with createSnapshot()");
        }
        
        return $this->differ->compare($last, $current);
    }
    
    public function getSnapshots(): array
    {
        return $this->snapshots->getAll();
    }
    
    public function exportChanges(array $changes): string
    {
        $sql = "-- Database changes detected at " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($changes as $change) {
            $sql .= "-- {$change['type']}: {$change['table']}";
            if (isset($change['column'])) {
                $sql .= ".{$change['column']}";
            }
            $sql .= "\n{$change['sql']}\n\n";
        }
        
        return $sql;
    }
}
