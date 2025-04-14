<?php
namespace DailyMenuManager\Admin;

use DailyMenuManager\Models\Settings;

class SettingsController {
    private static $instance = null;
    
    // Globale Definition der Währungssymbole
    private static $currencySymbols = [
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        'CHF' => 'CHF',
        'JPY' => '¥',
        'CAD' => 'C$',
        'AUD' => 'A$',
        'PLN' => 'zł',
        'custom' => '', // Wird dynamisch aus den Einstellungen geladen
    ];
    
    public static function init(): void {
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
            'daily-menu-manager-settings',
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
            
            // Speichere die Währung
            if (isset($_POST['daily_menu_currency'])) {
                $currency = sanitize_text_field($_POST['daily_menu_currency']);
                $settings_model->set('currency', $currency);
                
                // Speichere benutzerdefiniertes Währungssymbol wenn "custom" ausgewählt wurde
                if ($currency === 'custom' && isset($_POST['daily_menu_custom_currency_symbol'])) {
                    $custom_currency_symbol = sanitize_text_field($_POST['daily_menu_custom_currency_symbol']);
                    $settings_model->set('custom_currency_symbol', $custom_currency_symbol);
                }
            }
            
            // Speichere das Preisformat
            if (isset($_POST['daily_menu_price_format'])) {
                $price_format = sanitize_text_field($_POST['daily_menu_price_format']);
                $settings_model->set('price_format', $price_format);
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
            
            // Speichere die Menütypen
            $menu_types_labels = isset($_POST['daily_menu_types_labels']) ? $_POST['daily_menu_types_labels'] : [];
            $menu_types_plurals = isset($_POST['daily_menu_types_plurals']) ? $_POST['daily_menu_types_plurals'] : [];
            $menu_types = [];
            
            // Use StringUtils to generate keys from labels
            foreach ($menu_types_labels as $index => $label) {
                $label = sanitize_text_field($label);
                
                if (!empty($label)) {
                    // Generate key from label using StringUtils
                    $key = \DailyMenuManager\Helper\StringUtils::hard_sanitize($label);
                    
                    // Ensure key is unique by adding a suffix if needed
                    $original_key = $key;
                    $counter = 1;
                    while (isset($menu_types[$key])) {
                        $key = $original_key . '_' . $counter;
                        $counter++;
                    }
                    
                    // Get the plural form if available
                    $plural = isset($menu_types_plurals[$index]) ? sanitize_text_field($menu_types_plurals[$index]) : '';
                    
                    $menu_types[$key] = [
                        'label' => $label,
                        'plural' => $plural
                    ];
                }
            }
            
            if (!empty($menu_types)) {
                $settings_model->set('menu_types', $menu_types);
            }
            
            // Zeige eine Erfolgsmeldung an
            add_settings_error(
                'daily_menu_properties',
                'settings_updated',
                __('Settings saved.', 'daily-menu-manager'),
                'success'
            );
        }

        // Check if the migration button was pressed
        if (isset($_POST['run_migrations']) && check_admin_referer('daily_menu_settings_nonce')) {
            try {
                $migration_manager = new \DailyMenuManager\Database\MigrationManager();
                $migration_manager->runMigrations(true);
                
                update_option('daily_menu_manager_version', DMM_VERSION);
                
                \DailyMenuManager\Plugin::addAdminNotice(
                    __('Database update completed successfully.', 'daily-menu-manager'),
                    'success'
                );
            } catch (\Exception $e) {
                \DailyMenuManager\Plugin::addAdminNotice(
                    sprintf(
                        __('Database update failed: %s', 'daily-menu-manager'),
                        $e->getMessage()
                    ),
                    'error'
                );
            }
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
        
        // Set default values if empty
        if (empty($properties)) {
            $properties = [
                __("Vegetarian", "daily-menu-manager"),
                __("Vegan", "daily-menu-manager"),
                __("Glutenfree", "daily-menu-manager"),
            ];
            
            // Store in the database for future use
            $settings_model->set('menu_properties', $properties);
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
        
        // Get main color from database
        $main_color = $settings_model->get('main_color');
        
        // Set default value if empty
        if (empty($main_color)) {
            $main_color = '#2271b1';
            
            // Store in the database for future use
            $settings_model->set('main_color', $main_color);
        }
        
        return $main_color;
    }

    /**
     * Get default currency based on WordPress locale
     * 
     * @return string The default currency code
     */
    private static function getDefaultCurrencyByLocale(): string {
        $locale = get_locale();
        
        // Mapping von Locales zu Währungen
        $locale_currency_map = [
            'de_DE' => 'EUR',
            'de_AT' => 'EUR',
            'de_CH' => 'CHF',
            'de_LU' => 'EUR',
            'en_US' => 'USD',
            'en_GB' => 'GBP',
            'en_CA' => 'CAD',
            'en_AU' => 'AUD',
            'fr_FR' => 'EUR',
            'fr_CA' => 'CAD',
            'fr_CH' => 'CHF',
            'it_IT' => 'EUR',
            'ja' => 'JPY',
            'pl_PL' => 'PLN',
            'es_ES' => 'EUR',
            // Weitere Locales hinzufügen
        ];
        
        return $locale_currency_map[$locale] ?? 'EUR'; // Standard-Fallback auf EUR
    }

    /**
     * Get default price format based on WordPress locale
     * 
     * @return string The default price format
     */
    private static function getDefaultPriceFormatByLocale(): string {
        $locale = get_locale();
        
        // Mapping von Locales zu Preisformaten
        $locale_format_map = [
            // Europäische Länder verwenden meist Komma als Dezimaltrennzeichen und Symbol rechts
            'de_DE' => 'symbol_comma_right',
            'de_AT' => 'symbol_comma_right',
            'fr_FR' => 'symbol_comma_right',
            'it_IT' => 'symbol_comma_right',
            'es_ES' => 'symbol_comma_right',
            'pl_PL' => 'symbol_comma_right',
            
            // Englischsprachige Länder verwenden meist Punkt als Dezimaltrennzeichen
            'en_US' => 'symbol_dot_left', // $ vor dem Betrag
            'en_GB' => 'symbol_dot_right', // £ nach dem Betrag
            'en_CA' => 'symbol_dot_left',
            'en_AU' => 'symbol_dot_left',
            
            // Schweiz hat oft eigene Regeln
            'de_CH' => 'symbol_dot_right',
            'fr_CH' => 'symbol_dot_right',
            
            // Japan
            'ja' => 'symbol_dot_right',
        ];
        
        return $locale_format_map[$locale] ?? 'symbol_comma_right'; // Standard-Fallback
    }


    /**
     * Get currency
     * 
     * @return string The selected currency
     */
    public static function getCurrency(): string {
        Settings::init();
        $settings_model = Settings::getInstance();
        
        // Get currency from database
        $currency = $settings_model->get('currency');
        
        // Set default value based on WordPress locale if empty
        if (empty($currency)) {
            $currency = self::getDefaultCurrencyByLocale();
            
            // Store in the database for future use
            $settings_model->set('currency', $currency);
        }
        
        return $currency;
    }

    /**
     * Get available currencies
     * 
     * @return array The available currencies
     */
    public static function getAvailableCurrencies(): array {
        return [
            'EUR' => __('Euro (€)', 'daily-menu-manager'),
            'USD' => __('US Dollar ($)', 'daily-menu-manager'),
            'GBP' => __('British Pound (£)', 'daily-menu-manager'),
            'CHF' => __('Swiss Franc (CHF)', 'daily-menu-manager'),
            'JPY' => __('Japanese Yen (¥)', 'daily-menu-manager'),
            'CAD' => __('Canadian Dollar (C$)', 'daily-menu-manager'),
            'AUD' => __('Australian Dollar (A$)', 'daily-menu-manager'),
            'PLN' => __('Polish Złoty (zł)', 'daily-menu-manager'),
            'custom' => __('Custom currency', 'daily-menu-manager'),
        ];
    }
    
    /**
     * Get custom currency symbol
     * 
     * @return string The custom currency symbol
     */
    public static function getCustomCurrencySymbol(): string {
        Settings::init();
        $settings_model = Settings::getInstance();
        
        // Get custom currency symbol from database with default value
        $custom_currency_symbol = $settings_model->get('custom_currency_symbol', '€');
        
        return $custom_currency_symbol;
    }

    /**
     * Get the current currency symbol
     * 
     * @return string The currency symbol
     */
    public static function getCurrencySymbol(): string {
        $currency = self::getCurrency();
        
        // Wenn es sich um eine benutzerdefinierte Währung handelt, aktualisiere den Wert
        if ($currency === 'custom') {
            self::$currencySymbols['custom'] = self::getCustomCurrencySymbol();
        }
        
        return self::$currencySymbols[$currency] ?? $currency;
    }

    /**
     * Get price format
     * 
     * @return string The selected price format
     */
    public static function getPriceFormat(): string {
        Settings::init();
        $settings_model = Settings::getInstance();
        
        // Get price format from database
        $price_format = $settings_model->get('price_format');
        
        // Set default value based on WordPress locale if empty
        if (empty($price_format)) {
            $price_format = self::getDefaultPriceFormatByLocale();
            
            // Store in the database for future use
            $settings_model->set('price_format', $price_format);
        }
        
        return $price_format;
    }

    /**
     * Get available price formats
     * 
     * @return array The available price formats
     */
    public static function getAvailablePriceFormats(): array {
        $symbol = SettingsController::getCurrencySymbol();
        return [
            'symbol_comma_right' => sprintf(__('European format (9,99 %s)', 'daily-menu-manager'), $symbol),
            'symbol_dot_right' => sprintf(__('Anglo-American format (9.99 %s)', 'daily-menu-manager'), $symbol),
            'symbol_comma_left' => sprintf(__('European format, symbol first (%s 9,99)', 'daily-menu-manager'), $symbol),
            'symbol_dot_left' => sprintf(__('Anglo-American format, symbol first (%s 9.99)', 'daily-menu-manager'), $symbol),
            'symbol_comma_attached' => sprintf(__('Compact European format (9,99%s)', 'daily-menu-manager'), $symbol),
            'symbol_dot_attached' => sprintf(__('Compact Anglo-American format (9.99%s)', 'daily-menu-manager'), $symbol),
        ];
    }
    
    /**
     * Get example for price format
     * 
     * @param string $format The price format
     * @param string $currency The currency code
     * @return string Example for the price format
     */
    public static function getPriceFormatExample(string $format, string $currency): string {
        // Wenn es sich um eine benutzerdefinierte Währung handelt, aktualisiere den Wert
        if ($currency === 'custom') {
            self::$currencySymbols['custom'] = self::getCustomCurrencySymbol();
        }
        
        $symbol = self::$currencySymbols[$currency] ?? $currency;
        $price = 9.99;
        
        switch ($format) {
            case 'symbol_comma_right':
                return '9,99 ' . $symbol;
            case 'symbol_dot_right':
                return '9.99 ' . $symbol;
            case 'symbol_comma_left':
                return $symbol . ' 9,99';
            case 'symbol_dot_left':
                return $symbol . ' 9.99';
            case 'symbol_comma_attached':
                return '9,99' . $symbol;
            case 'symbol_dot_attached':
                return '9.99' . $symbol;
            default:
                return '9,99 ' . $symbol;
        }
    }
    
    /**
     * Format price according to settings
     *
     * @param float $price The price to format
     * @return string The formatted price
     */
    public static function formatPrice(float $price): string {
        $format = self::getPriceFormat();
        $currency = self::getCurrency();
        
        // Wenn es sich um eine benutzerdefinierte Währung handelt, aktualisiere den Wert
        if ($currency === 'custom') {
            self::$currencySymbols['custom'] = self::getCustomCurrencySymbol();
        }
        
        $symbol = self::$currencySymbols[$currency] ?? $currency;
        
        switch ($format) {
            case 'symbol_comma_right':
                return number_format($price, 2, ',', '.') . ' ' . $symbol;
            case 'symbol_dot_right':
                return number_format($price, 2, '.', ',') . ' ' . $symbol;
            case 'symbol_comma_left':
                return $symbol . ' ' . number_format($price, 2, ',', '.');
            case 'symbol_dot_left':
                return $symbol . ' ' . number_format($price, 2, '.', ',');
            case 'symbol_comma_attached':
                return number_format($price, 2, ',', '.') . $symbol;
            case 'symbol_dot_attached':
                return number_format($price, 2, '.', ',') . $symbol;
            default:
                return number_format($price, 2, ',', '.') . ' ' . $symbol;
        }
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

    /**
     * Get menu types
     * 
     * @return array The menu types
     */
    public static function getMenuTypes(): array {
        Settings::init();
        $settings_model = Settings::getInstance();

        // Get menu types from database
        $menu_types = $settings_model->get('menu_types');
        
        // Set default values if empty
        if (empty($menu_types)) {
            $menu_types = [
                'appetizer' => [
                    'label' => __('Appetizer', 'daily-menu-manager'),
                    'plural' => __('Appetizers', 'daily-menu-manager'),
                    'enabled' => true
                ],
                'main_course' => [
                    'label' => __('Main Course', 'daily-menu-manager'),
                    'plural' => __('Main Courses', 'daily-menu-manager'),
                    'enabled' => true
                ],
                'dessert' => [
                    'label' => __('Dessert', 'daily-menu-manager'),
                    'plural' => __('Desserts', 'daily-menu-manager'),
                    'enabled' => true
                ]
            ];
            
            // Store in the database for future use
            $settings_model->set('menu_types', $menu_types);
        }
        
        return $menu_types;
    }
}  // This closing bracket was likely missing