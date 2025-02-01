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
     * MigrationManager constructor.
     */
    public function __construct()
    {
        $this->migrationsPath = DMM_PLUGIN_DIR . 'includes/Database/migrations/';
        $this->currentVersion = get_option('daily_menu_manager_db_version', '0.0.0');
    }

    /**
     * Get the current database version.
     *
     * @return string
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * Set the current database version.
     *
     * @param string $version
     */
    public function setCurrentVersion($version)
    {
        $this->currentVersion = $version;
        update_option('daily_menu_manager_db_version', $version);
    }

    /**
     * Discover migration files.
     *
     * @return array
     */
    public function discoverMigrations()
    {
        $files = glob($this->migrationsPath . '*.php');
        usort($files, function ($a, $b) {
            return version_compare(basename($a, '.php'), basename($b, '.php'));
        });
        return $files;
    }

    /**
     * Run migrations.
     */
    public function runMigrations()
    {
        global $wpdb;
        $migrations = $this->discoverMigrations();
        
        $wpdb->query('START TRANSACTION');
        try {
            foreach ($migrations as $file) {
                $version = basename($file, '.php');
                if (version_compare(substr($version, 0, 5), substr($this->currentVersion, 0, 5), '<=')) {
                    $this->executeMigration($file);
                    $this->setCurrentVersion($version);
                }
            }
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Execute a migration.
     *
     * @param string $file
     * @throws Exception
     */
    private function executeMigration($file)
    {
        $className = $this->getMigrationClassName($file);
        $this->includeMigrationFile($file);

        if (!class_exists($className)) {
            throw new Exception("Migration class $className not found in file $file.");
        }

        $migration = new $className();
        if (!$migration instanceof Migration) {
            throw new Exception("Migration class $className must extend Migration.");
        }

        $migration->up();
    }

    /**
     * Get the migration class name from the file name.
     *
     * @param string $file
     * @return string
     */
    private function getMigrationClassName($file)
    {
        $baseName = basename($file, '.php');
        $className = preg_replace('/^(\d+)\.(\d+)\.(\d+)_/', 'V$1$2$3', $baseName);
        $className = str_replace('_', '', ucwords($className, '_'));
        return 'DailyMenuManager\\Database\\migrations\\' . $className;
    }

    /**
     * Include the migration file.
     *
     * @param string $file
     */
    private function includeMigrationFile($file)
    {
        require_once $file;
    }

    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("DailyMenuManager Migration: $message");
        }
    }

    private function validateMigrationVersion($version) {
        if (!preg_match('/^\d+\.\d+\.\d+/', $version)) {
            throw new Exception("Invalid migration version format: $version");
        }
    }
}