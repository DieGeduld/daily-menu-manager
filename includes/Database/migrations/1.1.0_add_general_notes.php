<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;
use wpdb;

/**
 * Class AddGeneralNotes
 *
 * This migration adds the general_notes column to the menu_orders table.
 */
class AddGeneralNotes extends Migration
{
    /**
     * Apply the migration.
     */
    public function up()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'menu_orders';
        $column_name = 'general_notes';

        // Check if the column already exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            $column_name
        ));

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column_name TEXT AFTER notes");
        }
    }

    /**
     * Revert the migration.
     */
    public function down()
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
}