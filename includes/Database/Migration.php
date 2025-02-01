<?php

namespace DailyMenuManager\Database;

use DailyMenuManager\Contracts\Database\MigrationInterface;

/**
 * Class Migration
 * 
 * This abstract class serves as the base for all database migrations.
 * Each migration should extend this class and implement the up() and down() methods.
 */
abstract class Migration implements MigrationInterface
{
    protected $dependencies = [];
    protected $batchSize;

    abstract public function up(): void;
    abstract public function down(): void;

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function setBatchSize(int $size): void
    {
        $this->batchSize = (int)$size;
    }

    protected function runInBatches(callable $operation)
    {
        global $wpdb;
        
        if (!$this->batchSize) {
            throw new \Exception('Batch size must be set before running batch operations');
        }
        
        $offset = 0;
        do {
            $affected = $operation($this->batchSize, $offset);
            $offset += $this->batchSize;
            
            // Nach jedem Batch kurz pausieren um den Server zu entlasten
            if ($affected >= $this->batchSize) {
                usleep(100000); // 100ms Pause
            }
        } while ($affected >= $this->batchSize);
    }

    protected function tableExists($tableName)
    {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName;
    }

    protected function columnExists($tableName, $columnName)
    {
        global $wpdb;
        return !empty($wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $tableName LIKE %s",
            $columnName
        )));
    }
}