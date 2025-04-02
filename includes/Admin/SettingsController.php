<?php
namespace DailyMenuManager\Admin;

use DailyMenuManager\Models\Settings;

class SettingsController {
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        add_action('admin_menu', [self::class, 'addAdminMenu']);
    }
    
    /**
     * Fügt den Einstellungen-Menüpunkt hinzu
     */
    public static function addAdminMenu() {
        add_submenu_page(
            'daily-menu-manager',
            __('Settings', 'daily-menu-manager'),
            __('Settings', 'daily-menu-manager'),
            'manage_options',
            'daily-menu-settings',
            [self::class, 'displaySettingsPage']
        );
    }
    
    /**
     * Zeigt die Einstellungsseite an und verarbeitet das Formular
     */
    public static function displaySettingsPage() {
        // Ensure Settings model is initialized
        Settings::init();
        $settings_model = Settings::getInstance();
        
        // Verarbeite das Formular, wenn es abgesendet wurde
        if (isset($_POST['save_menu_settings']) && check_admin_referer('daily_menu_settings_nonce')) {
            $properties = isset($_POST['daily_menu_properties']) ? $_POST['daily_menu_properties'] : [];
            $sanitized_properties = [];
            
            foreach ($properties as $property) {
                if (!empty($property)) {
                    $sanitized_properties[] = sanitize_text_field($property);
                }
            }
            
            // Store in database
            $settings_model->set('menu_properties', $sanitized_properties);
            
            // Also update in WordPress options for backward compatibility
            update_option('daily_menu_properties', $sanitized_properties);
            
            // Speichere die Hauptfarbe
            if (isset($_POST['daily_menu_main_color'])) {
                // Verwende unsere eigene Funktion, falls WordPress-Funktion nicht verfügbar
                if (function_exists('sanitize_hex_color')) {
                    $main_color = sanitize_hex_color($_POST['daily_menu_main_color']);
                } else {
                    $main_color = sanitize_text_field($_POST['daily_menu_main_color']);
                }
                
                if ($main_color) {
                    $settings_model->set('main_color', $main_color);
                }
            }
            
            // Speichere das Datumsformat
            if (isset($_POST['daily_menu_date_format'])) {
                $date_format = sanitize_text_field($_POST['daily_menu_date_format']);
                $settings_model->set('date_format', $date_format);
            }
            
            // Speichere die Konsumtypen
            $consumption_types = isset($_POST['daily_menu_consumption_types']) ? $_POST['daily_menu_consumption_types'] : [];
            $sanitized_consumption_types = [];
            
            foreach ($consumption_types as $type) {
                if (!empty($type)) {
                    $sanitized_consumption_types[] = sanitize_text_field($type);
                }
            }
            
            $settings_model->set('consumption_types', $sanitized_consumption_types);
            
            // Zeige eine Erfolgsmeldung an
            add_settings_error(
                'daily_menu_properties',
                'settings_updated',
                __('Settings saved.', 'daily-menu-manager'),
                'success'
            );
        }
        
        // Lade das Template
        require_once DMM_PLUGIN_DIR . 'includes/Views/admin-settings-page.php';
    }
    
    /**
     * Get menu properties
     * 
     * @return array The menu properties
     */
    public static function getMenuProperties(): array {
        Settings::init();
        $settings_model = Settings::getInstance();
        
        // Try to get from database first
        $properties = $settings_model->get('menu_properties');
        
        // Fallback to WordPress options if not found
        if (empty($properties)) {
            $properties = get_option('daily_menu_properties', [
                __("Vegetarian", "daily-menu-manager"),
                __("Vegan", "daily-menu-manager"),
                __("Glutenfree", "daily-menu-manager"),
            ]);
            
            // Store in the database for future use
            if (!empty($properties)) {
                $settings_model->set('menu_properties', $properties);
            }
        }
        
        return $properties;
    }
    
    /**
     * Get main color
     * 
     * @return string The main color in hex format
     */
    public static function getMainColor(): string {
        Settings::init();
        $settings_model = Settings::getInstance();
        
        // Get main color from database with default value
        $main_color = $settings_model->get('main_color', '#3498db');
        
        return $main_color;
    }

    /**
     * Get date format
     * 
     * @return string The date format
     */
    public static function getDateFormat(): string {
        Settings::init();
        $settings_model = Settings::getInstance();
        
        // Get date format from database with default value
        $date_format = $settings_model->get('date_format', 'd.m.Y');
        
        return $date_format;
    }
    
    /**
     * Get available date formats
     * 
     * @return array The available date formats
     */
    public static function getAvailableDateFormats(): array {
        return [
            'd.m.Y' => __('DD.MM.YYYY (e.g. 31.03.2025)', 'daily-menu-manager'),
            'Y-m-d' => __('YYYY-MM-DD (e.g. 2025-03-31)', 'daily-menu-manager'),
            'm/d/Y' => __('MM/DD/YYYY (e.g. 03/31/2025)', 'daily-menu-manager'),
            'd/m/Y' => __('DD/MM/YYYY (e.g. 31/03/2025)', 'daily-menu-manager'),
            'j. F Y' => __('D. Month YYYY (e.g. 31. March 2025)', 'daily-menu-manager'),
        ];
    }
    
    /**
     * Get consumption types
     * 
     * @return array The consumption types
     */
    public static function getConsumptionTypes(): array {
        Settings::init();
        $settings_model = Settings::getInstance();
        
        // Get consumption types from database with default values
        $consumption_types = $settings_model->get('consumption_types');
        
        if (empty($consumption_types)) {
            $consumption_types = [
                __('Pick up', 'daily-menu-manager'),
                __('Eat in', 'daily-menu-manager'),
            ];
            
            // Store in the database for future use
            $settings_model->set('consumption_types', $consumption_types);
        }
        
        return $consumption_types;
    }
}