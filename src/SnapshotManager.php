<?php

namespace DbDiffAuditor;

class SnapshotManager
{
    // PHP8 style properties
    // private string $snapshotPath;
    
    // PHP7 style properties
    /** @var string */
    private $snapshotPath;
    public function __construct(string $path = '.db-snapshots')
    {
        $this->snapshotPath = rtrim($path, '/');
        if (!is_dir($this->snapshotPath)) {
            mkdir($this->snapshotPath, 0755, true);
        }
    }
    
    public function create(array $schema, ?string $message = null): string
    {
        $filename = 'snapshot-' . date('Ymd-His') . '.json';
        $filepath = $this->snapshotPath . '/' . $filename;
        
        $data = [
            'schema' => $schema,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        
        return $filename;
    }
    
    public function getLatest(): ?array
    {
        $files = $this->getSnapshotFiles();
        if (empty($files)) {
            return null;
        }
        
        $latestFile = $files[0];
        return $this->load(basename($latestFile));
    }
    
    public function getAll(): array
    {
        $files = $this->getSnapshotFiles();
        
        return array_map(function($file) {
            $data = json_decode(file_get_contents($file), true);
            return [
                'filename' => basename($file),
                'created' => $data['created_at'] ?? date('Y-m-d H:i:s', filemtime($file)),
                'message' => $data['message'] ?? null,
                'size' => filesize($file),
            ];
        }, $files);
    }
    
    public function load(string $filename): array
    {
        $filepath = $this->snapshotPath . '/' . $filename;
        if (!file_exists($filepath)) {
            throw new \RuntimeException("Snapshot not found: $filename");
        }
        
        $data = json_decode(file_get_contents($filepath), true);
        return $data['schema'] ?? $data; // Handle both old and new format
    }
    
    private function getSnapshotFiles(): array
    {
        $files = glob($this->snapshotPath . '/snapshot-*.json');
        rsort($files); // Most recent first
        return $files;
    }
    
    public function delete(string $filename): bool
    {
        $filepath = $this->snapshotPath . '/' . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}
