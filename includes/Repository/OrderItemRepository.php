<?php

namespace DailyMenuManager\Repository;

use DailyMenuManager\Entity\OrderItem;

class OrderItemRepository extends BaseRepository
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('daily_menu_order_items', OrderItem::class);
    }

    /**
     * Find all order items
     *
     * @return array Array of OrderItem objects
     */
    public function findAll()
    {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC",
            ARRAY_A
        );

        $orderItems = [];
        foreach ($results as $row) {
            $orderItems[] = new OrderItem($row);
        }

        return $orderItems;
    }

    /**
     * Save an order item
     *
     * @param OrderItem $orderItem The order item to save
     * @return OrderItem|WP_Error The saved order item with updated ID, or WP_Error on failure
     */
    public function save($orderItem)
    {
        $data = $orderItem->toArray();

        // Remove ID for insertion, WordPress will handle it
        if (empty($data['id'])) {
            unset($data['id']);
        }

        // Handle dates for created_at and updated_at
        unset($data['created_at']);
        unset($data['updated_at']);

        if (empty($orderItem->id)) {
            // Insert new order item
            $result = $this->wpdb->insert(
                $this->table_name,
                [
                    'order_id' => $data['order_id'],
                    'menu_id' => $data['menu_id'],
                    'menu_item_id' => $data['menu_item_id'],
                    'quantity' => $data['quantity'],
                    'price' => $data['price'],
                    'title' => $data['title'],
                    'notes' => $data['notes'],
                ],
                [
                    '%d', // order_id
                    '%d', // menu_id
                    '%d', // menu_item_id
                    '%d', // quantity
                    '%f', // price
                    '%s', // title
                    '%s', // notes
                ]
            );

            if ($result === false) {
                return new \WP_Error('db_insert_error', $this->wpdb->last_error);
            }

            $orderItem->id = $this->wpdb->insert_id;
        } else {
            // Update existing order item
            $result = $this->wpdb->update(
                $this->table_name,
                [
                    'order_id' => $data['order_id'],
                    'menu_id' => $data['menu_id'],
                    'menu_item_id' => $data['menu_item_id'],
                    'quantity' => $data['quantity'],
                    'price' => $data['price'],
                    'title' => $data['title'],
                    'notes' => $data['notes'],
                ],
                ['id' => $data['id']],
                [
                    '%d', // order_id
                    '%d', // menu_id
                    '%d', // menu_item_id
                    '%d', // quantity
                    '%f', // price
                    '%s', // title
                    '%s', // notes
                ],
                ['%d'] // id
            );

            if ($result === false) {
                return new \WP_Error('db_update_error', $this->wpdb->last_error);
            }
        }

        return $orderItem;
    }

    /**
     * Find order items by order ID
     *
     * @param int $order_id The order ID
     * @return array Array of OrderItem objects
     */
    public function findByOrderId($order_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE order_id = %d ORDER BY id ASC",
            $order_id
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);

        $orderItems = [];
        foreach ($results as $row) {
            $orderItems[] = new OrderItem($row);
        }

        return $orderItems;
    }

    /**
     * Find order items by menu ID
     *
     * @param int $menu_id The menu ID
     * @return array Array of OrderItem objects
     */
    public function findByMenuId($menu_id)
    {
        return $this->findBy('menu_id', $menu_id);
    }

    /**
     * Find order items by menu item ID
     *
     * @param int $menu_item_id The menu item ID
     * @return array Array of OrderItem objects
     */
    public function findByMenuItemId($menu_item_id)
    {
        return $this->findBy('menu_item_id', $menu_item_id);
    }

    /**
     * Delete order items by order ID
     *
     * @param int $order_id The order ID
     * @return bool Whether the deletion was successful
     */
    public function deleteByOrderId($order_id)
    {
        $result = $this->wpdb->delete(
            $this->table_name,
            ['order_id' => $order_id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get total amount for an order
     *
     * @param int $order_id The order ID
     * @return float The total amount
     */
    public function getTotalAmount($order_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT SUM(quantity * price) as total FROM {$this->table_name} WHERE order_id = %d",
            $order_id
        );

        $result = $this->wpdb->get_var($query);

        return $result ? floatval($result) : 0.00;
    }

    /**
     * Get total quantity of all items in an order
     *
     * @param int $order_id The order ID
     * @return int The total quantity
     */
    public function getTotalQuantity($order_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT SUM(quantity) as total FROM {$this->table_name} WHERE order_id = %d",
            $order_id
        );

        $result = $this->wpdb->get_var($query);

        return $result ? intval($result) : 0;
    }

    /**
     * Batch save order items
     *
     * @param array $items Array of OrderItem objects
     * @param int $order_id The order ID to associate with
     * @return array|WP_Error Array of saved OrderItem objects or WP_Error on failure
     */
    public function saveItems(array $items, int $order_id)
    {
        // Begin transaction if supported
        if (method_exists($this->wpdb, 'begin_transaction')) {
            $this->wpdb->begin_transaction();
        }

        $savedItems = [];
        $error = null;

        try {
            foreach ($items as $item) {
                if (!($item instanceof OrderItem)) {
                    // Convert array to OrderItem if needed
                    if (is_array($item)) {
                        $item['order_id'] = $order_id;
                        $item = new OrderItem($item);
                    } else {
                        throw new \Exception('Invalid item type');
                    }
                } else {
                    $item->order_id = $order_id;
                }

                $savedItem = $this->save($item);
                if (is_wp_error($savedItem)) {
                    throw new \Exception($savedItem->get_error_message());
                }
                $savedItems[] = $savedItem;
            }

            // Commit transaction if supported
            if (method_exists($this->wpdb, 'commit')) {
                $this->wpdb->commit();
            }

            return $savedItems;
        } catch (\Exception $e) {
            // Rollback transaction if supported
            if (method_exists($this->wpdb, 'rollback')) {
                $this->wpdb->rollback();
            }

            return new \WP_Error('batch_save_error', $e->getMessage());
        }
    }
}
