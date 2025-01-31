<?php

namespace DailyMenuManager\Database;

use wpdb;

class MigrationManager {
    private $wpdb;
    private $migrationsTable;

    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
        $this->migrationsTable = $this->wpdb->prefix . 'dmm_migrations';
    }

    /**
     * Initialize the migrations table if it doesn't exist.
     */
    public function initializeMigrationsTable() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            migration_name varchar(255) NOT NULL,
            migrated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY migration_name (migration_name)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Run pending migrations.
     */
    public function runMigrations(array $migrations) {
        $this->initializeMigrationsTable();

        foreach ($migrations as $migration) {
            if (!$this->isMigrationRun($migration)) {
                $this->runMigration($migration);
                $this->markMigrationAsRun($migration);
            }
        }
    }

    /**
     * Check if a migration has already been run.
     */
    private function isMigrationRun($migration) {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->migrationsTable} WHERE migration_name = %s",
            $migration
        ));

        return $result > 0;
    }

    /**
     * Execute a single migration.
     */
    private function runMigration($migration) {
        // Include the migration file and run it
        include_once DMM_PLUGIN_DIR . "includes/Database/Migrations/{$migration}.php";
        $migrationClass = "\\DailyMenuManager\\Database\\Migrations\\$migration";
        if (class_exists($migrationClass)) {
            $migrationInstance = new $migrationClass($this->wpdb);
            $migrationInstance->up();
        }
    }

    /**
     * Mark a migration as run in the database.
     */
    private function markMigrationAsRun($migration) {
        $this->wpdb->insert(
            $this->migrationsTable,
            ['migration_name' => $migration],
            ['%s']
        );
    }

    /**
     * Rollback a migration.
     */
    public function rollbackMigration($migration) {
        include_once DMM_PLUGIN_DIR . "includes/Database/Migrations/{$migration}.php";
        $migrationClass = "\\DailyMenuManager\\Database\\Migrations\\$migration";
        if (class_exists($migrationClass)) {
            $migrationInstance = new $migrationClass($this->wpdb);
            $migrationInstance->down();
            $this->unmarkMigrationAsRun($migration);
        }
    }

    /**
     * Unmark a migration as run in the database.
     */
    private function unmarkMigrationAsRun($migration) {
        $this->wpdb->delete(
            $this->migrationsTable,
            ['migration_name' => $migration],
            ['%s']
        );
    }
}
