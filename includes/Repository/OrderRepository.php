<?php

namespace DailyMenuManager\Repository;

use DailyMenuManager\Entity\Order;
use DailyMenuManager\Entity\OrderItem;

class OrderRepository extends BaseRepository
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('ddm_orders', Order::class);
    }

    /**
     * Find all orders
     *
     * @return array Array of Order objects
     */
    public function findAll()
    {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY order_date DESC",
            ARRAY_A
        );

        $orders = [];
        foreach ($results as $row) {
            $orders[] = new Order($row);
        }

        return $orders;
    }

    /**
     * Save an order
     *
     * @param Order $order The order to save
     * @return Order|WP_Error The saved order with updated ID or error
     */
    public function save($order)
    {
        $data = $order->toArray();

        // Remove ID for insertion, WordPress will handle it
        if (empty($data['id'])) {
            unset($data['id']);

            // Generate a unique order number if not set
            if (empty($data['order_number'])) {
                $data['order_number'] = $this->generateOrderNumber();
            }
        }

        // Handle dates for created_at and updated_at
        unset($data['created_at']);
        unset($data['updated_at']);

        if (empty($order->getId())) {
            // Insert new order
            $result = $this->wpdb->insert(
                $this->table_name,
                [
                    'menu_id' => $data['menu_id'],
                    'order_number' => $data['order_number'],
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'consumption_type' => $data['consumption_type'],
                    'pickup_time' => $data['pickup_time'],
                    'customer_email' => $data['customer_email'],
                    'notes' => $data['notes'],
                    'status' => $data['status'],
                    'order_date' => $data['order_date'],
                ],
                [
                    '%d', // menu_id
                    '%s', // order_number
                    '%s', // customer_name
                    '%s', // customer_phone
                    '%s', // consumption_type
                    '%s', // pickup_time
                    '%s', // customer_email
                    '%s', // notes
                    '%s', // status
                    '%s',  // order_date
                ]
            );

            if ($result === false) {
                return new \WP_Error('db_insert_error', $this->wpdb->last_error);
            }

            $order->setId($this->wpdb->insert_id);
        } else {
            // Update existing order
            $result = $this->wpdb->update(
                $this->table_name,
                [
                    'menu_id' => $data['menu_id'],
                    'order_number' => $data['order_number'],
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'consumption_type' => $data['consumption_type'],
                    'pickup_time' => $data['pickup_time'],
                    'customer_email' => $data['customer_email'],
                    'notes' => $data['notes'],
                    'status' => $data['status'],
                    'order_date' => $data['order_date'],
                ],
                ['id' => $data['id']],
                [
                    '%d', // menu_id
                    '%s', // order_number
                    '%s', // customer_name
                    '%s', // customer_phone
                    '%s', // consumption_type
                    '%s', // pickup_time
                    '%s', // customer_email
                    '%s', // notes
                    '%s', // status
                    '%s',  // order_date
                ],
                ['%d'] // id
            );

            if ($result === false) {
                return new \WP_Error('db_update_error', $this->wpdb->last_error);
            }
        }

        return $order;
    }

    /**
     * Find orders by menu ID
     *
     * @param int $menu_id The menu ID
     * @return array Array of Order objects
     */
    public function findByMenuId($menu_id)
    {
        return $this->findBy('menu_id', $menu_id);
    }

    /**
     * Find orders by status
     *
     * @param string $status The order status
     * @return array Array of Order objects
     */
    public function findByStatus($status)
    {
        return $this->findBy('status', $status);
    }

    /**
     * Find orders by date
     *
     * @param string $date The date in Y-m-d format
     * @return array Array of Order objects
     */
    public function findByDate($date)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE DATE(order_date) = %s ORDER BY order_date DESC",
            $date
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);

        $orders = [];
        foreach ($results as $row) {
            $orders[] = new Order($row);
        }

        return $orders;
    }

    /**
     * Generate a unique order number (3-digit format)
     *
     * @return string The generated order number
     */
    protected function generateOrderNumber()
    {
        // Generiere fortlaufende Bestellnummer (000-999)
        $last_order = $this->wpdb->get_var(
            "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '-', -1) AS UNSIGNED)) 
            FROM {$this->table_name}"
        );

        // Wenn keine Bestellung existiert oder der letzte Wert kein gültiger Integer ist
        if ($last_order === false || !is_numeric($last_order)) {
            $next_number = 0;
        } else {
            $next_number = intval($last_order) + 1;
            if ($next_number > 999) {
                $next_number = 0;
            }
        }

        // Formatiere die Bestellnummer mit führenden Nullen
        return str_pad($next_number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new order with all related items
     *
     * @param array $data Order data including items
     * @return array|WP_Error Order information or error
     */
    public function createOrder($data)
    {
        try {
            $order_number = $this->generateOrderNumber();
            $order_items = [];
            $total_amount = 0;
            $created_orders = [];

            $this->wpdb->query('START TRANSACTION');

            $order_data = new Order([
                'menu_id' => intval($data['menu_id']),
                'menu_item_id' => intval($data['menuItemId']),
                'order_number' => $order_number,
                'customer_name' => sanitize_text_field($data['customer_name']),
                'customer_phone' => sanitize_text_field($data['customer_phone']),
                'consumption_type' => sanitize_text_field($data['consumption_type']),
                'pickup_time' => sanitize_text_field($data['pickup_time']),
                'notes' => sanitize_textarea_field($item['notes'] ?? ''),
                'general_notes' => sanitize_textarea_field($data['general_notes'] ?? ''),
                'order_date' => current_time('mysql'),
            ]);

            // Speichern der Order-Entity
            $order = $this->save($order_data);
            if (is_wp_error($order)) {
                //throw new \Exception($saved_order->get_error_message());
            }

            $order_items = [];
            $total_amount = 0;

            foreach ($data['items'] as $item) {
                $quantity = intval($item['quantity']);
                if ($quantity > 0) {
                    $created_orders[] = $order;

                    $menuItemRepository = new MenuItemRepository();
                    $menuItem = $menuItemRepository->findById($item['menuItemId']);

                    if (!$menuItem) {
                        throw new \Exception('Menu item not found');
                    }

                    // Title auch speichern?
                    $order_item_data = [
                        'order_id' => $order->id,
                        'quantity' => $quantity,
                        'menu_item_id' => $menuItem->getId(),
                        'title' => $menuItem->getTitle(),
                        'price' => $menuItem->getPrice(),
                        'notes' => isset($item['notes']) ? sanitize_textarea_field($item['notes']) : '',
                    ];

                    $orderItem = new OrderItem($order_item_data);

                    $orderItemRepository = new OrderItemRepository();
                    $order_item = $orderItemRepository->save($orderItem);

                    $order_items[] = $order_item;
                    $total_amount += $order_item->getTotalPrice();
                }
            }

            $this->wpdb->query('COMMIT');

            return [
                'success' => true,
                'order_number' => $order_number,
                'items' => $order_items,
                'total_amount' => $total_amount,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'pickup_time' => $data['pickup_time'],
                'orders' => $created_orders, // Füge die erstellten Order-Entities hinzu
            ];
        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');

            return new \WP_Error('order_creation_failed', $e->getMessage());
        }
    }

    /**
     * Holt Bestellungen mit angewendeten Filtern
     */
    public function getOrders($filters)
    {
        global $wpdb;

        $where_clauses = [];
        $query_params = [];

        // Filter nach Datum
        if (!empty($filters['date'])) {
            $where_clauses[] = "DATE(o.order_date) = %s";
            $query_params[] = $filters['date'];
        }

        // Filter nach Bestellnummer
        if (!empty($filters['order_number'])) {
            $where_clauses[] = "o.order_number LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($filters['order_number']) . '%';
        }

        // Filter nach Kundenname
        if (!empty($filters['customer_name'])) {
            $where_clauses[] = "o.customer_name LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($filters['customer_name']) . '%';
        }

        // Filter nach Telefonnummer
        if (!empty($filters['customer_phone'])) {
            $where_clauses[] = "o.customer_phone LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($filters['customer_phone']) . '%';
        }

        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

        $query = $wpdb->prepare(
            "SELECT 
                o.id,
                o.order_number,
                o.order_date,
                o.customer_name,
                o.customer_phone,
                o.consumption_type,
                o.pickup_time,
                o.notes AS general_notes,
                oi.id AS order_item_id,
                oi.menu_item_id,
                oi.title AS menu_item_title,
                oi.quantity,
                oi.price,
                oi.notes,
                (SELECT MIN(oi2.id) FROM {$this->order_items_table} oi2 WHERE oi2.order_id = o.id) AS first_item_in_order
            FROM 
                {$this->orders_table} o
            INNER JOIN 
                {$this->order_items_table} oi ON o.id = oi.order_id
            {$where_sql}
            ORDER BY 
                o.order_date DESC, o.order_number, oi.id",
            $query_params
        );

        return $wpdb->get_results($query);
    }

    public function getOrderItemsByOrderIds($order_ids)
    {
        global $wpdb;
        if (empty($order_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));
        $sql = "SELECT * FROM {$wpdb->prefix}ddm_order_items WHERE order_id IN ($placeholders)";

        return $wpdb->get_results($wpdb->prepare($sql, ...$order_ids));
    }

    /**
     * Get order statistics for a period
     *
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @return array Order statistics
     */
    public function getOrderStats($start_date = null, $end_date = null)
    {
        if (!$start_date) {
            $start_date = date('Y-m-d');
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        $stats = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                DATE(o.order_date) as date,
                COUNT(DISTINCT o.order_number) as total_orders,
                SUM(o.quantity * mi.price) as total_revenue,
                COUNT(o.id) as total_items
            FROM {$this->table_name} o
            JOIN {$this->wpdb->prefix}ddm_menu_items mi ON o.menu_item_id = mi.id
            WHERE DATE(o.order_date) BETWEEN %s AND %s
            GROUP BY DATE(o.order_date)
            ORDER BY date DESC
        ", $start_date, $end_date));

        return $stats;
    }

    /**
     * Get a single order by order number
     *
     * @param string $order_number Order number
     * @return array Order details
     */
    public function getOrderByNumber($order_number)
    {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                o.*,
                mi.title as menu_item_title,
                mi.price,
                mi.item_type
            FROM {$this->table_name} o
            JOIN {$this->wpdb->prefix}ddm_menu_items mi ON o.menu_item_id = mi.id
            WHERE o.order_number = %s
            ORDER BY mi.item_type, mi.title
        ", $order_number));
    }

    /**
     * Delete an order by order number
     *
     * @param string $order_number Order number
     * @return bool Success or failure
     */
    public function deleteOrder($order_number)
    {
        return $this->wpdb->delete(
            $this->table_name,
            ['order_number' => $order_number],
            ['%s']
        );
    }

    /**
     * Update order status by order number
     *
     * @param string $order_number Order number
     * @param string $status New status
     * @return bool Success or failure
     */
    public function updateOrderStatus($order_number, $status)
    {
        return $this->wpdb->update(
            $this->table_name,
            ['status' => $status],
            ['order_number' => $order_number],
            ['%s'],
            ['%s']
        );
    }
}
