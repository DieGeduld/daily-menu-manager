<?php

namespace DailyMenuManager\Entity;

class OrderItem extends AbstractEntity
{
    public $id;
    public $order_id;
    public $menu_item_id;
    public $quantity;
    public $price;
    public $title;
    public $notes;
    public $created_at;
    public $updated_at;

    /**
     * Constructor to create an OrderItem entity from array data
     *
     * @param array $data Array of order item data
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->order_id = $data['order_id'] ?? null;
        $this->menu_item_id = intval($data['menu_item_id']) ?? null;
        $this->quantity = $data['quantity'] ?? 1;
        $this->price = $data['price'] ?? 0.00;
        $this->title = $data['title'] ?? '';
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /**
     * Convert entity to array
     *
     * @return array OrderItem data as array
     */
    public function toArray()
    {
        // TODO: check if everything is in
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'menu_item_id' => $this->menu_item_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'title' => $this->title,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the total price for this order item
     *
     * @return float Total price (quantity * price)
     */
    public function getTotalPrice()
    {
        return $this->quantity * $this->price;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }
}
