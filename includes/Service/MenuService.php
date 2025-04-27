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
        $items = $this->menu_item_repository->findByMenuId($menu->id);
        $menu->setItems($items);

        return $menu;
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
        return $this->menu_item_repository->deleteById($item_id);
    }

    /**
     * Save complete menu with items
     *
     * @param array $data Menu and items data
     * @return int|WP_Error The menu ID or WP_Error on failure
     */
    public function saveMenu($data)
    {
        // Sanitize and validate data
        $menu_date = isset($data['menu_date']) ? sanitize_text_field($data['menu_date']) : current_time('Y-m-d');
        $menu_id = isset($data['menu_id']) ? intval($data['menu_id']) : 0;

        // Create or update menu
        if ($menu_id) {
            $menu = $this->menu_repository->findById($menu_id);
            if (!$menu) {
                return new \WP_Error('menu_not_found', __('Menu not found.', DMM_TEXT_DOMAIN));
            }
            $menu->menu_date = $menu_date;
        } else {
            // Check if a menu already exists for this date
            if ($this->menu_repository->menuExists($menu_date)) {
                $menu = $this->menu_repository->findByDate($menu_date);
            } else {
                $menu = new Menu([
                    'menu_date' => $menu_date,
                ]);
            }
        }

        // Save menu
        $result = $this->menu_repository->save($menu);
        if (is_wp_error($result)) {
            return $result;
        }

        // Process menu items
        $menu_items = isset($data['menu_items']) ? $data['menu_items'] : [];

        foreach ($menu_items as $item_data) {
            $item_id = isset($item_data['id']) ? intval($item_data['id']) : 0;

            // Skip empty items
            if (empty($item_data['title'])) {
                continue;
            }

            // Get or create menu item
            if ($item_id) {
                $item = $this->menu_item_repository->findById($item_id);
                if (!$item) {
                    continue; // Skip if item not found
                }
            } else {
                $item = new MenuItem();
            }

            // Update item data
            $item->setMenuId(intval($menu->id)); // Set menu ID for new items
            $item->setItemType(sanitize_text_field($item_data['type'] ?? ''));
            $item->setTitle(sanitize_text_field($item_data['title'] ?? ''));
            $item->setDescription(wp_kses_post($item_data['description'] ?? ''));
            $item->setPrice(floatval($item_data['price'] ?? 0));
            $item->setAvailableQuantity(intval($item_data['available_quantity'] ?? 0));
            $item->setSortOrder(intval($item_data['sort_order'] ?? 0));
            $item->setAllergens(sanitize_textarea_field($item_data['allergens'] ?? ''));

            // Process properties
            $properties = [];
            if (isset($item_data['properties']) && is_array($item_data['properties'])) {
                foreach ($item_data['properties'] as $prop => $value) {
                    if ($value) {
                        $properties[sanitize_text_field($prop)] = true;
                    }
                }
            }
            $item->setProperties($properties);

            // Save menu item
            $this->menu_item_repository->save($item);
        }

        return $menu->id;
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
