<?php

namespace DailyMenuManager\Repository;

use DailyMenuManager\Entity\MenuItem;
use DailyMenuManager\Interface\RepositoryInterface;

class MenuItemRepository extends BaseRepository
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('ddm_menu_items', MenuItem::class);
    }

    /**
     * Find a menu item by ID
     *
     * @param int $id The menu item ID
     * @return MenuItem|null The menu item or null if not found
     */
    public function findById($id): ?MenuItem
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );

        $result = $this->wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return new MenuItem($result);
    }

    /**
     * Find all menu items
     *
     * @return array Array of MenuItem objects
     */
    public function findAll()
    {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY sort_order ASC",
            ARRAY_A
        );

        $items = [];
        foreach ($results as $row) {
            $items[] = new MenuItem($row);
        }

        return $items;
    }

    /**
     * Save a menu item
     *
     * @param MenuItem $item The menu item to save
     * @return MenuItem The saved menu item with updated ID
     */
    public function save($item)
    {
        $data = $item->toArray();

        // Remove ID for insertion, WordPress will handle it
        if (empty($data['id'])) {
            unset($data['id']);
        }

        // Handle dates for created_at and updated_at
        unset($data['created_at']);
        unset($data['updated_at']);

        if ($item->getId() === null) {
            // For new items, determine the next sort order if not set
            if (empty($data['sort_order'])) {
                $data['sort_order'] = $this->getNextSortOrder($data['menu_id']);
            }

            // Insert new menu item
            $result = $this->wpdb->insert(
                $this->table_name,
                [
                    'menu_id' => $data['menu_id'],
                    'item_type' => $data['item_type'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'available_quantity' => $data['available_quantity'],
                    'properties' => is_array($data['properties']) ? json_encode($data['properties']) : $data['properties'],
                    'sort_order' => $data['sort_order'],
                    'allergens' => $data['allergens'],
                    'image_url' => $data['image_url'],
                    'image_id' => $data['image_id']
                ],
                [
                    '%d', // menu_id
                    '%s', // item_type
                    '%s', // title
                    '%s', // description
                    '%f', // price
                    '%d', // available_quantity
                    '%s', // properties (JSON)
                    '%d', // sort_order
                    '%s', // allergens
                    '%s', // image_url
                    '%d',  // image_id
                ]
            );

            if ($result === false) {
                return new \WP_Error('db_insert_error', $this->wpdb->last_error);
            }

            $item->setId($this->wpdb->insert_id);
        } else {
            // Update existing menu item
            $result = $this->wpdb->update(
                $this->table_name,
                [
                    'menu_id' => $data['menu_id'],
                    'item_type' => $data['item_type'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'available_quantity' => $data['available_quantity'],
                    'properties' => is_array($data['properties']) ? json_encode($data['properties']) : $data['properties'],
                    'sort_order' => $data['sort_order'],
                    'allergens' => $data['allergens'],
                    'image_url' => $data['image_url'],
                    'image_id' => $data['image_id']
                ],
                ['id' => $data['id']],
                [
                    '%d', // menu_id
                    '%s', // item_type
                    '%s', // title
                    '%s', // description
                    '%f', // price
                    '%d', // available_quantity
                    '%s', // properties (JSON)
                    '%d', // sort_order
                    '%s', // allergens
                    '%s', // image_url
                    '%d', // image_id
                ],
            );

            if ($result === false) {
                return new \WP_Error('db_update_error', $this->wpdb->last_error);
            }
        }

        return $item;
    }

    /**
     * Delete a menu item
     *
     * @param MenuItem $item The menu item to delete
     * @return bool Whether the deletion was successful
     */
    public function delete($item)
    {
        return $this->deleteById($item->getId());
    }

    /**
     * Delete a menu item by ID
     *
     * @param int $id The menu item ID to delete
     * @return bool Whether the deletion was successful
     */
    public function deleteById($id)
    {
        return $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * Find menu items by menu ID
     *
     * @param int $menu_id The menu ID
     * @return array Array of MenuItem objects
     */
    public function findByMenuId($menu_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE menu_id = %d ORDER BY sort_order ASC",
            $menu_id
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);

        $items = [];
        foreach ($results as $row) {
            $items[] = new MenuItem($row);
        }

        return $items;
    }

    /**
     * Duplicate a menu item
     *
     * @param int $item_id The ID of the menu item to duplicate
     * @return int|WP_Error The new menu item ID or WP_Error on failure
     */
    public function duplicateMenuItem($item_id)
    {
        // Get the original item
        $original_item = $this->findById($item_id);
        if (!$original_item) {
            return new \WP_Error(
                'item_not_found',
                __('Menu item not found.', DMM_TEXT_DOMAIN)
            );
        }

        // Create a new item based on the original
        $new_item = new MenuItem($original_item->toArray());
        $new_item->setId(null);
        $new_item->setTitle($original_item->getTitle() . ' ' . __('(Copy)', DMM_TEXT_DOMAIN));
        $new_item->setSortOrder($original_item->getSortOrder() + 1);

        // Save the new item
        $result = $this->save($new_item);
        if (is_wp_error($result)) {
            return $result;
        }

        // Update sort order for items after the new one
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->table_name} 
            SET sort_order = sort_order + 1 
            WHERE menu_id = %d 
            AND id != %d 
            AND sort_order >= %d",
            $new_item->getMenuId(),
            $new_item->getId(),
            $new_item->getSortOrder()
        ));

        return $new_item->getId();
    }

    /**
     * Get the next sort order for a menu
     *
     * @param int $menu_id The menu ID
     * @return int The next sort order
     */
    public function getNextSortOrder($menu_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT MAX(sort_order) FROM {$this->table_name} WHERE menu_id = %d",
            $menu_id
        );

        $max_order = $this->wpdb->get_var($query);

        return is_null($max_order) ? 0 : $max_order + 1;
    }

    /**
     * Update the order of menu items
     *
     * @param array $item_order Array of item IDs in order
     * @return bool Whether the update was successful
     */
    public function updateItemOrder($item_order)
    {
        if (empty($item_order) || !is_array($item_order)) {
            return false;
        }

        $success = true;
        foreach ($item_order as $index => $item_id) {
            $result = $this->wpdb->update(
                $this->table_name,
                ['sort_order' => $index],
                ['id' => $item_id],
                ['%d'],
                ['%d']
            );

            if ($result === false) {
                $success = false;
            }
        }

        return $success;
    }
}
