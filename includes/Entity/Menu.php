<?php

namespace DailyMenuManager\Entity;

class Menu extends AbstractEntity
{
    protected $id;
    protected $menu_date;
    protected $created_at;
    protected $updated_at;

    // Menu items collection
    protected $menuItems = [];

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
}
