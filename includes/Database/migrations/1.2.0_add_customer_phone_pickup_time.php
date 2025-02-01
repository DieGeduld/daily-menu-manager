<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;
use wpdb;

/**
 * Class AddCustomerPhonePickupTime
 *
 * This migration adds the customer_phone and pickup_time columns to the menu_orders table.
 */

class V120AddCustomerPhonePickupTime extends Migration
{
    /**
     * Apply the migration.
     */
    public function up()
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
    public function down()
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
}