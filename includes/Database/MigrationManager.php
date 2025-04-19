<?php

namespace DailyMenuManager\Database;

use Exception;

/**
 * Class MigrationManager
 *
 * Handles loading and executing migrations. Tracks the current database version,
 * discovers migration files, and executes them in order.
 */
class MigrationManager
{
    /**
     * @var string The current database version.
     */
    private $currentVersion;

    /**
     * @var string The path to the migrations directory.
     */
    private $migrationsPath;

    /**
     * @var array Cache for loaded migration instances
     */
    private $loadedMigrations = [];

    /**
     * @var array Configuration options
     */
    private $config;

    /**
     * MigrationManager constructor.
     */
    public function __construct(array $config = [])
    {
        $this->migrationsPath = DMM_PLUGIN_DIR . 'includes/Database/migrations/';
        $this->currentVersion = get_option('daily_menu_manager_version', '0.0.0');
        $this->config = array_merge([
            'batchSize' => 1000,
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
        ], $config);

        $this->setupMigrationTable();
    }

    /**
     * Set up the migration status table if it doesn't exist
     */
    private function setupMigrationTable()
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'dmm_migration_status';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            version varchar(50) NOT NULL,
            batch int NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            dependencies text DEFAULT NULL,
            is_dry_run tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY batch (batch),
            KEY status (status)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function hasOpenMigrations(): bool
    {
        return $this->currentVersion !== '0.0.0' && $this->currentVersion !== DMM_VERSION;
    }

    /**
     * Get the current database version.
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * Set the current database version.
     */
    // public function setCurrentVersion($version)
    // {
    //     $this->validateMigrationVersion($version);
    //     $this->currentVersion = $version;
    //     update_option('daily_menu_manager_db_version', $version);
    // }

    /**
     * Discover migration files.
     */
    public function discoverMigrations()
    {
        $files = glob($this->migrationsPath . '*.php');
        usort($files, function ($a, $b) {
            $versionA = $this->extractVersion(basename($a, '.php'));
            $versionB = $this->extractVersion(basename($b, '.php'));

            return version_compare($versionA, $versionB);
        });

        return $files;
    }

    /**
     * Extract version from filename
     */
    private function extractVersion($filename)
    {
        // Unterstützt V100InitialTables und 1.0.0_initial_tables Format
        if (preg_match('/^V(\d)(\d{2})(\d+)/', $filename, $matches)) {
            return $matches[1] . '.' . $matches[2] . '.' . $matches[3];
        } elseif (preg_match('/^(\d+\.\d+\.\d+)/', $filename, $matches)) {
            return $matches[1];
        }

        return '0.0.0';
    }

    /**
     * Get the migration class name from filename
     */
    private function getMigrationClassName($file)
    {
        $baseName = basename($file, '.php');

        // Unterstützt beide Formate
        if (preg_match('/^V(\d+)/', $baseName)) {
            $className = $baseName;
        } else {
            // Konvertiert 1.0.0_initial_tables zu V100InitialTables
            $className = preg_replace('/^(\d+)\.(\d+)\.(\d+)_/', 'V$1$2$3_', $baseName);
            $className = str_replace('_', '', ucwords($className, '_'));
        }

        return 'DailyMenuManager\\Database\\migrations\\' . $className;
    }

    /**
     * Get a migration instance
     */
    private function getMigrationInstance($file)
    {
        // Return cached instance if available
        if (isset($this->loadedMigrations[$file])) {
            return $this->loadedMigrations[$file];
        }

        if (! file_exists($file)) {
            throw new Exception("Migration file not found: $file");
        }

        require_once $file;
        $className = $this->getMigrationClassName($file);

        if (! class_exists($className)) {
            throw new Exception("Migration class $className not found in file $file");
        }

        $migration = new $className();
        if (! $migration instanceof Migration) {
            throw new Exception("Class $className must extend Migration");
        }

        // Set batch size from config
        $migration->setBatchSize($this->config['batchSize']);

        // Cache the instance
        $this->loadedMigrations[$file] = $migration;

        return $migration;
    }

    /**
     * Execute a migration
     */
    private function executeMigration($file, $manualExecution): bool
    {
        $migration = $this->getMigrationInstance($file);

        if (! $migration->canAutorun() && ! $manualExecution) {
            $migration->logMigration("Autorun is disabled for automatic migration {$migration->getVersion()}");

            return false;
        }
        $migration->up();

        return true;
    }

