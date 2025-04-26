<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;

/**
 * Class V130AddAvailableQuantity
 *
 * This migration adds the available_quantity column to the menu_items table.
 */
class V130AddAvailableQuantity extends Migration
{
    /**
     * @var array<string>
     */
    protected array $dependencies = ['1.2.0'];

    /**
     * @var int
     */
    protected int $batchSize = 500;

    /**
     * Apply the migration.
     */
    public function up(): void
    {
        $table_name = $this->wpdb->prefix . 'ddm_menu_items';
        $column_name = 'available_quantity';

        // Check if the column already exists
        $column_exists = $this->wpdb->get_results($this->wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            $column_name
        ));

        if (empty($column_exists)) {
            $this->wpdb->query("ALTER TABLE $table_name ADD COLUMN $column_name INT NOT NULL DEFAULT 0 AFTER price");
        }
    }

    /**
     * Revert the migration.
     */
    public function down(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ddm_menu_items';
        $column_name = 'available_quantity';

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
        return '1.3.0';
    }

    /**
     * Get the description of this migration.
     */
    public function getDescription(): string
    {
        return 'Adds available_quantity column to menu_items table';
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
            "{$wpdb->prefix}ddm_menu_items",
        ];
    }

    /**
     * Validate prerequisites for this migration.
     */
    public function validatePrerequisites(): bool
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ddm_menu_items';

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
        return 'V130_add_available_quantity';
    }

    /**
     * Get timestamp when this migration was created.
     */
    public function getTimestamp(): int
    {
        return strtotime('2024-02-01'); // Erstellungsdatum der Migration
    }
}
