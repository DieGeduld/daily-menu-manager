<?php

namespace DailyMenuManager\Entity;

class MenuItem extends AbstractEntity
{
    protected $id;
    protected $menu_id;
    protected $item_type;
    protected $title;
    protected $description;
    protected $price;
    protected $available_quantity;
    protected $properties;
    protected $sort_order;
    protected $allergens;
    protected $image_url;
    protected $image_id;
    protected $created_at;
    protected $updated_at;

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
}
