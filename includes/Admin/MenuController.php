<?php
namespace DailyMenuManager\Admin;

use DailyMenuManager\Models\Menu;

class MenuController {
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        add_action('admin_menu', [self::class, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminScripts']);
        add_action('wp_ajax_save_menu_order', [self::class, 'handleSaveMenuOrder']);
        add_action('wp_ajax_copy_menu', [self::class, 'handleCopyMenu']);
    }

    /**
     * Fügt Menüeinträge zum WordPress Admin hinzu
     */
    public static function addAdminMenu() {
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
     * Lädt Admin Assets
     */
    public static function enqueueAdminScripts($hook) {
        if ('daily-menu_page_daily-menu-orders' !== $hook && 'toplevel_page_daily-menu-manager' !== $hook) {
            return;
        }
    
        // jQuery UI Components
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-dialog');
        
        // jQuery UI Styles
        wp_enqueue_style(
            'jquery-ui-style',
            '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css'
        );
    
        // Plugin Admin Scripts
        wp_enqueue_script(
            'daily-menu-admin',
            plugins_url('assets/js/admin.js', dirname(__DIR__)),
            ['jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-dialog'],
            DMM_VERSION,
            true
        );
    
        // Admin Styles
        wp_enqueue_style(
            'daily-menu-admin-style',
            plugins_url('assets/css/admin.css', dirname(__DIR__)),
            [],
            DMM_VERSION
        );

        // Flatpickr CSS
        wp_enqueue_style(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );

        
        // Flatpickr Light Theme
        // wp_enqueue_style(
        //     'flatpickr-light',
        //     'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/light.css',
        //     [],
        //     '4.6.13'
        // );
        
        // Flatpickr JS
        wp_enqueue_script(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );
    
        // Lokalisierung - WICHTIG: Muss nach dem Enqueue des Scripts erfolgen
        wp_localize_script(
            'daily-menu-admin',
            'dailyMenuAdmin',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('daily_menu_admin_nonce'),
                'messages' => [
                    'copySuccess' => __('Menu was copied successfully!', 'daily-menu-manager'),
                    'copyError' => __('Error copying menu.', 'daily-menu-manager'),
                    'saveSuccess' => __('Menu was saved!', 'daily-menu-manager'),
                    'saveError' => __('Error saving menu.', 'daily-menu-manager'),
                    'deleteConfirm' => __('Are you sure you want to delete this menu item?', 'daily-menu-manager'),
                    'selectDate' => __('Please select a date.', 'daily-menu-manager'),
                    'noItems' => __('Please add at least one menu item.', 'daily-menu-manager'),
                    'requiredFields' => __('Please fill in all required fields.', 'daily-menu-manager'),
                    'copy' => __('Copy', 'daily-menu-manager'),
                    'cancel' => __('Cancel', 'daily-menu-manager')
                ],
                'menus' => Menu::getMenuDates(),
            ]
        );
    }

    /**
     * Zeigt die Hauptseite des Menü-Managers
     */
    public static function displayMenuPage() {
        $menu_model = new \DailyMenuManager\Models\Menu();
        
        // Speichern des Menüs wenn das Formular abgeschickt wurde
        if (isset($_POST['save_menu']) && check_admin_referer('save_menu_nonce')) {
            $result = $menu_model->saveMenu($_POST);
            if (is_wp_error($result)) {
                add_settings_error(
                    'daily_menu_manager',
                    'save_error',
                    $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'daily_menu_manager',
                    'save_success',
                    __('Menü erfolgreich gespeichert.', 'daily-menu-manager'),
                    'success'
                );
            }
        }
    
        // Hole das ausgewählte Datum oder setze das aktuelle Datum
        $selected_date = isset($_GET['menu_date']) ? sanitize_text_field($_GET['menu_date']) : current_time('Y-m-d');
        
        // Hole das aktuelle Menü
        $current_menu = $menu_model->getMenuForDate($selected_date);
        $menu_items = $current_menu ? $menu_model->getMenuItems($current_menu->id) : [];
    
        // Template laden
        require_once DMM_PLUGIN_DIR . 'includes/Views/admin-menu-page.php';
    }

    /**
     * AJAX Handler für die Sortierung der Menüeinträge
     */
    public static function handleSaveMenuOrder() {
        check_ajax_referer('daily_menu_admin_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No permission.', 'daily-menu-manager')]);
        }

        $item_order = $_POST['item_order'] ?? [];
        if (empty($item_order)) {
            wp_send_json_error(['message' => __('No order information received.', 'daily-menu-manager')]);
        }

        $menu = new Menu();
        $result = $menu->updateItemOrder($item_order);

        if ($result) {
            wp_send_json_success(['message' => __('Order updated.', 'daily-menu-manager')]);
        } else {
            wp_send_json_error(['message' => __('Error updating order.', 'daily-menu-manager')]);
        }
    }

    /**
     * AJAX Handler für das Kopieren eines Menüs
     */
    public static function handleCopyMenu() {
        check_ajax_referer('daily_menu_admin_nonce');
        
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

        $menu = new Menu();
        if ($type == "from") {
            $items = $menu->getMenuForDate($selectedDate);

            if (!$items) {
                wp_send_json_error(['message' => __('No menu exists for this date.', 'daily-menu-manager')]);
                exit();
            } 

            $result = $menu->copyMenu(intval($items->id), $currentDate);

            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            } else {
                wp_send_json_success([
                    'message' => __('Menu copied successfully.', 'daily-menu-manager'),
                    'new_menu_id' => $result
                ]);
            } 
        } else if ($type == "to") {

            if (!$currentDate || !$menu_id) {
                wp_send_json_error(['message' => __('Invalid parameters, menu ID missing', 'daily-menu-manager')]);
            }

            $result = $menu->copyMenu(intval($menu_id), $selectedDate);

            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            } else {
                wp_send_json_success([
                    'message' => __('Menu copied successfully.', 'daily-menu-manager'),
                    'new_menu_id' => $result
                ]);
            } 
        } else {
            wp_send_json_error(['message' => __('Invalid parameters.', 'daily-menu-manager')]);
        }

        $menu = new Menu();
        $items = $menu->getMenuForDate($selectedDate);
        
        // Check if a menu already exists for the target date
        if ($menu->menuExists($currentDate)) {
            wp_send_json_error(['message' => __('A menu already exists for this date.', 'daily-menu-manager')]);
        }

        $result = $menu->copyMenu($menu_id, $currentDate);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success([
                'message' => __('Menu copied successfully.', 'daily-menu-manager'),
                'new_menu_id' => $result
            ]);
        }
    }

    /**
     * Hilfsmethode: Holt die verfügbaren Menütypen
     */
    private static function getMenuTypes() {
        return [
            'appetizer' => [
                'label' => __('Vorspeise', 'daily-menu-manager'),
                'label_de' => 'Vorspeise'
            ],
            'main_course' => [
                'label' => __('Hauptgang', 'daily-menu-manager'),
                'label_de' => 'Hauptgang'
            ],
            'dessert' => [
                'label' => __('Nachspeise', 'daily-menu-manager'),
                'label_de' => 'Nachspeise'
            ]
        ];
    }

    /**
     * Rendert ein einzelnes Menü-Item im Admin-Bereich
     */
    private static function renderMenuItem($item) {
        // Hole die Item-Konfiguration basierend auf dem Typ
        $item_config = self::getMenuTypeConfig($item->item_type);
        $is_collapsed = isset($_COOKIE['menu_item_' . $item->id . '_collapsed']) && $_COOKIE['menu_item_' . $item->id . '_collapsed'] === 'true';
        $collapse_class = $is_collapsed ? 'collapsed' : '';
        ?>
        <div class="menu-item <?php echo esc_attr($collapse_class); ?>" 
             data-type="<?php echo esc_attr($item->item_type); ?>"
             data-id="<?php echo esc_attr($item->id); ?>">
            
            <!-- Header Section -->
            <div class="menu-item-header">
                <!-- Left Controls -->
                <div class="menu-item-controls">
                    <span class="move-handle dashicons dashicons-move" 
                          title="<?php esc_attr_e('Drag to reorder', 'daily-menu-manager'); ?>"
                          aria-label="<?php esc_attr_e('Drag handle', 'daily-menu-manager'); ?>">
                    </span>
                    <button type="button" 
                            class="toggle-menu-item dashicons <?php echo $is_collapsed ? 'dashicons-arrow-right' : 'dashicons-arrow-down'; ?>"
                            aria-expanded="<?php echo $is_collapsed ? 'false' : 'true'; ?>"
                            aria-label="<?php esc_attr_e('Toggle menu item', 'daily-menu-manager'); ?>"
                            title="<?php esc_attr_e('Click to expand/collapse', 'daily-menu-manager'); ?>">
                    </button>
                </div>
    
                <!-- Title Area -->
                <div class="menu-item-title-area">
                    <span class="menu-item-type-label"><?php echo esc_html($item_config['label']); ?></span>
                    <span class="menu-item-title-preview"><?php echo esc_html($item->title ?: __('(No title)', 'daily-menu-manager')); ?></span>
                </div>
    
                <!-- Right Controls -->
                <div class="menu-item-actions">
                    <button type="button" 
                            class="duplicate-menu-item dashicons dashicons-admin-page"
                            title="<?php esc_attr_e('Duplicate item', 'daily-menu-manager'); ?>"
                            aria-label="<?php esc_attr_e('Duplicate this menu item', 'daily-menu-manager'); ?>">
                    </button>
                    <button type="button" 
                            class="remove-menu-item dashicons dashicons-trash"
                            title="<?php esc_attr_e('Delete item', 'daily-menu-manager'); ?>"
                            aria-label="<?php esc_attr_e('Delete this menu item', 'daily-menu-manager'); ?>">
                    </button>
                </div>
            </div>
    
            <!-- Content Section -->
            <div class="menu-item-content" <?php echo $is_collapsed ? 'style="display: none;"' : ''; ?>>
                <!-- Hidden Fields -->
                <input type="hidden" name="menu_items[<?php echo esc_attr($item->id); ?>][id]" 
                       value="<?php echo esc_attr($item->id); ?>">
                <input type="hidden" name="menu_items[<?php echo esc_attr($item->id); ?>][type]" 
                       value="<?php echo esc_attr($item->item_type); ?>">
                <input type="hidden" name="menu_items[<?php echo esc_attr($item->id); ?>][sort_order]" 
                       value="<?php echo esc_attr($item->sort_order); ?>" 
                       class="sort-order">
    
                <!-- Title Field -->
                <div class="menu-item-field">
                    <label for="title_<?php echo esc_attr($item->id); ?>">
                        <?php _e('Title', 'daily-menu-manager'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="title_<?php echo esc_attr($item->id); ?>"
                           name="menu_items[<?php echo esc_attr($item->id); ?>][title]"
                           value="<?php echo esc_attr($item->title); ?>"
                           required
                           class="menu-item-title-input"
                           data-original-value="<?php echo esc_attr($item->title); ?>">
                    <span class="field-description">
                        <?php _e('Enter the name of the dish or menu item', 'daily-menu-manager'); ?>
                    </span>
                </div>
    
                <!-- Description Field -->
                <div class="menu-item-field">
                    <label for="description_<?php echo esc_attr($item->id); ?>">
                        <?php _e('Description', 'daily-menu-manager'); ?>
                    </label>
                    <textarea id="description_<?php echo esc_attr($item->id); ?>"
                              name="menu_items[<?php echo esc_attr($item->id); ?>][description]"
                              class="menu-item-description"
                              rows="3"
                              data-original-value="<?php echo esc_attr($item->description); ?>"><?php 
                        echo esc_textarea($item->description); 
                    ?></textarea>
                    <span class="field-description">
                        <?php _e('Optional: Add ingredients or other details about this item', 'daily-menu-manager'); ?>
                    </span>
                </div>
    
                <!-- Price Field -->
                <div class="menu-item-field">
                    <label for="price_<?php echo esc_attr($item->id); ?>">
                        <?php _e('Price', 'daily-menu-manager'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="price-input-wrapper">
                        <span class="currency-symbol">€</span>
                        <input type="number" 
                            id="price_<?php echo esc_attr($item->id); ?>"
                            name="menu_items[<?php echo esc_attr($item->id); ?>][price]"
                            value="<?php echo esc_attr(number_format($item->price, 2, '.', '')); ?>"
                            step="0.01"
                            min="0"
                            required
                            class="menu-item-price">
                    </div>
                    <span class="field-description">
                        <?php _e('Enter the price for this item (e.g., 12,50)', 'daily-menu-manager'); ?>
                    </span>
                </div>

                <!-- Available Quantity Field -->
                <div class="menu-item-field">
                    <label for="available_quantity_<?php echo esc_attr($item->id); ?>">
                        <?php _e('Available Quantity', 'daily-menu-manager'); ?>
                    </label>
                    <input type="number" 
                        id="available_quantity_<?php echo esc_attr($item->id); ?>"
                        name="menu_items[<?php echo esc_attr($item->id); ?>][available_quantity]"
                        value="<?php echo esc_attr($item->available_quantity); ?>"
                        min="0"
                        class="menu-item-available-quantity">
                    <span class="field-description">
                        <?php _e('Enter the available quantity for this item', 'daily-menu-manager'); ?>
                    </span>
                </div>
    
                <!-- Additional Options Field -->
                <div class="menu-item-field">
                    <label for="options_<?php echo esc_attr($item->id); ?>">
                        <?php _e('Additional Options', 'daily-menu-manager'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=daily-menu-settings')); ?>"><?php _e('Manage additional options', 'daily-menu-manager'); ?></a>
                    </label>
                    <div class="options-grid">
                    <?php

                        $allProps = \DailyMenuManager\Admin\SettingsController::getMenuProperties() ?? [];

                        //$props = json_decode($item->properties ?? '{}', true) ?? [];
                        $props = $item->properties ?? [];
                        foreach ($allProps as $key => $prop): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                   name="menu_items[<?php echo esc_attr($item->id); ?>][properties][<?php echo $prop; ?>]"
                                   <?php checked(isset($props[$prop])); ?>>
                            <?php echo $prop; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
    
                <!-- Allergen Information Field -->
                <div class="menu-item-field">
                    <label for="allergens_<?php echo esc_attr($item->id); ?>">
                        <?php _e('Allergen Information', 'daily-menu-manager'); ?>
                    </label>
                    <textarea id="allergens_<?php echo esc_attr($item->id); ?>"
                              name="menu_items[<?php echo esc_attr($item->id); ?>][allergens]"
                              class="menu-item-allergens"
                              rows="2"><?php 
                        echo esc_textarea(isset($item->allergens) ? $item->allergens : ''); 
                    ?></textarea>
                    <span class="field-description">
                        <?php _e('List any allergens present in this dish', 'daily-menu-manager'); ?>
                    </span>
                </div>
    
                <!-- Advanced Settings (Initially Hidden) -->
                <div class="advanced-settings" style="display: none;">
                    <button type="button" class="toggle-advanced-settings">
                        <?php _e('Advanced Settings', 'daily-menu-manager'); ?>
                    </button>
                    <div class="advanced-settings-content">
                        <!-- Availability Times -->
                        <div class="menu-item-field">
                            <label>
                                <?php _e('Availability Times', 'daily-menu-manager'); ?>
                            </label>
                            <div class="time-range-inputs">
                                <input type="time" 
                                       name="menu_items[<?php echo esc_attr($item->id); ?>][available_from]"
                                       value="<?php echo esc_attr(isset($item->available_from) ? $item->available_from : ''); ?>">
                                <span>-</span>
                                <input type="time" 
                                       name="menu_items[<?php echo esc_attr($item->id); ?>][available_until]"
                                       value="<?php echo esc_attr(isset($item->available_until) ? $item->available_until : ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Holt die Konfiguration für einen bestimmten Menütyp
     * 
     * @param string $type Der Menütyp (z.B. 'appetizer', 'main_course', 'dessert')
     * @return array Die Konfiguration für den Menütyp
     */
    private static function getMenuTypeConfig($type) {
        $menu_types = self::getMenuTypes();
        
        // Wenn der Typ existiert, gib seine Konfiguration zurück
        if (isset($menu_types[$type])) {
            return $menu_types[$type];
        }
        
        // Fallback für unbekannte Typen
        return [
            'label' => ucfirst(str_replace('_', ' ', $type)),
            'label_de' => ucfirst(str_replace('_', ' ', $type))
        ];
    }

}



// Füge neue Menüeinträge hinzu
if (isset($menu_data['menu_items'])) {
    $sort_order = 1;
    foreach ($menu_data['menu_items'] as $item_data) {
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'menu_items',
            [
                'menu_id' => $menu_id,
                'item_type' => sanitize_text_field($item_data['type']),
                'title' => sanitize_text_field($item_data['title']),
                'description' => sanitize_textarea_field($item_data['description']),
                'price' => floatval($item_data['price']),
                'available_quantity' => intval($item_data['available_quantity']),
                'properties' => sanitize_text_field($item_data['properties']),
                'allergens' => sanitize_textarea_field($item_data['allergens']),
                'sort_order' => $sort_order++
            ],
            ['%d', '%s', '%s', '%s', '%f', '%d', '%s', '%s', '%d']
        );
        
        if ($inserted === false) {
            error_log($wpdb->last_error);
            throw new \Exception('Fehler beim Speichern der Menüeinträge.' . $wpdb->last_error);
        }
    }
}