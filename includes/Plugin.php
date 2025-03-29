<?php
namespace DailyMenuManager;

class Plugin {
    private static ?self $instance = null;
    private array $menu_types;
    private bool $initialized = false;

    public static function getInstance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (!$this->initialized) {
            $this->initializePlugin();
        }
    }

    private function initializePlugin(): void {
        $this->setupMenuTypes();
        $this->initializeComponents();
        $this->checkForUpdates();
        $this->initialized = true;
    }

    // Todo: 
    private function setupMenuTypes(): void {
        $this->menu_types = [
            'appetizer' => [
                'label' => __('Appetizer', 'daily-menu-manager'),
                'label_de' => __('Vorspeise', 'daily-menu-manager')
            ],
            'main_course' => [
                'label' => __('Main Course', 'daily-menu-manager'),
                'label_de' => __('Hauptgang', 'daily-menu-manager')
            ],
            'dessert' => [
                'label' => __('Dessert', 'daily-menu-manager'),
                'label_de' => __('Nachspeise', 'daily-menu-manager')
            ]
        ];
    }

    private function initializeComponents(): void {
        // Initialize Models
        Models\Menu::init();
        Models\Order::init();
        Models\Settings::init();
        
        // Initialize Components based on context
        if (is_admin()) {
            $this->initAdminComponents();
        }
        $this->initFrontendComponents();
        $this->registerAjaxHandlers();
    }

    private function initAdminComponents(): void {
        Admin\MenuController::init();
        Admin\OrderController::init();
        Admin\SettingsController::init();
    }

    private function initFrontendComponents(): void {
        Frontend\ShortcodeController::init();
    }

    private function registerAjaxHandlers(): void {
        $this->registerPublicAjaxHandlers();
        if (is_admin()) {
            $this->registerAdminAjaxHandlers();
        }
    }

    private function registerPublicAjaxHandlers(): void {
        add_action('wp_ajax_submit_order', [Admin\OrderController::class, 'handleOrder']);
        add_action('wp_ajax_nopriv_submit_order', [Admin\OrderController::class, 'handleOrder']);
    }

    private function registerAdminAjaxHandlers(): void {
        $admin_handlers = [
            'save_menu_order' => [Admin\MenuController::class, 'handleSaveMenuOrder'],
            'copy_menu' => [Admin\MenuController::class, 'handleCopyMenu'],
            'print_order' => [Admin\OrderController::class, 'handlePrintOrder'],
            'delete_order' => [Admin\OrderController::class, 'handleDeleteOrder']
        ];

        foreach ($admin_handlers as $action => $callback) {
            add_action('wp_ajax_' . $action, $callback);
        }
    }

    // Function is a duplication of the checkForUpdates function in the Bootstrap class
    private function checkForUpdates(): void {
        $installed_version = get_option('daily_menu_manager_version');
        
        if ($installed_version !== DMM_VERSION) {
            try {
                $migration_manager = new Database\MigrationManager();
                $migration_manager->runMigrations();
                //update_option('daily_menu_manager_version', DMM_VERSION);
                self::addAdminNotice(
                    sprintf(__('Daily Menu Manager updated to version %s', 'daily-menu-manager'), DMM_VERSION),
                    'success'
                );
            } catch (\Exception $e) {
                self::log('Update failed: ' . $e->getMessage());
                self::addAdminNotice(
                    __('Update failed. Please check the error log.', 'daily-menu-manager'),
                    'error'
                );
            }
        }
    }

    public function getMenuTypes(): array {
        return apply_filters('daily_menu_manager_menu_types', $this->menu_types);
    }

    public static function addAdminNotice(string $message, string $type = 'success'): void {
        $notices = get_option('daily_menu_manager_notices', []);
        $notices[] = compact('message', 'type');
        update_option('daily_menu_manager_notices', $notices);
    }

    public static function log(string $message, $data = null): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Daily Menu Manager: ' . $message);
            if ($data !== null) {
                error_log(print_r($data, true));
            }
        }
    }

    // Prevent cloning of singleton
    private function __clone() {}
}