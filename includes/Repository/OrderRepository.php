<?php

namespace DailyMenuManager\Repository;

use DailyMenuManager\Entity\Order;

class OrderRepository extends BaseRepository
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('menu_orders', Order::class);
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
     * @return Order The saved order with updated ID
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

        if (empty($order->id)) {
            // Insert new order
            $result = $this->wpdb->insert(
                $this->table_name,
                ['menu_id' => $data['menu_id'],
                    'menu_item_id' => $data['menu_item_id'],
                    'order_number' => $data['order_number'],
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'consumption_type' => $data['consumption_type'],
                    'pickup_time' => $data['pickup_time'],    
                    'customer_email' => $data['customer_email'],
                    'quantity' => $data['quantity'],
                    'notes' => $data['notes'],
                    'general_notes' => $data['general_notes'],
                    'status' => $data['status'],
                    'order_date' => $data['order_date']],
                [
                    '%d', // menu_id
                    '%d', // menu_item_id
                    '%s', // order_number
                    '%s', // customer_name
                    '%s', // customer_phone
                    '%s', // consumption_type
                    '%s', // pickup_time
                    '%s', // customer_email
                    '%d', // quantity
                    '%s', // notes
                    '%s', // general_notes
                    '%s', // status
                    '%s',  // order_date
                ]
            );

            if ($result === false) {
                return new \WP_Error('db_insert_error', $this->wpdb->last_error);
            }

            $order->id = $this->wpdb->insert_id;
        } else {
            // Update existing order
            $result = $this->wpdb->update(
                $this->table_name,
                ['menu_id' => $data['menu_id'],
                'menu_item_id' => $data['menu_item_id'],
                'order_number' => $data['order_number'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'consumption_type' => $data['consumption_type'],
                'pickup_time' => $data['pickup_time'],    
                'customer_email' => $data['customer_email'],
                'quantity' => $data['quantity'],
                'notes' => $data['notes'],
                'general_notes' => $data['general_notes'],
                'status' => $data['status'],
                'order_date' => $data['order_date']],
                ['id' => $data['id']],
                [
                    '%d', // menu_id
                    '%d', // menu_item_id
                    '%s', // order_number
                    '%s', // customer_name
                    '%s', // customer_phone
                    '%s', // consumption_type
                    '%s', // pickup_time
                    '%s', // customer_email
                    '%d', // quantity
                    '%s', // notes
                    '%s', // general_notes
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
     * Find orders by menu item ID
     *
     * @param int $menu_item_id The menu item ID
     * @return array Array of Order objects
     */
    public function findByMenuItemId($menu_item_id)
    {
        return $this->findBy('menu_item_id', $menu_item_id);
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
     * Generate a unique order number
     *
     * @return string The generated order number
     */
    private function generateOrderNumber()
    {
        $prefix = 'ORD-';
        $date = date('Ymd');
        $random = wp_rand(1000, 9999);

        $order_number = $prefix . $date . '-' . $random;

        // Check if this order number already exists
        if ($this->existsBy('order_number', $order_number)) {
            return $this->generateOrderNumber();
        }

        return $order_number;
    }

    /**
     * Update order status
     *
     * @param int $order_id The order ID
     * @param string $status The new status
     * @return bool Whether the update was successful
     */
    public function updateStatus($order_id, $status)
    {
        $result = $this->wpdb->update(
            $this->table_name,
            ['status' => $status],
            ['id' => $order_id],
            ['%s'],
            ['%d']
        );

        return $result !== false;
    }
}
