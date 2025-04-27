<?php

namespace DailyMenuManager\Repository;

use DailyMenuManager\Entity\Menu;
use DailyMenuManager\Entity\MenuItem;

class MenuRepository extends BaseRepository
{
    private $items_table_name;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('ddm_menus', Menu::class);
        global $wpdb;
        $this->items_table_name = $wpdb->prefix . 'ddm_menu_items';
    }

    /**
     * Find all menus
     *
     * @return array Array of Menu objects
     */
    public function findAll()
    {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY menu_date DESC",
            ARRAY_A
        );

        $menus = [];
        foreach ($results as $row) {
            $menus[] = new Menu($row);
        }

        return $menus;
    }

    /**
     * Save a menu
     *
     * @param Menu $menu The menu to save
     * @return Menu The saved menu with updated ID
     */
    public function save($menu)
    {
        $data = $menu->toArray();

        // Remove ID for insertion, WordPress will handle it
        if (empty($data['id'])) {
            unset($data['id']);
        }

        // Handle dates for created_at and updated_at
        unset($data['created_at']);
        unset($data['updated_at']);

        if (empty($data['id'])) {
            // Insert new menu
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                ['%s'] // menu_date
            );

            if ($result === false) {
                return new \WP_Error('db_insert_error', $this->wpdb->last_error);
            }

            $menu->id = $this->wpdb->insert_id;
        } else {
            // Update existing menu
            $result = $this->wpdb->update(
                $this->table_name,
                $data,
                ['id' => $data['id']],
                ['%s'], // menu_date
            );

            if ($result === false) {
                return new \WP_Error('db_update_error', $this->wpdb->last_error);
            }
        }

        return $menu;
    }

    /**
     * Delete a menu by ID
     *
     * @param int $id The menu ID to delete
     * @return bool Whether the deletion was successful
     */
    public function deleteById($id)
    {
        // First delete all menu items associated with this menu
        $this->wpdb->delete(
            $this->items_table_name,
            ['menu_id' => $id],
            ['%d']
        );

        // Then delete the menu itself
        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Find a menu by date
     *
     * @param string $date The date in Y-m-d format
     * @return Menu|null The menu or null if not found
     */
    public function findByDate($date)
    {
        return $this->findOneBy('menu_date', $date);
    }

    /**
     * Check if a menu exists for a given date
     *
     * @param string $date The date in Y-m-d format
     * @return bool Whether a menu exists for the date
     */
    public function menuExists($date)
    {
        return $this->existsBy('menu_date', $date);
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
        // Check if a menu already exists for the target date
        if ($this->menuExists($target_date)) {
            return new \WP_Error(
                'menu_exists',
                __('A menu already exists for this date.', DMM_TEXT_DOMAIN)
            );
        }

        // Get the source menu
        $source_menu = $this->findById($menu_id);
        if (!$source_menu) {
            return new \WP_Error(
                'menu_not_found',
                __('Source menu not found.', DMM_TEXT_DOMAIN)
            );
        }

        // Create new menu for target date
        $new_menu = new Menu([
            'menu_date' => $target_date,
        ]);

        // Save the new menu
        $result = $this->save($new_menu);
        if (is_wp_error($result)) {
            return $result;
        }

        // Get menu items repository
        $menu_item_repo = new MenuItemRepository();

        // Get menu items from source menu
        $source_items = $menu_item_repo->findByMenuId($menu_id);

        // Copy each menu item
        foreach ($source_items as $item) {
            $new_item = new MenuItem($item->toArray());
            $new_item->setId(null);  // Set ID to null for new item
            $new_item->setMenuId($new_menu->getId());  // Assign to new menu
            $menu_item_repo->save($new_item);
        }

        return $new_menu->id;
    }
}
