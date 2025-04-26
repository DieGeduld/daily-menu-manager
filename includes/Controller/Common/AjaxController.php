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

        add_action('wp_enqueue_scripts', [self::class, 'enqueue_daily_dish_manager_scripts']);
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

    public static function enqueue_daily_dish_manager_scripts()
    {
        $is_dev = false;

        // Prüfen, ob der Vite-Devserver läuft
        $handle = @fsockopen('localhost', 5173);
        if ($handle) {
            $is_dev = true;
            fclose($handle);
        }

        if ($is_dev) {
            add_filter('script_loader_tag', function ($tag, $handle, $src) {
                if ($handle === 'daily-menu-manager') {
                    return '<script type="module" src="' . esc_url($src) . '"></script>';
                }

                return $tag;
            }, 10, 3);

            // Entwicklung: Lade direkt vom Vite-Server
            wp_enqueue_script(
                'daily-menu-manager',
                'http://localhost:5173/src/js/frontend/frontend.js', // <<< ganz wichtig: genau der Pfad!
                [],
                null,
                true
            );
        } else {
            // Produktion: Lade aus dem gebauten Dist-Ordner
            wp_enqueue_script(
                'daily-menu-manager',
                get_stylesheet_directory_uri() . '/wp-content/daily-menu-manager/dist/frontend.js', // <<< Dein Build-Output
                [],
                '1.0.0',
                true
            );
        }
    }
}
