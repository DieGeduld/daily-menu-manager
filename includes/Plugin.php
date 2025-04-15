<?php
namespace DailyMenuManager;

use DailyMenuManager\Admin\MenuController;
use DailyMenuManager\Admin\OrderController;
use DailyMenuManager\Admin\SettingsController;
use DailyMenuManager\Frontend\ShortcodeController;

class Plugin {
    private static ?self $instance = null;
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
        $this->initializeComponents();
        $this->checkForUpdates();
        $this->initialized = true;
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
        add_action('wp_ajax_nopriv_submit_order', [Admin\OrderController::class, 'handleOrder']);
        add_action('wp_ajax_nopriv_get_available_quantities', [Admin\SettingsController::class, 'getAvailableQuantities']);        

        add_action('wp_ajax_submit_order', [Admin\OrderController::class, 'handleOrder']);
        add_action('wp_ajax_get_available_quantities', [Admin\SettingsController::class, 'getAvailableQuantities']);
    }

    private function registerAdminAjaxHandlers(): void {
        
        $admin_handlers = [
            'delete_menu_item' => [MenuController::class, 'handleDeleteMenuItem'],
            'save_menu_order' => [MenuController::class, 'handleSaveMenuOrder'],
            'get_menu_data' => [MenuController::class, 'handleGetMenuData'],
            'save_menu_data'=> [MenuController::class, 'handleSaveMenuData'],
            'copy_menu' => [MenuController::class, 'handleCopyMenu'],
            'print_order' => [OrderController::class, 'handlePrintOrder'],
            'delete_order' => [OrderController::class, 'handleDeleteOrder'],
        ];

        foreach ($admin_handlers as $action => $callback) {
            add_action('wp_ajax_' . $action, $callback);
        }
    }

    private function checkForUpdates(): void {
        $installed_version = get_option('daily_menu_manager_version');
        
        if ($installed_version !== DMM_VERSION) {
            self::addAdminNotice(
                sprintf(
                    __('Daily Menu Manager needs database update from version %s to %s. Please visit the settings page to run the update.', 'daily-menu-manager'),
                    $installed_version,
                    DMM_VERSION
                ),
                'warning'
            );
        }
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