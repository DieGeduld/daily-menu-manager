<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;
use wpdb;

/**
 * Class V1_0_0_InitialTables
 *
 * This migration sets up the initial database tables for the Daily Menu Manager plugin.
 */
class V1_0_0_InitialTables extends Migration
{
    /**
     * Apply the migration.
     */
    public function up()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Create daily_menus table
        $sql_daily_menus = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}daily_menus (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            menu_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY menu_date (menu_date)
        ) $charset_collate;";

        // Create menu_items table
        $sql_menu_items = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menu_items (
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
        $sql_menu_orders = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menu_orders (
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
    }

    /**
     * Revert the migration.
     */
    public function down()
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}daily_menus");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_items");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_orders");
    }
}