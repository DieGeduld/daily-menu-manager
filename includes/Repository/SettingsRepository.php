<?php

namespace DailyMenuManager\Repository;

use DailyMenuManager\Entity\SettingsEntity;

/**
 * Class SettingsRepository
 *
 * Repository for managing plugin settings stored in the database.
 */
class SettingsRepository
{
    private static ?self $instance = null;
    private string $table;

    /**
     * Initialize the Settings repository and set up the hooks.
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
        $this->table = $wpdb->prefix . 'ddm_menu_settings';
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
     * Get a setting by key.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return SettingsEntity|null The setting entity or null
     */
    public function get(string $key, $default = null): ?SettingsEntity
    {
        global $wpdb;

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$this->table} WHERE setting_key = %s",
            $key
        ));

        if ($value === null) {
            if ($default === null) {
                return null;
            }

            return new SettingsEntity([
                'key' => $key,
                'value' => $default
            ]);
        }

        return new SettingsEntity([
            'key' => $key,
            'value' => json_decode($value, true)
        ]);
    }

    /**
     * Get a setting value directly by key.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed The setting value or default
     */
    public function getValue(string $key, $default = null)
    {
        $entity = $this->get($key, $default);
        return $entity ? $entity->getValue() : $default;
    }

    /**
     * Set a setting value.
     *
     * @param SettingsEntity $entity The setting entity
     * @return bool Whether the operation was successful
     */
    public function save(SettingsEntity $entity): bool
    {
        global $wpdb;

        $key = $entity->getKey();
        $value = $entity->getValue();
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
     * Set a setting value directly.
     *
     * @param string $key The setting key
     * @param mixed $value The setting value (will be JSON encoded)
     * @return bool Whether the operation was successful
     */
    public function set(string $key, $value): bool
    {
        $entity = new SettingsEntity([
            'key' => $key,
            'value' => $value
        ]);

        return $this->save($entity);
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
     * Get all settings as an array of SettingsEntity objects.
     *
     * @return SettingsEntity[] All settings
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
                $settings[] = new SettingsEntity([
                    'key' => $row['setting_key'],
                    'value' => json_decode($row['setting_value'], true)
                ]);
            }
        }

        return $settings;
    }

    /**
     * Get all settings as an associative array.
     *
     * @return array All settings
     */
    public function getAllAsArray(): array
    {
        $entities = $this->getAll();
        $settings = [];

        foreach ($entities as $entity) {
            $settings[$entity->getKey()] = $entity->getValue();
        }

        return $settings;
    }
}
