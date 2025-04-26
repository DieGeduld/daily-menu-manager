<?php

namespace DailyMenuManager\Controller\Admin;

use DailyMenuManager\Entity\MenuItem;
use DailyMenuManager\Repository\MenuItemRepository;
use DailyMenuManager\Service\MenuService;

class MenuController
{
    private static $instance = null;
    private static $menu_service;

    public static function init()
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$menu_service = new MenuService();
        }

        add_action('admin_menu', [self::class, 'addAdminMenu']);
    }

    /**
     * Adds menu entries to the WordPress Admin
     */
    public static function addAdminMenu()
    {
        add_menu_page(
            __('Daily Menu Manager', 'daily-menu-manager'),
            __('Daily Menu', 'daily-menu-manager'),
            'manage_options',
            'daily-menu-manager',
            [self::class, 'displayMenuPage'],
            'dashicons-food',
            6
        );
    }

    /**
     * Displays the main page of the menu manager
     */
    public static function displayMenuPage()
    {
        // Get selected date or set current date
        $selected_date = isset($_GET['menu_date']) ? sanitize_text_field($_GET['menu_date']) : current_time('Y-m-d');

        // Save menu if form was submitted
        if (isset($_POST['save_menu']) && check_admin_referer('save_menu_nonce')) {
            $result = self::saveMenuFromPost($_POST);

            if (is_wp_error($result)) {
                add_settings_error(
                    'daily_dish_manager',
                    'save_error',
                    $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'daily_dish_manager',
                    'save_success',
                    __('Menu saved successfully.', 'daily-menu-manager'),
                    'success'
                );
            }
        }

        // Get the current menu with items
        $current_menu = self::$menu_service->getMenuForDate($selected_date);
        $menu_items = $current_menu ? $current_menu->getItems() : [];

        // Load the template
        require_once DMM_PLUGIN_DIR . 'includes/Views/admin-menu-page.php';
    }

    /**
     * Saves a menu from POST data
     *
     * @param array $post_data The POST data
     * @return int|WP_Error The menu ID or WP_Error on failure
     */
    private static function saveMenuFromPost($post_data)
    {
        return self::$menu_service->saveMenu($post_data);
    }

    /**
     * AJAX handler for saving menu item order
     */
    public static function handleSaveMenuOrder()
    {
        check_ajax_referer('daily_dish_manager_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $item_order = $_POST['item_order'] ?? [];
        if (empty($item_order)) {
            wp_send_json_error(['message' => __('No order information received.', 'daily-menu-manager')]);
        }

        $result = self::$menu_service->updateItemOrder($item_order);

        if ($result) {
            wp_send_json_success(['message' => __('Order updated.', 'daily-menu-manager')]);
        } else {
            wp_send_json_error(['message' => __('Error updating order.', 'daily-menu-manager')]);
        }
    }

    /**
     * AJAX handler for copying a menu
     */
    public static function handleCopyMenu()
    {
        check_ajax_referer('daily_dish_manager_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $menu_id = intval($_POST['menu_id']);
        $type = sanitize_text_field($_POST['type']);
        $selectedDate = sanitize_text_field($_POST["selectedDate"]);
        $currentDate = sanitize_text_field($_POST["currentDate"]);

        if (!$currentDate) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'daily-menu-manager')]);
        }

        if ($type == "from") {
            $menu = self::$menu_service->getMenuForDate($selectedDate);

            if (!$menu) {
                wp_send_json_error(['message' => __('No menu exists for this date.', 'daily-menu-manager')]);
                exit();
            }

            $result = self::$menu_service->copyMenu($menu->id, $currentDate);

            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            } else {
                wp_send_json_success([
                    'message' => __('Menu copied successfully.', 'daily-menu-manager'),
                    'new_menu_id' => $result,
                ]);
            }
        } elseif ($type == "to") {
            if (!$currentDate || !$menu_id) {
                wp_send_json_error(['message' => __('Invalid parameters, menu ID missing', 'daily-menu-manager')]);
            }

            $result = self::$menu_service->copyMenu($menu_id, $selectedDate);

            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            } else {
                wp_send_json_success([
                    'message' => __('Menu copied successfully.', 'daily-menu-manager'),
                    'new_menu_id' => $result,
                ]);
            }
        } else {
            wp_send_json_error(['message' => __('Invalid parameters.', 'daily-menu-manager')]);
        }
    }

    /**
     * AJAX handler for deleting a menu item
     */
    public static function handleDeleteMenuItem()
    {
        check_ajax_referer('daily_dish_manager_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $item_id = intval($_POST['item_id']);
        if (!$item_id) {
            wp_send_json_error(['message' => __('Invalid menu item ID.', 'daily-menu-manager')]);
        }

        $result = self::$menu_service->deleteMenuItem($item_id);

        if (!$result) {
            wp_send_json_error(['message' => __('Error deleting menu item.', 'daily-menu-manager')]);
        } else {
            wp_send_json_success(['message' => __('Menu item deleted successfully.', 'daily-menu-manager')]);
        }
    }

    /**
     * AJAX handler for duplicating a menu item
     */
    public static function handleDuplicateMenuItem()
    {
        check_ajax_referer('daily_dish_manager_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $item_id = intval($_POST['item_id']);
        if (!$item_id) {
            wp_send_json_error(['message' => __('Invalid menu item ID.', 'daily-menu-manager')]);
        }

        $new_item_id = self::$menu_service->duplicateMenuItem($item_id);

        if (is_wp_error($new_item_id)) {
            wp_send_json_error(['message' => $new_item_id->get_error_message()]);
        }

        // Get the new item for rendering
        $menu_item_repository = new MenuItemRepository();
        $new_item = $menu_item_repository->findById($new_item_id);

        ob_start();
        self::renderMenuItem($new_item);
        $html = ob_get_clean();

        wp_send_json_success([
            'message' => __('Menu item duplicated successfully.', 'daily-menu-manager'),
            'html' => $html,
        ]);
    }

    /**
     * AJAX handler for getting menu data
     */
    public static function handleGetMenuData()
    {
        check_ajax_referer('daily-menu-manager');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : current_time('Y-m-d');

        $current_menu = self::$menu_service->getMenuForDate($date);
        $menu_items = $current_menu ? $current_menu->getItems() : [];

        wp_send_json_success([
            'menu' => $current_menu ? $current_menu->toArray() : null,
            'items' => array_map(function ($item) { return $item->toArray(); }, $menu_items),
        ]);
    }

    /**
     * AJAX handler for getting today's menu
     */
    public static function handleGetCurrentMenu()
    {
        check_ajax_referer('daily_dish_manager_nonce');

        //sleep(2); // Simulating processing time

        $current_menu = self::$menu_service->getMenuForDate(current_time('Y-m-d'));
        $item_types = SettingsController::getMenuTypes(true);

        $grouped_items = [];
        foreach ($current_menu->getItems() as $item) {
            $menuTypePlural = $item_types[$item->item_type]["plural"];
            if (!$menuTypePlural) {
                $menuTypePlural = $item->item_type . 's';
            }
            if (!isset($grouped_items[$menuTypePlural])) {
                $grouped_items[$menuTypePlural] = [];
            }
            $grouped_items[$menuTypePlural][] = $item->toArray();
        }

        if (!$current_menu) {
            // TODO: Be able to enter a custom message
            wp_send_json_error(['message' => __('No menu available for today.', 'daily-menu-manager')]);
        }

        $menu_items = $current_menu->getItems();

        wp_send_json_success([
            'menu' => $current_menu->toArray(),
            'items' => array_map(function ($item) { return $item->toArray(); }, $menu_items),
            'grouped_items' => $grouped_items,
        ]);
    }

    /**
     * AJAX handler for getting available quantities
     */
    public static function getAvailableQuantities()
    {
        check_ajax_referer('daily-menu-manager');

        $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
        if (!$menu_id) {
            wp_send_json_error(['message' => __('No menu ID provided', 'daily-menu-manager')]);
        }

        $quantities = self::$menu_service->getAvailableQuantities($menu_id);

        wp_send_json_success(['quantities' => $quantities]);
    }

    /**
     * AJAX handler for saving menu data
     */
    public static function handleSaveMenuData()
    {
        check_ajax_referer('daily-menu-manager');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            wp_send_json_error(['message' => __('Invalid data received.', 'daily-menu-manager')]);
        }

        try {
            $result = self::saveMenuFromPost($data);
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            } else {
                wp_send_json_success([
                    'message' => __('Menu saved successfully.', 'daily-menu-manager'),
                    'menu_id' => $result,
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Renders a single menu item in the admin area
     *
     * @param MenuItem|null $item The menu item to render
     */
    public static function renderMenuItem($item = null)
    {
        // For new items, create an empty item
        if ($item === null) {
            $item = new MenuItem([
                'id' => 0,
                'item_type' => '',
                'title' => '',
                'description' => '',
                'price' => 0,
                'available_quantity' => 0,
                'properties' => [],
                'allergens' => '',
                'sort_order' => 0,
                'image_id' => null,
                'image_url' => null,
            ]);
        }

        // Get the item configuration based on the type
        $item_config = self::getMenuTypeConfig($item->item_type);
        $is_collapsed = isset($_COOKIE['menu_item_' . $item->id . '_collapsed']) && $_COOKIE['menu_item_' . $item->id . '_collapsed'] === 'true';
        $collapse_class = $is_collapsed ? 'collapsed' : '';

        require DMM_PLUGIN_DIR . 'includes/Views/menu-item-template.php';
    }

    /**
     * Gets the configuration for a specific menu type
     *
     * @param string $type The menu type (e.g. 'appetizer', 'main_course', 'dessert')
     * @return array The configuration for the menu type
     */
    private static function getMenuTypeConfig($type)
    {
        $menu_types = \DailyMenuManager\Controller\Admin\SettingsController::getMenuTypes();

        // If the type exists, return its configuration
        if (isset($menu_types[$type])) {
            return $menu_types[$type];
        }

        // Fallback for unknown types
        return [
            'label' => ucfirst(str_replace('_', ' ', $type)),
            'plural' => ucfirst(str_replace('_', ' ', $type)),
            'enabled' => false,
        ];
    }
}
