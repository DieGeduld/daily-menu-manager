<?php

namespace DailyMenuManager\Controller\Admin;

use DailyMenuManager\Entity\Order;
use DailyMenuManager\Repository\MenuItemRepository;
use DailyMenuManager\Repository\OrderItemRepository;
use DailyMenuManager\Repository\OrderRepository;
use DailyMenuManager\Service\OrderService;

class OrderController
{
    private static $instance = null;

    public static function init()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        add_action('admin_menu', [self::class, 'addAdminMenu']);
    }

    /**
     * Fügt den Bestellungen-Menüpunkt hinzu
     */
    public static function addAdminMenu()
    {
        add_submenu_page(
            'daily-dish-manager',
            __('Orders', DMM_TEXT_DOMAIN),
            __('Orders', DMM_TEXT_DOMAIN),
            'manage_options',
            'daily-dish-manager-orders',
            [self::class, 'displayOrdersPage']
        );
    }

    /**
     * Zeigt die Bestellübersicht an
     */
    public static function displayOrdersPage(): void
    {
        $order = new Order();

        // Filter-Logik
        $filters = [
            'date' => isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : current_time('Y-m-d'),
            'order_number' => isset($_GET['filter_order']) ? sanitize_text_field($_GET['filter_order']) : '',
            'customer_name' => isset($_GET['filter_name']) ? sanitize_text_field($_GET['filter_name']) : '',
            'customer_phone' => isset($_GET['filter_phone']) ? sanitize_text_field($_GET['filter_phone']) : '',
        ];

        // Hole Bestellungen mit Filtern
        $orders = $order->getOrders($filters);

        // Statistiken initialisieren
        $stats = [
            'total_orders' => 0,
            'total_revenue' => 0,
            'total_items' => 0,
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
    public static function handlePrintOrder()
    {
        check_ajax_referer('daily_dish_orders_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', DMM_TEXT_DOMAIN)]);
        }

        $order_number = sanitize_text_field($_POST['order_number']);
        if (empty($order_number)) {
            wp_send_json_error(['message' => __('No order number provided.', DMM_TEXT_DOMAIN)]);
        }

        $order_model = new Order();
        $order = $order_model->getOrderByNumber($order_number);

        if (empty($order)) {
            wp_send_json_error(['message' => __('Order not found.', DMM_TEXT_DOMAIN)]);
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
    public static function handleDeleteOrder()
    {
        check_ajax_referer('daily_dish_orders_nonce'); //TODO: Only for admin

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', DMM_TEXT_DOMAIN)]);
        }

        $order_number = sanitize_text_field($_POST['order_number']);
        if (empty($order_number)) {
            wp_send_json_error(['message' => __('No order number provided.', DMM_TEXT_DOMAIN)]);
        }

        $order_model = new Order();
        $result = $order_model->deleteOrder($order_number);

        if ($result) {
            wp_send_json_success(['message' => __('Order deleted successfully.', DMM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error deleting order.', DMM_TEXT_DOMAIN)]);
        }
    }

    /**
     * Handle order submission from the frontend
     */
    public static function handleOrder(): void
    {
        check_ajax_referer('daily_dish_manager_nonce', 'nonce');

        $response = [
            'success' => false,
            'message' => 'Error processing order',
        ];

        try {
            // Get POST data
            // todo: sanitize_text_field?
            $data = $_POST;

            // Validate required data
            if (empty($data['items']) || empty($data['customerInfo'])) {
                throw new \Exception('Incomplete order data');
            }

            if (intval($data["menuId"]) !== self::getCurrentMenuId()) {
                throw new \Exception('Wrong Date');
            }

            // Parse JSON data
            $items = is_string($data['items']) ? json_decode(stripslashes($data['items']), true) : $data['items'];
            $customerInfo = is_string($data['customerInfo']) ? json_decode(stripslashes($data['customerInfo']), true) : $data['customerInfo'];

            // Validate customer info
            if (!$customerInfo['name'] || !$customerInfo['phone'] || !$customerInfo['pickupTime']) {
                throw new \Exception('Missing required customer information');
            }

            // Validate items
            if (!is_array($items) || count($items) === 0) {
                throw new \Exception('No items in order');
            }

            // Calculate and check total amount
            $totalAmount = 0;
            $sendTotalAmount = $data["totalPrice"];
            foreach ($items as $item) {
                if (!isset($item['price']) || !isset($item['quantity'])) {
                    throw new \Exception('Invalid item data');
                }
                $totalAmount += floatval($item['price']) * intval($item['quantity']);
            }

            if (floatval($totalAmount) !== floatval($sendTotalAmount)) {
                throw new \Exception('Total amount mismatch');
            }

            $menuId = self::getCurrentMenuId();

            /* check if order items have only items from today */
            foreach ($items as $item) {
                if (self::getMenuIdFromMenuItemId(intval($item["menuItemId"])) !== $menuId) {
                    throw new \Exception('Wrong Date');
                }
            }

            // Create order in database
            $orderData = [
                'menu_id' => $menuId,
                'customer_name' => sanitize_text_field($customerInfo['name']),
                'customer_phone' => sanitize_text_field($customerInfo['phone']),
                'consumption_type' => sanitize_text_field($customerInfo['consumptionType']),
                'pickup_time' => sanitize_text_field($customerInfo['pickupTime']),
                'customer_notes' => sanitize_textarea_field($customerInfo['notes'] ?? ''),
                'items' => $items,
                'total_amount' => $totalAmount,
                'order_date' => current_time('mysql'),
                'status' => 'pending',
            ];

            // Save order to database using OrderModel
            $orderRepository = new OrderRepository();
            $orderService = new OrderService($orderRepository);
            $order = $orderService->createOrder($orderData);
            $orderId = $order["orders"][0]->getId();

            if (!$orderId) {
                throw new \Exception('Failed to save order');
            }

            // Save order items with correct menu IDs
            foreach ($items as $item) {
                self::saveOrderItem($orderId, $item, $menuId);
                self::updateItemStock($item['id'], $item['quantity']); // id ?
            }

            // Prepare success response
            $response = [
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order_id' => $orderId,
                    'order_number' => $order["orders"][0]->getOrdernumber(),
                    'items' => $items,
                    'total_amount' => $totalAmount,
                    'pickup_time' => $customerInfo['pickupTime'],
                ],
            ];

            // Optional: Send confirmation email
            self::sendOrderConfirmationEmail($orderData);
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];

            // Log error for debugging
            error_log('Order submission error: ' . $e->getMessage());
        }

        // Return JSON response
        wp_send_json($response);
        exit;
    }

    /**
     * Save a single order item with correct IDs
     */
    private static function saveOrderItem($orderId, $item, $menuId): void
    {
        // Get menu_item_id from item or use default
        $menuItemId = intval($item['menuItemId']);

        if (!$menuItemId) {
            // throw new \Exception('Menu item ID not found');
            return;
        }

        $price = isset($item['price']) ? floatval($item['price']) : false;

        if (!$price) {
            // throw new \Exception('Price not found');
            return;
        }
        //TODO: Preis checken, ob er richtig ist, man könnte einen anderen abschicke

        $menuItemRepository = new MenuItemRepository();
        $menuItem = $menuItemRepository->findById($menuItemId);

        if (!$menuItem) {
            // throw new \Exception('Order item not found');
            return;
        } else {
            if ($menuItem->getPrice() != $price) {
                // throw new \Exception('Price mismatch');
                return;
            }
        }

        $menuItemId = intval($menuItemId);

        if (!$menuItemId) {
            // throw new \Exception('Menu item ID not found');
            return;
        }

        $quantity = isset($item['quantity']) ? intval($item['quantity']) : false;

        if (!$quantity) {
            // throw new \Exception('Quantity not found');
            return;
        }

        // Get item-specific menu ID if available
        $menuId = isset($item['menuId']) ? intval($item['menuId']) : false;

        if (!$menuId) {
            // throw new \Exception('Menu ID not found');
            return;
        }

        $orderItemData = [
            'order_id' => $orderId,
            'menu_item_id' => $menuItemId,
            'quantity' => $quantity,
            'price' => $price,
            'title' => sanitize_text_field($item['title']),
            'notes' => isset($item['notes']) ? sanitize_textarea_field($item['notes']) : '',
        ];

        $orderItemRepository = new OrderItemRepository();
        $menuItem = $orderItemRepository->save($orderItemData);
    }

    /**
     * Get current active menu ID
     */
    private static function getCurrentMenuId(): int
    {
        $currentDate = current_time('Y-m-d');

        // Get menu for current date
        $menu = new \DailyMenuManager\Model\Menu();
        $menu = $menu->getMenuForDate($currentDate);

        if ($menu) {
            return (is_numeric($menu->id)) ? intval($menu->id) : -1;
        }

        return -1;
    }
    /**
     * Get menu id from menu item id
     */
    private static function getMenuIdFromMenuItemId($menuItemId): int
    {
        $menuItemRepository = new MenuItemRepository();
        $menuItem = $menuItemRepository->findById($menuItemId);
        if ($menuItem) {
            return $menuItem->getMenuId();
        }

        return 0;
    }

    /**
     * Update item stock after order
     */
    private static function updateItemStock($itemId, $quantity): void
    {
        // Get current available quantity
        // $currentStock = get_post_meta($itemId, '_available_quantity', true);

        // // If stock management is enabled, update the stock
        // if ($currentStock !== '' && is_numeric($currentStock)) {
        //     $newStock = max(0, intval($currentStock) - intval($quantity));
        //     update_post_meta($itemId, '_available_quantity', $newStock);
        // }
    }

    /**
     * Send order confirmation email
     */
    private static function sendOrderConfirmationEmail($orderData): bool
    {
        return true;

        // Get admin email
        $adminEmail = get_option('admin_email');
        $siteName = get_bloginfo('name');

        // Prepare customer email
        $to = $adminEmail; // Could also send to customer if email is collected
        $subject = sprintf('[%s] Neue Bestellung #%s', $siteName, $orderData['order_number']);

        // Build email content
        $message = "Neue Bestellung eingegangen:\n\n";
        $message .= "Bestellnummer: " . $orderData['order_number'] . "\n";
        $message .= "Kunde: " . $orderData['customer_name'] . "\n";
        $message .= "Telefon: " . $orderData['customer_phone'] . "\n";
        $message .= "Abholzeit: " . $orderData['pickup_time'] . "\n";
        $message .= "Konsum: " . ($orderData['consumption_type'] === 'pickup' ? 'Abholung' : 'Vor Ort') . "\n";

        if (!empty($orderData['customer_notes'])) {
            $message .= "Anmerkungen: " . $orderData['customer_notes'] . "\n";
        }

        $message .= "\nBestellte Gerichte:\n";
        foreach ($orderData['items'] as $item) {
            $message .= "- " . $item['quantity'] . "x " . $item['title'] . " (" . number_format($item['price'] * $item['quantity'], 2) . " €)\n";
            if (!empty($item['notes'])) {
                $message .= "  Anmerkung: " . $item['notes'] . "\n";
            }
        }

        $message .= "\nGesamtbetrag: " . number_format($orderData['total_amount'], 2) . " €\n";

        // Send email
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        return wp_mail($to, $subject, $message, $headers);
    }
}
