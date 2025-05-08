<?php

namespace DailyMenuManager\Controller\Common;

use DailyMenuManager\Controller\Admin\SettingsController;
use DailyMenuManager\Model\Menu;
use DailyMenuManager\Service\MenuService;

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

        // Tailwind CSS
        // wp_enqueue_style(
        //     'tailwind',
        //     'https://unpkg.com/tailwindcss@^3/dist/tailwind.min.css',
        //     [],
        //     '3'
        // );

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

        $is_dev = false;

        // Prüfen, ob der Vite-Devserver läuft
        $handle = @fsockopen('localhost', 5173);
        if ($handle) {
            $is_dev = true;
            fclose($handle);
        }

        if ($is_dev) {
            add_filter('script_loader_tag', function ($tag, $handle, $src) {
                if ($handle === 'daily-dish-manager') {
                    return '<script type="module" src="' . esc_url($src) . '"></script>';
                }

                return $tag;
            }, 10, 3);

            // Entwicklung: Lade direkt vom Vite-Server
            wp_enqueue_script(
                'daily-dish-manager',
                'http://localhost:5173/src/js/frontend/frontend.js', // <<< ganz wichtig: genau der Pfad!
                [],
                null,
                true
            );
        } else {
            // Produktion: Lade aus dem gebauten Dist-Ordner

            wp_register_script_module(
                'daily-menu-frontend-module',
                DMM_PLUGIN_URL . 'dist/frontend.js',
                [],
                DMM_VERSION
            );
        }

        wp_script_add_data('daily-menu-frontend', 'type', 'module');

        wp_enqueue_script_module('daily-menu-frontend-module');

        add_action('wp_head', function () {
?>
            <script>
                window.dailyMenuAjax = <?php echo json_encode([
                                            'ajaxurl' => admin_url('admin-ajax.php'),
                                            'nonce' => wp_create_nonce('daily_dish_manager_nonce'),
                                            'messages' => [
                                                'orderSuccess' => __('Your order has been successfully placed!', DMM_TEXT_DOMAIN),
                                                'orderError' => __('There was an error placing your order. Please try again.', DMM_TEXT_DOMAIN),
                                                'emptyOrder' => __('Please select at least one dish.', DMM_TEXT_DOMAIN),
                                                'requiredFields' => __('Please fill out all required fields.', DMM_TEXT_DOMAIN),
                                            ],
                                            'timeFormat' => SettingsController::getTimeFormat(),
                                            'dateFormat' => SettingsController::getDateFormat(),
                                            'priceFormat' => SettingsController::getPriceFormat(),
                                            'currencySymbol' => SettingsController::getCurrencySymbol(),
                                            'pickupTimes' => SettingsController::getAvailablePickupTimes(),
                                            'translations' => [
                                                'notes' => __('Notes for this item', DMM_TEXT_DOMAIN),
                                                'available' => __('available', DMM_TEXT_DOMAIN),
                                                'total' => __('Total', DMM_TEXT_DOMAIN),
                                                'yourData' => __('Your Information', DMM_TEXT_DOMAIN),
                                                'submit' => __('Place Order', DMM_TEXT_DOMAIN),
                                                'submitting' => __('Submitting...', DMM_TEXT_DOMAIN),
                                                'name' => __('Name', DMM_TEXT_DOMAIN),
                                                'phone' => __('Phone', DMM_TEXT_DOMAIN),
                                                'pickupTime' => __('Pickup Time', DMM_TEXT_DOMAIN),
                                                'selectTime' => __('Select time', DMM_TEXT_DOMAIN),
                                                'additionalNotes' => __('Additional Notes', DMM_TEXT_DOMAIN),
                                                'orderSummary' => __('Order Summary', DMM_TEXT_DOMAIN),
                                                'noItems' => __('No items selected', DMM_TEXT_DOMAIN),
                                                'loading' => __('Loading menu...', DMM_TEXT_DOMAIN),
                                                'close' => __('Close', DMM_TEXT_DOMAIN),
                                                'orderNumber' => __('Order Number', DMM_TEXT_DOMAIN),
                                                'pickupInstructions' => __('Please mention this number when picking up.', DMM_TEXT_DOMAIN),
                                                'phoneNumber' => __('Phone Number', DMM_TEXT_DOMAIN),
                                                'orderDetails' => __('Order Details', DMM_TEXT_DOMAIN),
                                                'forPossibleInquiries' => __('(for possible inquiries)', DMM_TEXT_DOMAIN),
                                                'pickup_or_eat_in' => __('Pick up or eat in', DMM_TEXT_DOMAIN),
                                                'soldout' => __('Sold out', DMM_TEXT_DOMAIN),
                                            ],
                                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            </script>
<?php
        });
    }

    public static function enqueueAdminAssets($hook)
    {
        // TODO: Check
        if ('daily-menu_page_daily-menu-orders' !== $hook && 'toplevel_page_daily-dish-manager' !== $hook && "tagesmenue_page_daily-dish-manager-settings" != $hook) {
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
                'nonce' => wp_create_nonce('daily_dish_manager_admin_nonce'),
                'messages' => [
                    'copySuccess' => __('Menu was copied successfully!', DMM_TEXT_DOMAIN),
                    'copyError' => __('Error copying menu.', DMM_TEXT_DOMAIN),
                    'saveSuccess' => __('Menu was saved!', DMM_TEXT_DOMAIN),
                    'saveError' => __('Error saving menu.', DMM_TEXT_DOMAIN),
                    'deleteConfirm' => __('Are you sure you want to delete this menu item?', DMM_TEXT_DOMAIN),
                    'duplicateSuccess' => __('Menu item was duplicated successfully!', DMM_TEXT_DOMAIN),
                    'duplicateError' => __('Error duplicating menu item.', DMM_TEXT_DOMAIN),
                    'selectDate' => __('Please select a date.', DMM_TEXT_DOMAIN),
                    'noItems' => __('Please add at least one menu item.', DMM_TEXT_DOMAIN),
                    'requiredFields' => __('Please fill in all required fields.', DMM_TEXT_DOMAIN),
                    'copy' => __('Copy', DMM_TEXT_DOMAIN),
                    'cancel' => __('Cancel', DMM_TEXT_DOMAIN),
                ],
                'menus' => MenuService::getMenuDates(),
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
