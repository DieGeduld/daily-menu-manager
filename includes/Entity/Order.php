<?php

namespace DailyMenuManager\Entity;

class Order
{
    public $id;
    public $menu_id;
    public $menu_item_id;
    public $order_number;
    public $customer_name;
    public $customer_phone;
    public $consumption_type;
    public $pickup_time;
    public $customer_email;
    public $quantity;
    public $notes;
    public $general_notes;
    public $status;
    public $order_date;
    public $created_at;
    public $updated_at;

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

    /**
     * Convert entity to array
     *
     * @return array Order data as array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'menu_id' => $this->menu_id,
            'menu_item_id' => $this->menu_item_id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'consumption_type' => $this->consumption_type,
            'pickup_time' => $this->pickup_time,
            'customer_email' => $this->customer_email,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'general_notes' => $this->general_notes,
            'status' => $this->status,
            'order_date' => $this->order_date,
        ];
    }
}
