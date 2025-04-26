<?php

namespace DailyMenuManager\Entity;

class MenuItem
{
    public $id;
    public $menu_id;
    public $item_type;
    public $title;
    public $description;
    public $price;
    public $available_quantity;
    public $properties;
    public $sort_order;
    public $allergens;
    public $image_url;
    public $image_id;
    public $created_at;
    public $updated_at;

    /**
     * Constructor to create a MenuItem entity from array data
     *
     * @param array $data Array of menu item data
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->menu_id = $data['menu_id'] ?? null;
        $this->item_type = $data['item_type'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->price = $data['price'] ?? 0;
        $this->available_quantity = $data['available_quantity'] ?? 0;

        // Handle properties field which could be a JSON string or an array
        if (isset($data['properties'])) {
            if (is_string($data['properties']) && !empty($data['properties'])) {
                $this->properties = json_decode($data['properties'], true);
            } else {
                $this->properties = $data['properties'];
            }
        } else {
            $this->properties = [];
        }

        $this->sort_order = $data['sort_order'] ?? 0;
        $this->allergens = $data['allergens'] ?? '';
        $this->image_url = $data['image_url'] ?? null;
        $this->image_id = $data['image_id'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /**
     * Convert entity to array
     *
     * @return array MenuItem data as array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'menu_id' => $this->menu_id,
            'item_type' => $this->item_type,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'available_quantity' => $this->available_quantity,
            'properties' => is_array($this->properties) ? json_encode($this->properties) : $this->properties,
            'sort_order' => $this->sort_order,
            'allergens' => $this->allergens,
            'image_url' => $this->image_url,
            'image_id' => $this->image_id,
        ];
    }
}
