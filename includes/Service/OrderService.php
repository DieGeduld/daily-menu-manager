<?php

namespace DailyMenuManager\Service;

use DailyMenuManager\Entity\Order;
use DailyMenuManager\Repository\OrderRepository;

class OrderService
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * Constructor
     *
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Create a complete order with multiple items
     *
     * @param array $data Order data including items
     * @return array|WP_Error Order result or error
     */
    public function createOrder(array $data)
    {
        try {
            if (empty($data['customer_name'])) {
                return new \WP_Error('invalid_order', 'Kundenname ist erforderlich');
            }

            if (empty($data['items']) || !is_array($data['items'])) {
                return new \WP_Error('invalid_order', 'Keine Artikel in der Bestellung');
            }

            $hasItems = false;
            foreach ($data['items'] as $item) {
                if (!empty($item['quantity']) && $item['quantity'] > 0) {
                    $hasItems = true;

                    break;
                }
            }

            if (!$hasItems) {
                return new \WP_Error('invalid_order', 'Keine Artikel mit Menge > 0');
            }

            // Delegate to repository for actual database operations
            return $this->orderRepository->createOrder($data);
        } catch (\Exception $e) {
            return new \WP_Error('order_creation_failed', $e->getMessage());
        }
    }

    /**
     * Get all orders with optional filtering
     *
     * @param array $filters Optional filter parameters
     * @return array Orders list
     */
    public function getOrders(array $filters = [])
    {
        return $this->orderRepository->getOrders($filters);
    }

    /**
     * Get order details by order number
     *
     * @param string $orderNumber Order number
     * @return array|null Order details or null if not found
     */
    public function getOrderDetails($orderNumber)
    {
        $orderItems = $this->orderRepository->getOrderByNumber($orderNumber);

        if (empty($orderItems)) {
            return null;
        }

        // Group items by order number and calculate totals
        $totalAmount = 0;
        $items = [];

        foreach ($orderItems as $item) {
            $totalAmount += $item->quantity * $item->price;
            $items[] = [
                'title' => $item->menu_item_title,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'notes' => $item->notes,
                'type' => $item->item_type,
            ];
        }

        // Get first item for common order data
        $firstItem = $orderItems[0];

        return [
            'order_number' => $firstItem->order_number,
            'customer_name' => $firstItem->customer_name,
            'customer_phone' => $firstItem->customer_phone,
            'pickup_time' => $firstItem->pickup_time,
            'consumption_type' => $firstItem->consumption_type,
            'order_date' => $firstItem->order_date,
            'status' => $firstItem->status,
            'general_notes' => $firstItem->general_notes,
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Update order status
     *
     * @param string $orderNumber Order number
     * @param string $status New status
     * @return bool Success status
     */
    public function updateOrderStatus($orderNumber, $status)
    {
        if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
            return false;
        }

        return $this->orderRepository->updateOrderStatus($orderNumber, $status);
    }

    /**
     * Cancel an order
     *
     * @param string $orderNumber Order number
     * @return bool Success status
     */
    public function cancelOrder($orderNumber)
    {
        return $this->updateOrderStatus($orderNumber, 'cancelled');
    }

    /**
     * Complete an order
     *
     * @param string $orderNumber Order number
     * @return bool Success status
     */
    public function completeOrder($orderNumber)
    {
        return $this->updateOrderStatus($orderNumber, 'completed');
    }

    /**
     * Delete an order
     *
     * @param string $orderNumber Order number
     * @return bool Success status
     */
    public function deleteOrder($orderNumber)
    {
        return $this->orderRepository->deleteOrder($orderNumber);
    }

    /**
     * Get order statistics for a date range
     *
     * @param string|null $startDate Start date in Y-m-d format
     * @param string|null $endDate End date in Y-m-d format
     * @return array Order statistics
     */
    public function getOrderStatistics($startDate = null, $endDate = null)
    {
        $stats = $this->orderRepository->getOrderStats($startDate, $endDate);

        $totalOrders = 0;
        $totalRevenue = 0;
        $totalItems = 0;

        foreach ($stats as $dayStat) {
            $totalOrders += $dayStat->total_orders;
            $totalRevenue += $dayStat->total_revenue;
            $totalItems += $dayStat->total_items;
        }

        return [
            'daily_stats' => $stats,
            'summary' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'total_items' => $totalItems,
                'average_order_value' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
                'date_range' => [
                    'start' => $startDate ?: date('Y-m-d'),
                    'end' => $endDate ?: date('Y-m-d'),
                ],
            ],
        ];
    }

    /**
     * Search orders by various criteria
     *
     * @param string $searchTerm Search term
     * @return array Search results
     */
    public function searchOrders($searchTerm)
    {
        $filters = [];

        // Try to identify what the search term might be
        if (preg_match('/^\d{3}$/', $searchTerm)) {
            // Looks like an order number
            $filters['order_number'] = $searchTerm;
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchTerm)) {
            // Looks like a date
            $filters['date'] = $searchTerm;
        } elseif (preg_match('/^\+?[\d\s\-\(\)]{7,}$/', $searchTerm)) {
            // Looks like a phone number
            $filters['customer_phone'] = $searchTerm;
        } else {
            // Assume it's a customer name
            $filters['customer_name'] = $searchTerm;
        }

        return $this->orderRepository->getOrders($filters);
    }
}
