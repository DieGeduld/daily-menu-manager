<?php

namespace DailyMenuManager\Service;

use DailyMenuManager\Entity\Menu;
use DailyMenuManager\Entity\MenuItem;
use DailyMenuManager\Repository\MenuItemRepository;
use DailyMenuManager\Repository\MenuRepository;

class MenuService
{
    private $menu_repository;
    private $menu_item_repository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->menu_repository = new MenuRepository();
        $this->menu_item_repository = new MenuItemRepository();
    }

    /**
     * Get menu for a specific date
     *
     * @param string $date The date in Y-m-d format
     * @return Menu|null The menu with items or null if not found
     */
    public function getMenuForDate($date)
    {
        $menu = $this->menu_repository->findByDate($date);

        if (!$menu) {
            return null;
        }

        // Populate menu with items
        $items = $this->menu_item_repository->findByMenuId($menu->getId());
        $menu->setMenuItems($items);

        return $menu;
    }

    /**
     * Holt das aktuelle Menü
     *
     * @return object|null
     */
    public function getCurrentMenu()
    {
        return $this->getMenuForDate(current_time('Y-m-d'));
    }

    /**
     * Create a new menu for a specific date
     *
     * @param string $date The date in Y-m-d format
     * @return Menu|WP_Error The created menu or WP_Error if it already exists
     */
    public function createMenu($date)
    {
        // Check if menu already exists
        if ($this->menu_repository->menuExists($date)) {
            return new \WP_Error(
                'menu_exists',
                __('A menu already exists for this date.', DMM_TEXT_DOMAIN)
            );
        }

        $menu = new Menu([
            'menu_date' => $date,
        ]);

        return $this->menu_repository->save($menu);
    }

    /**
     * Add a new item to a menu
     *
     * @param int $menu_id The menu ID
     * @param array $item_data The item data
     * @return MenuItem|WP_Error The created menu item or WP_Error on failure
     */
    public function addMenuItem($menu_id, $item_data)
    {
        // Check if menu exists
        $menu = $this->menu_repository->findById($menu_id);
        if (!$menu) {
            return new \WP_Error(
                'menu_not_found',
                __('Menu not found.', DMM_TEXT_DOMAIN)
            );
        }

        // Set menu ID
        $item_data['menu_id'] = $menu_id;

        // Create menu item
        $item = new MenuItem($item_data);

        return $this->menu_item_repository->save($item);
    }

    /**
     * Update a menu item
     *
     * @param int $item_id The item ID
     * @param array $item_data The updated item data
     * @return MenuItem|WP_Error The updated menu item or WP_Error on failure
     */
    public function updateMenuItem($item_id, $item_data)
    {
        // Check if item exists
        $item = $this->menu_item_repository->findById($item_id);
        if (!$item) {
            return new \WP_Error(
                'item_not_found',
                __('Menu item not found.', DMM_TEXT_DOMAIN)
            );
        }

        // Update item fields
        foreach ($item_data as $key => $value) {
            if (property_exists($item, $key)) {
                $item->$key = $value;
            }
        }

        return $this->menu_item_repository->save($item);
    }

    /**
     * Delete a menu item
     *
     * @param int $item_id The item ID
     * @return bool Whether the deletion was successful
     */
    public function deleteMenuItem($item_id)
    {
        $menu = $this->getMenuByMenuItemId($item_id);
        $deleteResult = $this->menu_item_repository->deleteById($item_id);

        if ($menu && $deleteResult) {
            $items = $this->menu_item_repository->findBy('menu_id', $menu->getId());
            if (count($items) == 0) {
                $deleteResult = $this->menu_repository->delete($menu);
            }
        }
        return true;
    }

    public function getMenuByMenuItemId($item_id): Menu|null
    {
        $menuItem = $this->menu_item_repository->findById($item_id);
        if ($menuItem) {
            $menu = $this->menu_repository->findById($menuItem->getMenuId());
            return $menu;
        }
        return null;
    }

    /**
     * Update menu item order
     *
     * @param array $item_order Array of item IDs in order
     * @return bool Whether the update was successful
     */
    public function updateItemOrder($item_order)
    {
        return $this->menu_item_repository->updateItemOrder($item_order);
    }

    /**
     * Copy a menu to a new date
     *
     * @param int $menu_id The ID of the menu to copy
     * @param string $target_date The target date in Y-m-d format
     * @return int|WP_Error The new menu ID or WP_Error on failure
     */
    public function copyMenu($menu_id, $target_date)
    {
        return $this->menu_repository->copyMenu($menu_id, $target_date);
    }

    /**
     * Duplicate a menu item
     *
     * @param int $item_id The ID of the menu item to duplicate
     * @return int|WP_Error The new menu item ID or WP_Error on failure
     */
    public function duplicateMenuItem($item_id)
    {
        return $this->menu_item_repository->duplicateMenuItem($item_id);
    }

    /**
     * Get available quantities for menu items
     *
     * @param int $menu_id The menu ID
     * @return array Array of item IDs and their available quantities
     */
    public function getAvailableQuantities($menu_id)
    {
        $items = $this->menu_item_repository->findByMenuId($menu_id);

        $quantities = [];
        foreach ($items as $item) {
            $quantities[$item->id] = $item->available_quantity;
        }

        return $quantities;
    }
}
