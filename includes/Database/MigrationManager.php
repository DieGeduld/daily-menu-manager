<?php

namespace DailyMenuManager\Database;

use wpdb;

class MigrationManager {
    private $wpdb;
    private $migrationsPath;
    private $migrationsTable;

    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
        $this->migrationsPath = DMM_PLUGIN_DIR . 'includes/Database/Migrations/';
        $this->migrationsTable = $this->wpdb->prefix . 'dmm_migrations';
        $this->createMigrationsTable();
    }

    private function createMigrationsTable() {
        $charset_collate = $this->wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY migration (migration)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function runMigrations() {
        $appliedMigrations = $this->getAppliedMigrations();
        $availableMigrations = $this->getAvailableMigrations();

        foreach ($availableMigrations as $migration) {
            if (!in_array($migration, $appliedMigrations)) {
                $this->runMigration($migration);
            }
        }
    }

    private function getAppliedMigrations() {
        $results = $this->wpdb->get_results("SELECT migration FROM {$this->migrationsTable}", ARRAY_A);
        return array_column($results, 'migration');
    }

    private function getAvailableMigrations() {
        $files = scandir($this->migrationsPath);
        $migrations = array_filter($files, function($file) {
            return preg_match('/^M\d+_.+\.php$/', $file);
        });
        sort($migrations);
        return $migrations;
    }

    private function runMigration($migration) {
        require_once $this->migrationsPath . $migration;
        $className = pathinfo($migration, PATHINFO_FILENAME);
        if (class_exists($className)) {
            $migrationInstance = new $className($this->wpdb);
            $migrationInstance->up();
            $this->recordMigration($migration);
        }
    }

    private function rollbackMigration($migration) {
        require_once $this->migrationsPath . $migration;
        $className = pathinfo($migration, PATHINFO_FILENAME);
        if (class_exists($className)) {
            $migrationInstance = new $className($this->wpdb);
            $migrationInstance->down();
            $this->removeMigrationRecord($migration);
        }
    }

    private function recordMigration($migration) {
        $this->wpdb->insert($this->migrationsTable, ['migration' => $migration], ['%s']);
    }

    private function removeMigrationRecord($migration) {
        $this->wpdb->delete($this->migrationsTable, ['migration' => $migration], ['%s']);
    }
}
