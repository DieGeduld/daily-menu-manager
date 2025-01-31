<?php

namespace DailyMenuManager\Database\Migrations;

use wpdb;

class AddCustomerPhonePickupTime {
    private $wpdb;

    public function __construct(wpdb $wpdb) {
        $this->wpdb = $wpdb;
    }

    /**
     * Run the migrations.
     */
    public function up() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $this->wpdb->get_charset_collate();

        // Alter menu_orders table to add customer_phone and pickup_time columns
        $sql = "ALTER TABLE {$this->wpdb->prefix}menu_orders 
                ADD COLUMN IF NOT EXISTS customer_phone varchar(50) AFTER customer_name,
                ADD COLUMN IF NOT EXISTS pickup_time time AFTER general_notes;";

        dbDelta($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down() {
        // Remove customer_phone and pickup_time columns from menu_orders table
        $sql = "ALTER TABLE {$this->wpdb->prefix}menu_orders 
                DROP COLUMN IF EXISTS customer_phone,
                DROP COLUMN IF EXISTS pickup_time;";

        $this->wpdb->query($sql);
    }
}
