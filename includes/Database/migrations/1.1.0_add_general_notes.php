<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;
use wpdb;

/**
 * Class V110AddGeneralNotes
 *
 * This migration adds the general_notes column to the menu_orders table.
 */
class V110AddGeneralNotes extends Migration
{
    /**
     * @var array<string>
     */
    protected array $dependencies = ['1.0.0'];

    /**
     * @var int
     */
    protected int $batchSize = 500;

    /**
     * Apply the migration.
     */
    public function up(): void
    {
        $table_name = $this->wpdb->prefix . 'menu_orders';
        $column_name = 'general_notes';

        // Check if the column already exists
        $column_exists = $this->wpdb->get_results($this->wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            $column_name
        ));

        if (empty($column_exists)) {
            $this->wpdb->query("ALTER TABLE $table_name ADD COLUMN $column_name TEXT AFTER notes");
        }
        
        parent::up();
    }

    /**
     * Revert the migration.
     */
    public function down(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'menu_orders';
        $column_name = 'general_notes';

        // Check if the column exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            $column_name
        ));

        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN $column_name");
        }
    }

    /**
     * Get the version of this migration.
     */
    public function getVersion(): string
    {
        return '1.1.0';
    }

    /**
     * Get the description of this migration.
     */
    public function getDescription(): string
    {
        return 'Adds general_notes column to menu_orders table';
    }

    /**
     * Check if this migration can be reversed.
     */
    public function isReversible(): bool
    {
        return true;
    }

    /**
     * Get tables affected by this migration.
     *
     * @return array<string>
     */
    public function getAffectedTables(): array
    {
        global $wpdb;
        return [
            "{$wpdb->prefix}menu_orders"
        ];
    }

    /**
     * Validate prerequisites for this migration.
     */
    public function validatePrerequisites(): bool
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'menu_orders';
        
        if (!$this->tableExists($table_name)) {
            throw new \RuntimeException("Table '$table_name' does not exist");
        }
        
        return true;
    }

    /**
     * Get unique identifier for this migration.
     */
    public function getId(): string
    {
        return 'V110_add_general_notes';
    }

    /**
     * Get timestamp when this migration was created.
     */
    public function getTimestamp(): int
    {
        return strtotime('2024-02-01'); // Erstellungsdatum der Migration
    }

    /**
     * Get list of tables that must exist before this migration runs.
     *
     * @return array<string>
     */
    protected function getRequiredTables(): array
    {
        global $wpdb;
        return [
            "{$wpdb->prefix}menu_orders"
        ];
    }
}