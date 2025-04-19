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
    protected array $dependencies = [];

    protected int $batchSize;

    protected $wpdb;

    protected bool $autorun = true;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logMigration("Running migration {$this->getVersion()}");
    }

    public function canAutorun(): bool
    {
        return $this->autorun;
    }

    public function up(): void
    {
        if ($this->wpdb->last_error) {
            $this->logMigration("Failed to run migration {$this->getVersion()}: {$this->wpdb->last_error}");

            throw new \Exception("Failed to run migration {$this->getVersion()}: {$this->wpdb->last_error}");
        } else {
            $this->logMigration("Successfully ran migration {$this->getVersion()}");

        }
    }

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

        if (! $this->batchSize) {
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

        return ! empty($wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $tableName LIKE %s",
            $columnName
        )));
    }

    public function logMigration(string $message): void
    {
        $logFile = DMM_PLUGIN_DIR . 'logs/errors.log';

        if (! file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }

        if (! file_exists($logFile)) {
            touch($logFile);
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $class = get_class($this);
            error_log(
                "[" . date("Y-m-d H:i:s") . "] {$class}: {$message}",
                3,
                DMM_PLUGIN_DIR . 'logs/errors.log'
            );
        }
    }
}
