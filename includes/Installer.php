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

    public static function run_updates() {
        // Get the stored version
        $installed_version = get_option('daily_menu_manager_version');
        $plugin_version = DMM_VERSION; // Define this in your main plugin file

        // If versions don't match, run migrations
        if ($installed_version !== $plugin_version) {
            $migration_manager = new Database\MigrationManager();
            
            try {
                $migration_manager->runMigrations();
                // Update the stored version after successful migration
                update_option('daily_menu_manager_version', $plugin_version);
            } catch (\Exception $e) {
                // Log error and maybe show admin notice
                error_log('Daily Menu Manager migration failed: ' . $e->getMessage());
                add_action('admin_notices', function() use ($e) {
                    echo '<div class="error"><p>Daily Menu Manager update failed: ' . esc_html($e->getMessage()) . '</p></div>';
                });
            }
        }
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
                        'label' => __('Appetizer', 'daily-menu-manager'),
                        'plural' => __('Appetizers', 'daily-menu-manager'),
                        'enabled' => true
                    ],
                    'main_course' => [
                        'label' => __('Main Course', 'daily-menu-manager'),
                        'plural' => __('Main Course', 'daily-menu-manager'),
                        'enabled' => true
                    ],
                    'dessert' => [
                        'label' => __('Dessert', 'daily-menu-manager'),
                        'plural' => __('Dessert', 'daily-menu-manager'),
                        'enabled' => true
                    ]
                ],
                'order_statuses' => [
                    'pending' => __('Pending', 'daily-menu-manager'),
                    'confirmed' => __('Confirmed', 'daily-menu-manager'),
                    'completed' => __('Completed', 'daily-menu-manager'),
                    'cancelled' => __('Cancelled', 'daily-menu-manager')
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
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_settings");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}dmm_migration_status");
    }

    /**
     * Entfernt alle Plugin-Optionen
     */
    private static function removeOptions() {
        delete_option('daily_menu_manager_version');
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
                'Daily Menu Manager requires PHP 7.2 or higher. Currently running PHP %s.', PHP_VERSION
            );
        }
        
        // WordPress Version
        if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
            $errors[] = __('Daily Menu Manager requires WordPress 5.0 or higher.', 'daily-menu-manager');
        }
        
        // Erforderliche PHP-Erweiterungen
        $required_extensions = ['mysqli', 'json'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = sprintf(
                    'The PHP extension %s is required.',
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
        $installed_version = get_option('daily_menu_manager_version');
        
        if ($installed_version !== DMM_VERSION) {
            self::createTables();
            return true;
        }
        
        return false;
    }

    /**
     * Erstellt die Datenbanktabellen
     * @throws \RuntimeException wenn die Tabellenerstellung fehlschlägt
     * @return bool True bei Erfolg
     */
    private static function createTables(): bool
    {
        try {
            $migrationManager = new Database\MigrationManager();
            $migrationManager->runMigrations();

            // Aktualisiere die gespeicherte Datenbankversion
            update_option('daily_menu_manager_version', DMM_VERSION);
            
            return true;
        } catch (\Exception $e) {
            error_log('Daily Menu Manager table creation failed: ' . $e->getMessage());
            throw new \RuntimeException(
                sprintf(
                    __('Error creating tables: %s', 'daily-menu-manager'),
                    $e->getMessage()
                )
            );
        }
    }
}