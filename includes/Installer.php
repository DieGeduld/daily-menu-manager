<?php
namespace DailyMenuManager;

class Installer {
    /**
     * Aktiviert das Plugin
     * Wird bei der Plugin-Aktivierung aufgerufen
     */
    public static function activate() {
        $migrationManager = new \DailyMenuManager\Database\MigrationManager();
        $migrationManager->runMigrations();
        self::addCapabilities();
        self::createDefaultOptions();
        self::createUploadDirectory();
        
        // Setze Flag für Willkommensnachricht
        set_transient('dmm_activation_redirect', true, 30);
    }

    /**
     * Deaktiviert das Plugin
     * Wird bei der Plugin-Deaktivierung aufgerufen
     */
    public static function deactivate() {
        self::removeScheduledEvents();
        // Capabilities werden absichtlich nicht entfernt
    }

    /**
     * Deinstalliert das Plugin vollständig
     * Wird nur aufgerufen, wenn das Plugin gelöscht wird
     */
    public static function uninstall() {
        self::dropTables();
        self::removeOptions();
        self::removeCapabilities();
        self::removeUploadDirectory();
    }


    /**
     * Erstellt die Standard-Plugin-Optionen
     */
    private static function createDefaultOptions() {
        $default_options = [
            'daily_menu_manager_settings' => [
                'currency' => '€',
                'order_prefix' => date('Ymd') . '-',
                'enable_email_notifications' => true,
                'notification_email' => get_option('admin_email'),
                'menu_types' => [
                    'appetizer' => [
                        'label' => __('Vorspeise', 'daily-menu-manager'),
                        'enabled' => true
                    ],
                    'main_course' => [
                        'label' => __('Hauptgang', 'daily-menu-manager'),
                        'enabled' => true
                    ],
                    'dessert' => [
                        'label' => __('Nachspeise', 'daily-menu-manager'),
                        'enabled' => true
                    ]
                ],
                'order_statuses' => [
                    'pending' => __('Ausstehend', 'daily-menu-manager'),
                    'confirmed' => __('Bestätigt', 'daily-menu-manager'),
                    'completed' => __('Abgeschlossen', 'daily-menu-manager'),
                    'cancelled' => __('Storniert', 'daily-menu-manager')
                ]
            ]
        ];

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
    }

    /**
     * Fügt Benutzer-Capabilities hinzu
     */
    private static function addCapabilities() {
        $roles = ['administrator', 'editor'];
        $capabilities = [
            'manage_daily_menu' => true,
            'edit_daily_menu' => true,
            'view_orders' => true,
            'manage_orders' => true,
        ];

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap => $grant) {
                    $role->add_cap($cap, $grant);
                }
            }
        }
    }

    /**
     * Erstellt das Upload-Verzeichnis für das Plugin
     */
    private static function createUploadDirectory() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/daily-menu-manager';

        if (!file_exists($plugin_upload_dir)) {
            wp_mkdir_p($plugin_upload_dir);
            
            // Erstelle .htaccess zum Schutz des Verzeichnisses
            $htaccess_content = "Order Deny,Allow\\nDeny from all\\n";
            file_put_contents($plugin_upload_dir . '/.htaccess', $htaccess_content);
            
            // Erstelle index.php für zusätzliche Sicherheit
            file_put_contents($plugin_upload_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Entfernt geplante Events
     */
    private static function removeScheduledEvents() {
        wp_clear_scheduled_hook('daily_menu_manager_daily_cleanup');
        wp_clear_scheduled_hook('daily_menu_manager_order_reminder');
    }

    /**
     * Löscht die Plugin-Tabellen
     */
    private static function dropTables() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}daily_menus");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_items");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_orders");
    }

    /**
     * Entfernt alle Plugin-Optionen
     */
    private static function removeOptions() {
        delete_option('daily_menu_manager_db_version');
        delete_option('daily_menu_manager_settings');
        delete_option('daily_menu_manager_notices');
        
        // Entferne alle Transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '%_transient_daily_menu_%' 
             OR option_name LIKE '%_transient_timeout_daily_menu_%'"
        );
    }

    /**
     * Entfernt Benutzer-Capabilities
     */
    private static function removeCapabilities() {
        $roles = ['administrator', 'editor'];
        $capabilities = [
            'manage_daily_menu',
            'edit_daily_menu',
            'view_orders',
            'manage_orders',
        ];

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }

    /**
     * Entfernt das Upload-Verzeichnis
     */
    private static function removeUploadDirectory() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/daily-menu-manager';

        if (file_exists($plugin_upload_dir)) {
            self::recursiveRemoveDirectory($plugin_upload_dir);
        }
    }

    /**
     * Hilfsfunktion: Rekursives Löschen eines Verzeichnisses
     */
    private static function recursiveRemoveDirectory($directory) {
        foreach (glob("{$directory}/*") as $file) {
            if (is_dir($file)) {
                self::recursiveRemoveDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($directory);
    }

    /**
     * Prüft die Systemanforderungen
     * @return bool|array True wenn alle Anforderungen erfüllt sind, sonst Array mit Fehlern
     */
    public static function checkSystemRequirements() {
        $errors = [];
        
        // PHP Version
        if (version_compare(PHP_VERSION, '7.2', '<')) {
            $errors[] = sprintf(
                __('Daily Menu Manager benötigt PHP 7.2 oder höher. Aktuell läuft PHP %s.', 'daily-menu-manager'),
                PHP_VERSION
            );
        }
        
        // WordPress Version
        if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
            $errors[] = __('Daily Menu Manager benötigt WordPress 5.0 oder höher.', 'daily-menu-manager');
        }
        
        // Erforderliche PHP-Erweiterungen
        $required_extensions = ['mysqli', 'json'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = sprintf(
                    __('Die PHP-Erweiterung %s wird benötigt.', 'daily-menu-manager'),
                    $ext
                );
            }
        }
        
        return empty($errors) ? true : $errors;
    }

    /**
     * Erstellt oder aktualisiert die Datenbank
     * @return bool True bei Erfolg
     */
    public static function updateDatabase() {
        $installed_version = get_option('daily_menu_manager_db_version');
        
        if ($installed_version !== DMM_VERSION) {
            self::createTables();
            return true;
        }
        
        return false;
    }
}