    /**
     * Validate migration version format
     */
    private function validateMigrationVersion($version)
    {
        if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw new Exception("Invalid migration version format: $version");
        }
    }

    /**
     * Run migrations
     */
    public function runMigrations($manualExecution = false)
    {
        global $wpdb;
        $migrations = $this->discoverMigrations();
        $batch = $this->getNextBatchNumber();
        $this->log("Starting migrations batch #$batch");

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Erstelle einen Abhängigkeitsgraphen
            $dependencyGraph = [];
            foreach ($migrations as $file) {
                $version = $this->extractVersion(basename($file, '.php'));
                $migration = $this->getMigrationInstance($file);
                $dependencyGraph[$version] = $migration->getDependencies();
            }

            // Sortiere Migrationen nach Abhängigkeiten
            $sortedVersions = $this->topologicalSort($dependencyGraph);

            // Führe Migrationen in sortierter Reihenfolge aus
            foreach ($sortedVersions as $version) {
                $file = $this->findMigrationFile($version);

                if ($this->shouldRunMigration($version)) {
                    $this->log("Running migration $version");
                    $this->recordMigrationStart($version, $batch);

                    try {
                        if ($this->executeMigration($file, $manualExecution)) {
                            $this->recordMigrationSuccess($version);
                            $this->log("Successfully completed migration $version");
                        } else {
                            $this->log("Skipping migration $version - autorun disabled");
                            $this->recordMigrationSkip($version, $batch);

                            break;
                        }
                    } catch (\Exception $e) {
                        $this->log($e->getMessage());
                        $this->recordMigrationError($version, $e->getMessage());

                        throw $e;
                    }
                } else {
                    $this->log("Skipping migration $version - already executed");
                }
            }

            $wpdb->query('COMMIT');
            $this->log("All migrations completed successfully");

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->log("Migration failed, rolling back changes: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Topologische Sortierung der Migrationen
     */
    private function topologicalSort($graph)
    {
        $sorted = [];
        $visited = [];
        $temp = [];

        // Für jeden Knoten im Graphen
        foreach ($graph as $node => $edges) {
            if (! isset($visited[$node])) {
                $this->visit($node, $graph, $visited, $temp, $sorted);
            }
        }

        return $sorted;
    }

    /**
     * Hilfsfunktion für topologische Sortierung
     */
    private function visit($node, &$graph, &$visited, &$temp, &$sorted)
    {
        if (isset($temp[$node])) {
            throw new Exception("Circular dependency detected: $node");
        }
        if (! isset($visited[$node])) {
            $temp[$node] = true;

            if (isset($graph[$node])) {
                foreach ($graph[$node] as $dependency) {
                    $this->visit($dependency, $graph, $visited, $temp, $sorted);
                }
            }

            $visited[$node] = true;
            unset($temp[$node]);
            $sorted[] = $node;
        }
    }

    /**
     * Findet die Migrationsdatei für eine bestimmte Version
     */
    private function findMigrationFile($version)
    {
        $files = glob($this->migrationsPath . '*.php');

        foreach ($files as $file) {
            if ($this->extractVersion(basename($file, '.php')) === $version) {
                return $file;
            }
        }

        throw new Exception("Migration file for version $version not found");
    }

    private function getNextBatchNumber()
    {
        global $wpdb;
        $lastBatch = $wpdb->get_var("
            SELECT MAX(batch) 
            FROM {$wpdb->prefix}dmm_migration_status
        ");

        return (int)$lastBatch + 1;
    }

    private function recordMigrationStart($version, $batch)
    {
        global $wpdb;

        $versionExists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dmm_migration_status WHERE version = %s",
            $version
        ));

        if (! $versionExists) {

            $wpdb->insert(
                $wpdb->prefix . 'dmm_migration_status',
                [
                    'version' => $version,
                    'batch' => $batch,
                    'status' => 'running',
                    'started_at' => current_time('mysql'),
                    'is_dry_run' => 0,
                ]
            );

        } else {

            $wpdb->update(
                $wpdb->prefix . 'dmm_migration_status',
                [
                    'status' => 'skipped',
                    'error_message' => 'Skipped due to autorun disabled',
                    'completed_at' => current_time('mysql'),
                ],
                ['version' => $version]
            );
        }
    }

    private function recordMigrationSuccess($version)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'dmm_migration_status',
            [
                'status' => 'completed',
                'error_message' => null,
                'completed_at' => current_time('mysql'),
            ],
            ['version' => $version]
        );
    }

    private function recordMigrationError($version, $errorMessage)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'dmm_migration_status',
            [
                'status' => 'failed',
                'error_message' => $errorMessage,
                'completed_at' => current_time('mysql'),
            ],
            ['version' => $version]
        );
    }

    private function recordMigrationSkip($version, $errorMessage)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'dmm_migration_status',
            [
                'status' => 'skipped',
                'error_message' => 'Skipped due to autorun disabled',
                'completed_at' => current_time('mysql'),
            ],
            ['version' => $version]
        );
    }

    private function shouldRunMigration($version)
    {
        global $wpdb;

        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}dmm_migration_status WHERE version = %s",
            $version
        ));

        return ! $status || $status === 'failed' || $status === 'skipped';
    }

    private function checkDependencies($version)
    {
        // Finde die tatsächliche Migrationsdatei basierend auf der Version
        $files = glob($this->migrationsPath . '*.php');
        $migrationFile = null;

        foreach ($files as $file) {
            if ($this->extractVersion(basename($file, '.php')) === $version) {
                $migrationFile = $file;

                break;
            }
        }

        if (! $migrationFile) {
            throw new Exception("Migration file for version $version not found");
        }

        $migration = $this->getMigrationInstance($migrationFile);
        $dependencies = $migration->getDependencies();

        foreach ($dependencies as $dependency) {
            if (! $this->isMigrationCompleted($dependency)) {
                throw new Exception("Dependency not satisfied: $dependency");
            }
        }

        return true;
    }

    private function isMigrationCompleted($version)
    {
        global $wpdb;

        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}dmm_migration_status WHERE version = %s",
            $version
        ));

        return $status === 'completed';
    }

    private function log($message)
    {
        if ($this->config['debug']) {
            error_log("DailyMenuManager Migration: $message");
        }
    }

    public function getBatchSize()
    {
        return $this->config['batchSize'];
    }

    public function setBatchSize($size)
    {
        $this->config['batchSize'] = (int)$size;
    }
}
