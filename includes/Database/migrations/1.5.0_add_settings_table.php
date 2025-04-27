<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;

/**
 * Class V150AddSettingsTable
 *
 * This migration creates the settings table for plugin configuration.
 */
class V150AddSettingsTable extends Migration
{
    /**
     * @var array<string>
     */
    protected array $dependencies = ['1.4.0'];

    /**
     * @var int
     */
    protected int $batchSize = 500;

    /**
     * Apply the migration.
     */
    public function up(): void
    {
        $table_name = $this->wpdb->prefix . 'ddm_menu_settings';
        $charset_collate = $this->wpdb->get_charset_collate();

        // Check if the table already exists
        if (!$this->tableExists($table_name)) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                setting_key varchar(100) NOT NULL,
                setting_value longtext NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY setting_key (setting_key)
            ) $charset_collate;";

            if (!function_exists('dbDelta')) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }
            dbDelta($sql);

            // Insert default values
            $this->insertDefaultSettings($table_name);
        }
    }

    /**
     * Insert default settings.
     */
    private function insertDefaultSettings(string $table_name): void
    {
        global $wpdb;

        // Default menu properties
        $default_properties = [
            __("Vegetarian", DMM_TEXT_DOMAIN),
            __("Vegan", DMM_TEXT_DOMAIN),
            __("Glutenfree", DMM_TEXT_DOMAIN),
        ];

        $wpdb->insert(
            $table_name,
            [
                'setting_key' => 'menu_properties',
                'setting_value' => json_encode($default_properties),
            ]
        );
    }

    /**
     * Revert the migration.
     */
    public function down(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ddm_menu_settings';

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
        return '1.5.0';
    }

    /**
     * Get the description of this migration.
     */
    public function getDescription(): string
    {
        return 'Creates settings table for plugin configuration';
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
            "{$wpdb->prefix}ddm_menu_settings",
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
        return 'V150_add_settings_table';
    }

    /**
     * Get timestamp when this migration was created.
     */
    public function getTimestamp(): int
    {
        return strtotime('2024-03-22');
    }
}
