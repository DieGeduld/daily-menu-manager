<?php
namespace DailyMenuManager\Admin;

use DailyMenuManager\Models\Order;

class OrderStatistics {
    public $total_orders = 0;
    public $total_revenue = 0;
    public $total_items = 0;

    public function __construct($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

class OrderController {
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        add_action('admin_menu', [self::class, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminScripts']);
        add_action('wp_ajax_print_order', [self::class, 'handlePrintOrder']);
        add_action('wp_ajax_delete_order', [self::class, 'handleDeleteOrder']);
    }

    /**
     * Fügt den Bestellungen-Menüpunkt hinzu
     */
    public static function addAdminMenu() {
        add_submenu_page(
            'daily-menu-manager',
            __('Orders', 'daily-menu-manager'),
            __('Orders', 'daily-menu-manager'),
            'manage_options',
            'daily-menu-orders',
            [self::class, 'displayOrdersPage']
        );
    }

    /**
     * Lädt die benötigten Admin-Scripts
     */
    public static function enqueueAdminScripts($hook) {
        if ('daily-menu_page_daily-menu-orders' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'daily-menu-admin-orders',
            plugins_url('assets/css/admin-orders.css', dirname(__DIR__)),
            [],
            DMM_VERSION
        );

        wp_enqueue_script(
            'daily-menu-admin-orders',
            plugins_url('assets/js/admin-orders.js', dirname(__DIR__)),
            ['jquery'],
            DMM_VERSION,
            true
        );

        wp_localize_script('daily-menu-admin-orders', 'dailyMenuOrders', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('daily_menu_orders_nonce'),
            'messages' => [
                'deleteConfirm' => __('Are you sure you want to delete this order?', 'daily-menu-manager'),
                'deleteSuccess' => __('Order has been deleted.', 'daily-menu-manager'),
                'deleteError' => __('Error deleting the order.', 'daily-menu-manager')
            ]
        ]);
    }

    /**
     * Zeigt die Bestellübersicht an
     */
    public static function displayOrdersPage() {
        $order_model = new \DailyMenuManager\Models\Order();
        
        // Filter-Logik
        $filters = [
            'date' => isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : current_time('Y-m-d'),
            'order_number' => isset($_GET['filter_order']) ? sanitize_text_field($_GET['filter_order']) : '',
            'customer_name' => isset($_GET['filter_name']) ? sanitize_text_field($_GET['filter_name']) : '',
            'customer_phone' => isset($_GET['filter_phone']) ? sanitize_text_field($_GET['filter_phone']) : ''
        ];

        // Hole Bestellungen mit Filtern
        $orders = $order_model->getOrders($filters);
        
        // Statistiken initialisieren
        $stats = [
            'total_orders' => 0,
            'total_revenue' => 0,
            'total_items' => 0
        ];
        
        // Berechne die Statistiken
        if (!empty($orders)) {
            $counted_orders = [];
            $total_revenue = 0;
            $total_items = 0;
            
            foreach ($orders as $order) {
                if (!isset($counted_orders[$order->order_number])) {
                    $counted_orders[$order->order_number] = true;
                }
                $total_revenue += ($order->price * $order->quantity);
                $total_items += $order->quantity;
            }
            
            $stats['total_orders'] = count($counted_orders);
            $stats['total_revenue'] = $total_revenue;
            $stats['total_items'] = $total_items;
        }
        
        // Template laden
        require_once DMM_PLUGIN_DIR . 'includes/Views/admin-orders-page.php';
    }

    /**
     * AJAX Handler für den Bestellungsdruck
     */
    public static function handlePrintOrder() {
        check_ajax_referer('daily_menu_orders_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $order_number = sanitize_text_field($_POST['order_number']);
        if (empty($order_number)) {
            wp_send_json_error(['message' => __('No order number provided.', 'daily-menu-manager')]);
        }

        $order_model = new Order();
        $order = $order_model->getOrderByNumber($order_number);

        if (empty($order)) {
            wp_send_json_error(['message' => __('Order not found.', 'daily-menu-manager')]);
        }

        // Generate HTML for printing
        ob_start();
        require DMM_PLUGIN_DIR . 'includes/Views/print-order.php';
        $print_html = ob_get_clean();

        wp_send_json_success(['html' => $print_html]);
    }

    /**
     * AJAX Handler for deleting orders
     */
    public static function handleDeleteOrder() {
        check_ajax_referer('daily_menu_orders_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $order_number = sanitize_text_field($_POST['order_number']);
        if (empty($order_number)) {
            wp_send_json_error(['message' => __('No order number provided.', 'daily-menu-manager')]);
        }

        $order_model = new Order();
        $result = $order_model->deleteOrder($order_number);

        if ($result) {
            wp_send_json_success(['message' => __('Order deleted successfully.', 'daily-menu-manager')]);
        } else {
            wp_send_json_error(['message' => __('Error deleting order.', 'daily-menu-manager')]);
        }
    }
}
