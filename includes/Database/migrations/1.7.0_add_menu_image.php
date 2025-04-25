<?php

namespace DailyMenuManager\Database\migrations;

use DailyMenuManager\Database\Migration;

class V170AddMenuImage extends Migration
{
    protected int $batchSize = 100;

    public function up(): void
    {
        $table_name = $this->wpdb->prefix . 'menu_items';
        $column_name = 'image_id';
        $column_name2 = 'image_url';

        // Check if the column already exists
        $column_exists = $this->wpdb->get_results($this->wpdb->prepare(
            "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
            $column_name
        ));
        if (empty($column_exists)) {
            $this->wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN {$column_name} BIGINT UNSIGNED NULL AFTER `allergens`");
        }

        $column_exists2 = $this->wpdb->get_results($this->wpdb->prepare(
            "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
            $column_name2
        ));
        if (empty($column_exists2)) {
            $this->wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN {$column_name2} VARCHAR(255) NULL AFTER `allergens`");
        }
        parent::up();
    }

    public function down(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'daily_menu_manager_menus';

        $sql = "ALTER TABLE {$table_name} 
                DROP COLUMN image_id,
                DROP COLUMN image_url";

        $wpdb->query($sql);
    }

    public function getVersion(): string
    {
        return '1.7.0';
    }

    public function getDescription(): string
    {
        return 'Adds image support to daily menus';
    }

    public function isReversible(): bool
    {
        return true;
    }

    public function getDependencies(): array
    {
        return ['1.6.0'];
    }

    public function getAffectedTables(): array
    {
        global $wpdb;

        return [$wpdb->prefix . 'daily_menu_manager_menus'];
    }

    public function setBatchSize(int $size): void
    {
        $this->batchSize = $size;
    }

    public function validatePrerequisites(): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'daily_menu_manager_menus';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;

        if (!$table_exists) {
            throw new \RuntimeException("Required table {$table} does not exist");
        }

        return true;
    }

    public function getId(): string
    {
        return $this->getVersion();
    }

    public function getTimestamp(): int
    {
        return strtotime('2024-01-17');  // Use the date when this migration was created
    }
}
