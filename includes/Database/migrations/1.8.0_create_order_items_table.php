<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;

class V180CreateOrderItemsTable extends Migration
{
    protected int $batchSize = 100;

    public function up(): void
    {
        $table_name = $this->wpdb->prefix . 'daily_menu_order_items';

        // Check if the table already exists
        $table_exists = $this->wpdb->get_results(
            "SHOW TABLES LIKE '{$table_name}'"
        );

        if (!$table_exists) {
            $charset_collate = $this->wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                order_id BIGINT(20) UNSIGNED NOT NULL,
                menu_id BIGINT(20) UNSIGNED NOT NULL,
                menu_item_id BIGINT(20) UNSIGNED NOT NULL,
                quantity INT(11) UNSIGNED NOT NULL DEFAULT 1,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                title VARCHAR(255) NOT NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY order_id (order_id),
                KEY menu_id (menu_id),
                KEY menu_item_id (menu_item_id)
            ) {$charset_collate};";

            $this->wpdb->query($sql);
        }

        parent::up();
    }

    public function down(): void
    {
        $table_name = $this->wpdb->prefix . 'daily_menu_order_items';

        $this->wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }

    public function getVersion(): string
    {
        return '1.8.0';
    }

    public function getDescription(): string
    {
        return 'Creates the order items table to support multiple items per order';
    }

    public function isReversible(): bool
    {
        return true;
    }

    public function getDependencies(): array
    {
        return ['1.7.0'];
    }

    public function getAffectedTables(): array
    {
        return [$this->wpdb->prefix . 'daily_menu_order_items'];
    }

    public function setBatchSize(int $size): void
    {
        $this->batchSize = $size;
    }

    public function validatePrerequisites(): bool
    {
        $orderTable = $this->wpdb->prefix . 'menu_orders';
        $order_table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$orderTable}'") === $orderTable;

        if (!$order_table_exists) {
            throw new \RuntimeException("Required table {$orderTable} does not exist");
        }

        return true;
    }

    public function getId(): string
    {
        return $this->getVersion();
    }

    public function getTimestamp(): int
    {
        return strtotime('2024-04-25');  // Today's date as the migration creation date
    }
}
