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
            __('Bestellungen', 'daily-menu-manager'),
            __('Bestellungen', 'daily-menu-manager'),
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
                'deleteConfirm' => __('Möchten Sie diese Bestellung wirklich löschen?', 'daily-menu-manager'),
                'deleteSuccess' => __('Bestellung wurde gelöscht.', 'daily-menu-manager'),
                'deleteError' => __('Fehler beim Löschen der Bestellung.', 'daily-menu-manager')
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
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'daily-menu-manager')]);
        }

        $order_number = sanitize_text_field($_POST['order_number']);
        if (empty($order_number)) {
            wp_send_json_error(['message' => __('Keine Bestellnummer angegeben.', 'daily-menu-manager')]);
        }

        $order_model = new Order();
        $order = $order_model->getOrderByNumber($order_number);

        if (empty($order)) {
            wp_send_json_error(['message' => __('Bestellung nicht gefunden.', 'daily-menu-manager')]);
        }

        // Generiere HTML für den Druck
        ob_start();
        require DMM_PLUGIN_DIR . 'includes/Views/print-order.php';
        $print_html = ob_get_clean();

        wp_send_json_success(['html' => $print_html]);
    }

    /**
     * AJAX Handler für das Löschen von Bestellungen
     */
    public static function handleDeleteOrder() {
        check_ajax_referer('daily_menu_orders_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'daily-menu-manager')]);
        }

        $order_number = sanitize_text_field($_POST['order_number']);
        if (empty($order_number)) {
            wp_send_json_error(['message' => __('Keine Bestellnummer angegeben.', 'daily-menu-manager')]);
        }

        $order_model = new Order();
        $result = $order_model->deleteOrder($order_number);

        if ($result) {
            wp_send_json_success(['message' => __('Bestellung wurde gelöscht.', 'daily-menu-manager')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Löschen der Bestellung.', 'daily-menu-manager')]);
        }
    }

    /**
     * Verarbeitet neue Bestellungen aus dem Frontend
     */
    public static function handleOrder() {
        check_ajax_referer('daily_menu_nonce');
        
        if (empty($_POST['items'])) {
            wp_send_json_error([
                'message' => __('Keine Gerichte ausgewählt.', 'daily-menu-manager')
            ]);
        }

        // Validiere Pflichtfelder
        $required_fields = [
            'customer_name' => __('Bitte geben Sie Ihren Namen an.', 'daily-menu-manager'),
            'customer_phone' => __('Bitte geben Sie Ihre Telefonnummer an.', 'daily-menu-manager'),
            'pickup_time' => __('Bitte wählen Sie eine Abholzeit.', 'daily-menu-manager')
        ];

        foreach ($required_fields as $field => $message) {
            if (empty($_POST[$field])) {
                wp_send_json_error([
                    'message' => $message
                ]);
            }
        }

        // Validiere Abholzeit-Format und Bereich
        $pickup_time = sanitize_text_field($_POST['pickup_time']);
        if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $pickup_time)) {
            wp_send_json_error([
                'message' => __('Ungültige Abholzeit.', 'daily-menu-manager')
            ]);
        }

        // Prüfe ob die Zeit zwischen 11:00 und 16:00 liegt
        $time_value = strtotime($pickup_time);
        $min_time = strtotime('11:00');
        $max_time = strtotime('16:00');
        
        if ($time_value < $min_time || $time_value > $max_time) {
            wp_send_json_error([
                'message' => __('Die Abholzeit muss zwischen 11:00 und 16:00 Uhr liegen.', 'daily-menu-manager')
            ]);
        }

        $order_model = new Order();
        $result = $order_model->createOrder($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        } else {
            $menu = new \DailyMenuManager\Models\Menu();
            $update = $menu->updateAvailableQuantities($_POST['items']);
            if (is_wp_error($update)) {
                wp_send_json_error([
                    'message' => __('Fehler beim Aktualisieren der verfügbaren Mengen: ', 'daily-menu-manager') . $update->get_error_message()
                ]);
            }
            wp_send_json_success($result);
        }
    }
}