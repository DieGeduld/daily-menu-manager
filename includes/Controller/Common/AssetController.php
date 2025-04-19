<?php

namespace DailyMenuManager\Controller\Common;

use DailyMenuManager\Controller\Admin\SettingsController;
use DailyMenuManager\Model\Menu;

class AssetController
{
    public static function init()
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
    }

    /**
     * Lädt die benötigten CSS und JavaScript Dateien
     */
    public static function enqueueFrontendAssets()
    {
        // CSS laden
        wp_enqueue_style(
            'daily-menu-frontend',
            DMM_PLUGIN_URL . 'dist/frontend.css',
            [],
            DMM_VERSION
        );

        // Bootstrap CSS (falls benötigt)
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );

        // SweetAlert2 CSS
        wp_enqueue_style(
            'sweetalert2',
            'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.min.css',
            [],
            '11.10.5'
        );

        // SweetAlert2 JS
        wp_register_script(
            'sweetalert2',
            'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.all.min.js',
            [],
            '11.10.5',
            true
        );
        wp_enqueue_script('sweetalert2');

        // Vue.js Frontend App
        wp_register_script_module(
            'daily-menu-frontend-module',
            DMM_PLUGIN_URL . 'dist/frontend.js',
            [],
            DMM_VERSION
        );

        wp_script_add_data('daily-menu-frontend', 'type', 'module');

        wp_enqueue_script_module('daily-menu-frontend-module');

        add_action('wp_head', function () {
            ?>
            <script>
                window.dailyMenuAjax = <?php echo json_encode([
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('daily_menu_manager_nonce'),
                    'messages' => [
                        'orderSuccess' => __('Your order has been successfully placed!', 'daily-menu-manager'),
                        'orderError' => __('There was an error placing your order. Please try again.', 'daily-menu-manager'),
                        'emptyOrder' => __('Please select at least one dish.', 'daily-menu-manager'),
                        'requiredFields' => __('Please fill out all required fields.', 'daily-menu-manager'),
                    ],
                    'timeFormat' => SettingsController::getTimeFormat(),
                    'dateFormat' => SettingsController::getDateFormat(),
                    'priceFormat' => SettingsController::getPriceFormat(),
                    'currencySymbol' => SettingsController::getCurrencySymbol(),
                    'pickupTimes' => SettingsController::getAvailablePickupTimes(),
                    'translations' => [
                        'notes' => __('Notes for this item', 'daily-menu-manager'),
                        'available' => __('available', 'daily-menu-manager'),
                        'orderTotal' => __('Order Total', 'daily-menu-manager'),
                        'yourData' => __('Your Information', 'daily-menu-manager'),
                        'submit' => __('Place Order', 'daily-menu-manager'),
                        'submitting' => __('Submitting...', 'daily-menu-manager'),
                        'name' => __('Name', 'daily-menu-manager'),
                        'phone' => __('Phone', 'daily-menu-manager'),
                        'pickupTime' => __('Pickup Time', 'daily-menu-manager'),
                        'selectTime' => __('Select time', 'daily-menu-manager'),
                        'additionalNotes' => __('Additional Notes', 'daily-menu-manager'),
                        'orderSummary' => __('Order Summary', 'daily-menu-manager'),
                        'noItems' => __('No items selected', 'daily-menu-manager'),
                        'loading' => __('Loading menu...', 'daily-menu-manager'),
                        'close' => __('Close', 'daily-menu-manager'),
                        'orderNumber' => __('Order Number', 'daily-menu-manager'),
                        'pickupInstructions' => __('Please mention this number when picking up.', 'daily-menu-manager'),
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            </script>
            <?php
        });

    }

    public static function enqueueAdminAssets($hook)
    {
        // TODO: Check
        if ('daily-menu_page_daily-menu-orders' !== $hook && 'toplevel_page_daily-menu-manager' !== $hook && "tagesmenue_page_daily-menu-manager-settings" != $hook) {
            return;
        }

        // jQuery UI Components
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-tabs');

        // jQuery UI Styles
        wp_enqueue_style(
            'jquery-ui-style',
            '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css'
        );

        wp_enqueue_style(
            'jquery-ui-styles',
            'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
            [],
            '1.12.1'
        );

        // Plugin Admin Scripts
        wp_enqueue_script(
            'daily-menu-admin',
            DMM_PLUGIN_URL . '/assets/js/admin.js', //TODO: Longrun: dist/admin.min.js
            ['jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-dialog', 'jquery-ui-tabs'],
            DMM_VERSION,
            true
        );

        // Notyf CSS
        wp_enqueue_style(
            'notyf',
            'https://cdn.jsdelivr.net/npm/notyf@3.10.0/notyf.min.css',
            [],
            '3.10.0'
        );

        // SweetModal CSS
        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11.4.10/dist/sweetalert2.min.css',
            [],
            '11.4.10'
        );

        // SweetModal JS
        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11.4.10/dist/sweetalert2.min.js',
            [],
            '11.4.10',
            true
        );

        // Notyf JS
        wp_enqueue_script(
            'notyf',
            'https://cdn.jsdelivr.net/npm/notyf@3.10.0/notyf.min.js',
            [],
            '3.10.0',
            true
        );

        // Admin Styles
        wp_enqueue_style(
            'daily-menu-admin-style',
            DMM_PLUGIN_URL . 'dist/admin.css', //TODO: min
            [],
            DMM_VERSION
        );

        // Flatpickr CSS
        wp_enqueue_style(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );

        // Flatpickr JS
        wp_enqueue_script(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );

        // Lokalisierung - WICHTIG: Muss nach dem Enqueue des Scripts erfolgen
        wp_localize_script(
            'daily-menu-admin',
            'dailyMenuAdmin',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('daily_menu_admin_nonce'),
                'messages' => [
                    'copySuccess' => __('Menu was copied successfully!', 'daily-menu-manager'),
                    'copyError' => __('Error copying menu.', 'daily-menu-manager'),
                    'saveSuccess' => __('Menu was saved!', 'daily-menu-manager'),
                    'saveError' => __('Error saving menu.', 'daily-menu-manager'),
                    'deleteConfirm' => __('Are you sure you want to delete this menu item?', 'daily-menu-manager'),
                    'duplicateSuccess' => __('Menu item was duplicated successfully!', 'daily-menu-manager'),
                    'duplicateError' => __('Error duplicating menu item.', 'daily-menu-manager'),
                    'selectDate' => __('Please select a date.', 'daily-menu-manager'),
                    'noItems' => __('Please add at least one menu item.', 'daily-menu-manager'),
                    'requiredFields' => __('Please fill in all required fields.', 'daily-menu-manager'),
                    'copy' => __('Copy', 'daily-menu-manager'),
                    'cancel' => __('Cancel', 'daily-menu-manager'),
                ],
                'menus' => Menu::getMenuDates(),
                'timeFormat' => SettingsController::getTimeFormat(),
                'dateFormat' => SettingsController::getDateFormat(),
                'priceFormat' => SettingsController::getPriceFormat(),
                'currencySymbol' => SettingsController::getCurrencySymbol(),
                'locale' => get_locale(),
                'menuTypes' => SettingsController::getMenuTypes(true),
                'orderTimes' => SettingsController::getOrderTimes(),
            ]
        );
    }
}
