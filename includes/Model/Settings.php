<?php

namespace DailyMenuManager\Model;

/**
 * Class Settings
 *
 * Model for managing plugin settings stored in the database.
 */
class Settings
{
    private static ?self $instance = null;
    private string $table;

    /**
     * Initialize the Settings model and set up the hooks.
     */
    public static function init(): void
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
    }

    /**
     * Settings constructor.
     */
    private function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'menu_settings';
    }

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get a setting value by key.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed The setting value or default
     */
    public function get(string $key, $default = null)
    {
        global $wpdb;

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$this->table} WHERE setting_key = %s",
            $key
        ));

        if ($value === null) {
            return $default;
        }

        return json_decode($value, true);
    }

    /**
     * Set a setting value.
     *
     * @param string $key The setting key
     * @param mixed $value The setting value (will be JSON encoded)
     * @return bool Whether the operation was successful
     */
    public function set(string $key, $value): bool
    {
        global $wpdb;

        $json_value = json_encode($value);

        // Check if the setting already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE setting_key = %s",
            $key
        ));

        if ($exists) {
            // Update
            $result = $wpdb->update(
                $this->table,
                ['setting_value' => $json_value],
                ['setting_key' => $key]
            );
        } else {
            // Insert
            $result = $wpdb->insert(
                $this->table,
                [
                    'setting_key' => $key,
                    'setting_value' => $json_value,
                ]
            );
        }

        return $result !== false;
    }

    /**
     * Delete a setting by key.
     *
     * @param string $key The setting key
     * @return bool Whether the operation was successful
     */
    public function delete(string $key): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table,
            ['setting_key' => $key]
        );

        return $result !== false;
    }

    /**
     * Check if a setting exists.
     *
     * @param string $key The setting key
     * @return bool Whether the setting exists
     */
    public function exists(string $key): bool
    {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE setting_key = %s",
            $key
        ));

        return $count > 0;
    }

    /**
     * Get all settings as an associative array.
     *
     * @return array All settings
     */
    public function getAll(): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT setting_key, setting_value FROM {$this->table}",
            ARRAY_A
        );

        $settings = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $settings[$row['setting_key']] = json_decode($row['setting_value'], true);
            }
        }

        return $settings;
    }
}
