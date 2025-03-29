<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;

/**
 * Class V150AddSettingsTable
 *
 * This migration creates the settings table for plugin configuration.
 */
class V160AddConsumptionType extends Migration
{
    /**
     * @var array<string>
     */
    protected array $dependencies = ['1.5.0'];

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
        $column_name = 'consumption_type';
    
        // Check if the column already exists
        $column_exists = $this->wpdb->get_results($this->wpdb->prepare(
            "SHOW COLUMNS FROM `{$this->wpdb->prefix}menu_items` LIKE %s",
            $column_name
        ));
    
        if (empty($column_exists)) {
            $this->wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `{$column_name}` VARCHAR(255) NULL AFTER `customer_phone`");
        }

        parent::up();       
    }

    /**
     * Insert default settings.
     */
    private function insertDefaultSettings(string $table_name): void
    {
        global $wpdb;
        
        // Default menu properties
        $default_properties = [
            __("Vegetarian", "daily-menu-manager"),
            __("Vegan", "daily-menu-manager"),
            __("Glutenfree", "daily-menu-manager"),
        ];
        
        $wpdb->insert(
            $table_name,
            [
                'setting_key' => 'menu_properties',
                'setting_value' => json_encode($default_properties)
            ]
        );
    }

    /**
     * Revert the migration.
     */
    public function down(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'menu_settings';

        // Drop the table if it exists
        if ($this->tableExists($table_name)) {
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }
    }

    /**
     * Get the version of this migration.
     */
    public function getVersion(): string
    {
        return '1.6.0';
    }

    /**
     * Get the description of this migration.
     */
    public function getDescription(): string
    {
        return 'Adds a consumption column to menu_items table';
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
            "{$wpdb->prefix}menu_settings"
        ];
    }

    /**
     * Validate prerequisites for this migration.
     */
    public function validatePrerequisites(): bool
    {
        return true;
    }

    /**
     * Get unique identifier for this migration.
     */
    public function getId(): string
    {
        return 'V160_add_consumption_type';
    }

    /**
     * Get timestamp when this migration was created.
     */
    public function getTimestamp(): int
    {
        return strtotime('2024-03-29');
    }
}