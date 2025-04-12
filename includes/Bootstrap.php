<?php
namespace DailyMenuManager;

class Bootstrap {
    public static function init(): void {
        self::loadDependencies();
        self::registerHooks();
    }

    private static function loadDependencies(): void {
        // Load Composer if available
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        // Register autoloader
        spl_autoload_register([self::class, 'autoload']);
    }

    private static function autoload(string $class): void {
        if (strpos($class, 'DailyMenuManager\\') !== 0) {
            return;
        }

        $path = __DIR__ . '/';
        $file = str_replace(['DailyMenuManager\\', '\\'], ['', '/'], $class) . '.php';
        
        if (file_exists($path . $file)) {
            require_once $path . $file;
        }
    }

    private static function registerHooks(): void {
        // Core plugin hooks
        register_activation_hook(DMM_PLUGIN_FILE, [Installer::class, 'activate']);
        register_deactivation_hook(DMM_PLUGIN_FILE, [Installer::class, 'deactivate']);
        
        // Load translations at init hook
        add_action('init', function() {
            load_plugin_textdomain(
                DMM_TEXT_DOMAIN,
                false,
                dirname(plugin_basename(DMM_PLUGIN_FILE)) . '/languages/'
            );
            
            $locale = determine_locale();
            $mofile = plugin_dir_path(DMM_PLUGIN_FILE) . 'languages/' . DMM_TEXT_DOMAIN . '-' . $locale . '.mo';
            
            if (file_exists($mofile)) {
                load_textdomain('daily-menu-manager', $mofile);
            }

        });
        // Initialize plugin after WordPress loads, but after translations
        add_action('init', function() {
            Plugin::getInstance();
        }, 11); // Höhere Priorität als Übersetzungen
        
        // Check for updates
        add_action('init', [self::class, 'checkForUpdates'], 12);
    }

    public static function checkForUpdates(): void {
        $installed_version = get_option('daily_menu_manager_version');
        
        if ($installed_version !== DMM_VERSION) {
            try {
                $migration_manager = new Database\MigrationManager();
                $migration_manager->runMigrations();
                if ($migration_manager->hasOpenMigrations()) {
                    add_action('admin_notices', function() use ($installed_version) {
                        printf(
                            '<div class="notice notice-warning"><p>%s</p></div>',
                            esc_html(
                                sprintf(
                                    __('Daily Menu Manager needs database update from version %s to %s. Please visit the settings page to run the update.', 'daily-menu-manager'),
                                    $installed_version,
                                    DMM_VERSION
                                )
                            )
                        );
                    });

                } else {
                    add_action('admin_notices', function() {
                        printf(
                            '<div class="error"><p>%s</p></div>',
                            esc_html(
                                sprintf(
                                    __('Daily Menu Manager successfully updated to version %s', 'daily-menu-manager'),
                                    DMM_VERSION
                                )
                            )
                        );
                    });
                    update_option('daily_menu_manager_version', DMM_VERSION);
                }
                
            } catch (\Exception $e) {
                error_log('Daily Menu Manager update failed: ' . $e->getMessage());
                add_action('admin_notices', function() use ($e) {
                    printf(
                        '<div class="error"><p>%s</p></div>',
                        esc_html(
                            sprintf(
                                __('Daily Menu Manager update failed: %s', 'daily-menu-manager'),
                                $e->getMessage()
                            )
                        )
                    );
                });
            }
        }
    }
}