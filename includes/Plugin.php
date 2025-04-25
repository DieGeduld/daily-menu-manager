<?php

namespace DailyMenuManager;

use DailyMenuManager\Controller\Admin\MenuController;
use DailyMenuManager\Controller\Admin\OrderController;
use DailyMenuManager\Controller\Admin\SettingsController;
use DailyMenuManager\Controller\Common\AjaxController;
use DailyMenuManager\Controller\Common\AssetController;
use DailyMenuManager\Controller\Frontend\ShortcodeController;
use DailyMenuManager\Model\Menu;
use DailyMenuManager\Model\Order;
use DailyMenuManager\Model\Settings;

class Plugin
{
    private static ?self $instance = null;
    private bool $initialized = false;

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        if (!$this->initialized) {
            $this->initializePlugin();
        }
    }

    private function initializePlugin(): void
    {
        $this->initializeComponents();
        $this->initialized = true;
    }

    private function initializeComponents(): void
    {
        // Initialize Models
        Menu::init();
        Order::init();
        Settings::init();
        // Initialize Admin and Frontend components
        AjaxController::init();
        AssetController::init();
        MenuController::init();
        OrderController::init();
        SettingsController::init();

        // Initialize Components based on context
        if (is_admin()) {
            //TODO: Initialize admin-specific components
        }
        $this->initAdminComponents();
        $this->initFrontendComponents();
    }

    private function initAdminComponents(): void
    {
    }

    private function initFrontendComponents(): void
    {
        ShortcodeController::init();
    }

    public static function addAdminNotice(string $message, string $type = 'success'): void
    {
        $notices = get_option('daily_menu_manager_notices', []);
        $notices[] = compact('message', 'type');
        update_option('daily_menu_manager_notices', $notices);
    }

    public static function log(string $message, $data = null): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Daily Menu Manager: ' . $message);
            if ($data !== null) {
                error_log(print_r($data, true));
            }
        }
    }

    // Prevent cloning of singleton
    private function __clone()
    {
    }
}
