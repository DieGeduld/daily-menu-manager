<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;
use wpdb;

/**
 * Class V120AddCustomerPhonePickupTime
 *
 * This migration adds the customer_phone and pickup_time columns to the menu_orders table.
 */
class V120AddCustomerPhonePickupTime extends Migration
{
    /**
     * @var array<string>
     */
    protected array $dependencies = ['1.1.0'];

    /**
     * @var int
     */
    protected int $batchSize = 500;

    /**
     * Apply the migration.
     */
    public function up(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'menu_orders';

        // Add customer_phone column if it doesn't exist
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            'customer_phone'
        ));

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN customer_phone VARCHAR(50) AFTER customer_name");
        }

        // Add pickup_time column if it doesn't exist
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            'pickup_time'
        ));

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN pickup_time TIME AFTER general_notes");
        }
    }

    /**
     * Revert the migration.
     */
    public function down(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'menu_orders';

        // Remove customer_phone column if it exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            'customer_phone'
        ));

        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN customer_phone");
        }

        // Remove pickup_time column if it exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            'pickup_time'
        ));

        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN pickup_time");
        }
    }

    /**
     * Get the version of this migration.
     */
    public function getVersion(): string
    {
        return '1.2.0';
    }

    /**
     * Get the description of this migration.
     */
    public function getDescription(): string
    {
        return 'Adds customer_phone and pickup_time columns to menu_orders table';
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

        // Überprüfe, ob die vorherigen Migrationen ausgeführt wurden
        foreach ($this->dependencies as $version) {
            if (!$this->isMigrationCompleted($version)) {
                throw new \RuntimeException("Required migration version $version has not been executed");
            }
        }
        
        return true;
    }

    /**
     * Get unique identifier for this migration.
     */
    public function getId(): string
    {
        return 'V120_add_customer_phone_pickup_time';
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

    /**
     * Check if a specific migration version has been completed.
     *
     * @param string $version
     * @return bool
     */
    private function isMigrationCompleted(string $version): bool
    {
        global $wpdb;
        
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}dmm_migration_status WHERE version = %s",
            $version
        ));

        return $status === 'completed';
    }
}