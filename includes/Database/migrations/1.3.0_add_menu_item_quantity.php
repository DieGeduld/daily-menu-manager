<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;
use wpdb;

/**
 * Class AddMenuItemQuantity
 *
 * This migration adds the quantity column to the menu_items table.
 */
class V130AddMenuItemQuantity extends Migration
{
    protected $dependencies = ['1.0.0', '1.1.0', '1.2.0'];
    protected $batchSize = 500;

    /**
     * Apply the migration.
     */
    public function up()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'menu_items';
        $column_name = 'quantity';

        // Check if the column already exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            $column_name
        ));

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column_name INT NOT NULL DEFAULT 0 AFTER price");
        }
    }

    /**
     * Revert the migration.
     */
    public function down()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'menu_items';
        $column_name = 'quantity';

        // Check if the column exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            $column_name
        ));

        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN $column_name");
        }
    }
}
