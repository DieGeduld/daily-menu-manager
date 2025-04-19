<?php

namespace DailyMenuManager\Controller\Common;

use DailyMenuManager\Controller\Admin\MenuController;
use DailyMenuManager\Controller\Admin\OrderController;

class AjaxController
{
    public static function init(): void
    {
        self::registerPublicAjaxHandlers();
        if (is_admin()) {
            self::registerAdminAjaxHandlers();
        }
    }

    public static function registerPublicAjaxHandlers(): void
    {
        // Frontend AJAX Handlers (für alle Benutzer)
        add_action('wp_ajax_nopriv_submit_order', [OrderController::class, 'handleOrder']);
        add_action('wp_ajax_nopriv_get_available_quantities', [MenuController::class, 'getAvailableQuantities']);
        add_action('wp_ajax_nopriv_get_current_menu', [MenuController::class, 'handleGetCurrentMenu']);

        // Frontend AJAX Handlers (für eingeloggte Benutzer)
        add_action('wp_ajax_submit_order', [OrderController::class, 'handleOrder']);
        add_action('wp_ajax_get_available_quantities', [MenuController::class, 'getAvailableQuantities']);
        add_action('wp_ajax_get_current_menu', [MenuController::class, 'handleGetCurrentMenu']);
    }

    public static function registerAdminAjaxHandlers(): void
    {
        $admin_handlers = [
            'duplicate_menu_item' => [MenuController::class, 'handleDuplicateMenuItem'],
            'delete_menu_item' => [MenuController::class, 'handleDeleteMenuItem'],
            'save_menu_order' => [MenuController::class, 'handleSaveMenuOrder'],
            'get_menu_data' => [MenuController::class, 'handleGetMenuData'],
            'save_menu_data' => [MenuController::class, 'handleSaveMenuData'],
            'copy_menu' => [MenuController::class, 'handleCopyMenu'],
            'print_order' => [OrderController::class, 'handlePrintOrder'],
            'delete_order' => [OrderController::class, 'handleDeleteOrder'],
        ];

        foreach ($admin_handlers as $action => $callback) {
            add_action('wp_ajax_' . $action, $callback);
        }
    }
}
