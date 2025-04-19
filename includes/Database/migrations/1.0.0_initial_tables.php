<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;

/**
 * Class V100InitialTables
 *
 * This migration sets up the initial database tables for the Daily Menu Manager plugin.
 */
class V100InitialTables extends Migration
{
    /**
     * @var array<string> List of dependencies
     */
    protected array $dependencies = [];

    /**
     * @var int Batch size for processing
     */
    protected int $batchSize = 500;

    /**
     * Apply the migration.
     */
    public function up(): void
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $this->wpdb->get_charset_collate();

        // Create daily_menus table
        $sql_daily_menus = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}daily_menus (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            menu_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY menu_date (menu_date)
        ) $charset_collate;";

        // Create menu_items table
        $sql_menu_items = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}menu_items (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            menu_id mediumint(9) NOT NULL,
            item_type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL,
            sort_order int NOT NULL,
            allergens text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY menu_id (menu_id)
        ) $charset_collate;";

        // Create menu_orders table
        $sql_menu_orders = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}menu_orders (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            menu_id mediumint(9) NOT NULL,
            menu_item_id mediumint(9) NOT NULL,
            order_number varchar(50) NOT NULL,
            customer_name varchar(100) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            pickup_time time NOT NULL,
            customer_email varchar(100),
            quantity int NOT NULL DEFAULT 1,
            notes text,
            general_notes text,
            status varchar(50) DEFAULT 'pending',
            order_date datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_number (order_number),
            KEY status (status)
        ) $charset_collate;";

        // Execute the SQL statements
        dbDelta($sql_daily_menus);
        dbDelta($sql_menu_items);
        dbDelta($sql_menu_orders);

        parent::up();
    }

    /**
     * Revert the migration.
     */
    public function down(): void
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}daily_menus");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_items");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_orders");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}dmm_migration_status");
    }

    /**
     * Get the version of this migration.
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Get the description of this migration.
     */
    public function getDescription(): string
    {
        return 'Creates initial database tables for the Daily Menu Manager plugin';
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
            "{$wpdb->prefix}daily_menus",
            "{$wpdb->prefix}menu_items",
            "{$wpdb->prefix}menu_orders",
            "{$wpdb->prefix}dmm_migration_status",
        ];
    }

    /**
     * Validate prerequisites for this migration.
     */
    public function validatePrerequisites(): bool
    {
        // No prerequisites for initial migration
        return true;
    }

    /**
     * Get unique identifier for this migration.
     */
    public function getId(): string
    {
        return 'V100_initial_tables';
    }

    /**
     * Get timestamp when this migration was created.
     */
    public function getTimestamp(): int
    {
        return strtotime('2024-02-01'); // Datum der Erstellung der Migration
    }
}
