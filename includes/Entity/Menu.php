<?php

namespace DailyMenuManager\Entity;

class Menu extends AbstractEntity
{
    public $id;
    public $menu_date;
    public $created_at;
    public $updated_at;

    // Menu items collection
    private $items = [];

    /**
     * Constructor to create a Menu entity from array data
     *
     * @param array $data Array of menu data
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->menu_date = $data['menu_date'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /**
     * Add a menu item to this menu
     *
     * @param MenuItem $item The menu item to add
     */
    public function addItem(MenuItem $item)
    {
        $this->items[] = $item;
    }

    /**
     * Get all menu items for this menu
     *
     * @return array Array of MenuItem objects
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set the menu items collection
     *
     * @param array $items Array of MenuItem objects
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }

    /**
     * Convert entity to array
     *
     * @return array Menu data as array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'menu_date' => $this->menu_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
