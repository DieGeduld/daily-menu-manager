<?php

namespace DailyMenuManager\Entity;

class Order extends AbstractEntity
{
    protected $menu_id;
    protected $menu_item_id;
    protected $order_number;
    protected $customer_name;
    protected $customer_phone;
    protected $consumption_type;
    protected $pickup_time;
    protected $customer_email;
    protected $quantity;
    protected $notes;
    protected $general_notes;
    protected $status;
    protected $order_date;

    /**
     * Constructor to create an Order entity from array data
     *
     * @param array $data Array of order data
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->menu_id = $data['menu_id'] ?? null;
        $this->menu_item_id = $data['menu_item_id'] ?? null;
        $this->order_number = $data['order_number'] ?? '';
        $this->customer_name = $data['customer_name'] ?? '';
        $this->customer_phone = $data['customer_phone'] ?? '';
        $this->consumption_type = $data['consumption_type'] ?? null;
        $this->pickup_time = $data['pickup_time'] ?? null;
        $this->customer_email = $data['customer_email'] ?? null;
        $this->quantity = $data['quantity'] ?? 1;
        $this->notes = $data['notes'] ?? null;
        $this->general_notes = $data['general_notes'] ?? null;
        $this->status = $data['status'] ?? 'pending';
        $this->order_date = $data['order_date'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    // Spezifische Getter/Setter für Order

    /**
     * Get order number
     *
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->order_number;
    }

    /**
     * Set order number
     *
     * @param string $orderNumber
     * @return $this
     */
    public function setOrderNumber($orderNumber)
    {
        $this->order_number = $orderNumber;

        return $this;
    }

    // Weitere spezifische Getter/Setter...
}
