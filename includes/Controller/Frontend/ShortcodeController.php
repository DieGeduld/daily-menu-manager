<?php
namespace DailyMenuManager\Controller\Frontend;

use DailyMenuManager\Controller\Admin\SettingsController;
use DailyMenuManager\Models\Settings;
use DailyMenuManager\Models\Menu;

class ShortcodeController {
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        add_shortcode('daily_menu', [self::class, 'renderMenu']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        
    }

    /**
     * Lädt die benötigten CSS und JavaScript Dateien
     */
    public static function enqueueAssets() {
        // CSS laden
        wp_enqueue_style(
            'daily-menu-frontend',
            DMM_PLUGIN_URL . 'assets/dist/frontend.css',
            [],
            DMM_VERSION
        );

        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );
    
        // SweetAlert2 CSS
        wp_enqueue_style(
            'sweetalert2',
            'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.min.css',
            [],
            '11.10.5'
        );
    
        // SweetAlert2 JS
        wp_register_script(
            'sweetalert2',
            'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.5/sweetalert2.all.min.js',
            [],
            '11.10.5',
            true
        );
        wp_enqueue_script('sweetalert2');
    
        // Frontend JavaScript laden
        wp_enqueue_script(
            'daily-menu-frontend',
            DMM_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery', 'sweetalert2'],  // sweetalert2 als Abhängigkeit hinzugefügt
            DMM_VERSION,
            true
        );
    
        // AJAX URL und Nonce für JavaScript verfügbar machen
        wp_localize_script('daily-menu-frontend', 'dailyMenuAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('daily_menu_nonce'),
            'messages' => [
                'orderSuccess' => __('Your order has been successfully placed!', 'daily-menu-manager'),
                'orderError' => __('There was an error placing your order. Please try again.', 'daily-menu-manager'),
                'emptyOrder' => __('Please select at least one dish.', 'daily-menu-manager'),
                'requiredFields' => __('Please fill out all required fields.', 'daily-menu-manager')
            ], 
            'timeFormat' => SettingsController::getTimeFormat(),
            'priceFormat' => SettingsController::getPriceFormat(),
            'currencySymbol' => SettingsController::getCurrencySymbol(),
        ]);



    }

    /**
     * Rendert das Tagesmenü im Frontend
     * 
     * @param array $atts Shortcode Attribute
     * @return string HTML Output
     */
    public static function renderMenu($atts = []) {
        // Shortcode Attribute mit Standardwerten
        $atts = shortcode_atts([
            'date' => current_time('Y-m-d'),
            'show_order_form' => true,
            'title' => __('Today\'s Menu', 'daily-menu-manager')
        ], $atts, 'daily_menu');

        // Hole das Menü
        $menu = new Menu();
        $current_menu = $menu->getMenuForDate($atts['date']);
        
        if (!$current_menu) {
            return '<p class="no-menu">' . __('No menu available for today.', 'daily-menu-manager') . '</p>';
        }
        
        $menu_items = $menu->getMenuItems($current_menu->id);
        if (empty($menu_items)) {
            return '<p class="no-menu">' . __('No menu items available for today.', 'daily-menu-manager') . '</p>';
        }

        // Output Buffer starten
        ob_start();
        ?>
        <div class="daily-menu-manager">
            <h2><?php echo esc_html($atts['title']); ?> - <?php echo date_i18n('d. F Y', strtotime($atts['date'])); ?></h2>

            <?php if ($atts['show_order_form']): ?>
            <form id="menu-order-form" class="menu-order-form">
                <div class="menu-layout">
                    <!-- Linke Spalte: Menü-Items -->
                    <div class="menu-items-column">
                        <input type="hidden" name="menu_id" value="<?php echo esc_attr($current_menu->id); ?>">
                        <?php wp_nonce_field('menu_order_nonce'); ?>
                        
                        <?php
                        // Gruppiere Items nach Typ
                        $grouped_items = [];
                        foreach ($menu_items as $item) {
                            if (!isset($grouped_items[$item->item_type])) {
                                $grouped_items[$item->item_type] = [];
                            }
                            $grouped_items[$item->item_type][] = $item;
                        }
                        
                        // Zeige Items nach Typ gruppiert
                        foreach ($grouped_items as $type => $items): 
                            $type_label = self::getTypeLabelPlural($type);
                        ?>
                            <div class="menu-section menu-section-<?php echo esc_attr($type); ?>">
                                <h3><?php echo esc_html($type_label); ?></h3>
                                
                                <?php foreach ($items as $item): ?>
                                    <?php $props = array_keys($item->properties ?? []); ?>
                                    <div class="menu-item" data-item-available_quantity="<?php echo esc_attr($item->available_quantity); ?>" data-item-id="<?php echo esc_attr($item->id); ?>">
                                        <div class="menu-item-header">
                                            <?php if ($item->available_quantity == 0): ?>
                                                <span class="menu-item-title unavailable"><?php echo esc_html($item->title); ?> (<?php esc_html_e('out of stock', 'daily-menu-manager'); ?>)</span>
                                            <?php else: ?>
                                                <span class="menu-item-title"><?php echo esc_html($item->title); ?> (<?php echo sprintf(__('%dx available', 'daily-menu-manager'), esc_html($item->available_quantity)); ?>)</span>
                                            <?php endif; ?>
                                            <span class="menu-item-price"><?php echo SettingsController::formatPrice($item->price); ?></span>
                                        </div>
                                        <?php
                                            $main_color = SettingsController::getMainColor();
                                            foreach ($props as &$prop) {
                                                echo "<div style=\"background-color: $main_color;\" class=\"badge text-decoration-none me-1 mb-1\">" . __($prop, 'daily-menu-manager') . "</div>";
                                            }
                                        ?>
                                        <div class="menu-item-footer">
                                            <p class="menu-item-description">
                                                <?php if ($item->description): ?>
                                                    <?php echo nl2br(esc_html($item->description)); ?>
                                                <?php endif; ?>
                                            </p>
                                            
                                            <?php if ($atts['show_order_form']): ?>
                                                <div class="menu-item-order">
                                                    <div class="quantity-control">
                                                        <label for="quantity_<?php echo esc_attr($item->id); ?>">
                                                            <?php _e('Quantity:', 'daily-menu-manager'); ?>
                                                        </label>
                                                        <button type="button" style="background: <?php echo SettingsController::getMainColor(); ?>" class="quantity-btn minus">-</button>
                                                        <input type="number" 
                                                            class="quantity-input"
                                                            name="items[<?php echo esc_attr($item->id); ?>][quantity]" 
                                                            id="quantity_<?php echo esc_attr($item->id); ?>"
                                                            min="0" 
                                                            max="<?php echo esc_attr($item->available_quantity); ?>"
                                                            value="0"
                                                            data-price="<?php echo esc_attr($item->price); ?>">
                                                        <button type="button" style="background: <?php echo SettingsController::getMainColor(); ?>" class="quantity-btn plus">+</button>
                                                    </div>
                                                        
                                                   
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-notes">
                                            <label for="notes_<?php echo esc_attr($item->id); ?>">
                                                <?php _e('Notes:', 'daily-menu-manager'); ?>
                                            </label>
                                            <input type="text" 
                                                name="items[<?php echo esc_attr($item->id); ?>][notes]" 
                                                id="notes_<?php echo esc_attr($item->id); ?>"
                                                placeholder="<?php _e('e.g. without onions', 'daily-menu-manager'); ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Rechte Spalte: Bestellinfos -->
                    <div class="order-info-column">
                        <div class="order-summary">
                            <h3><?php _e('Order Summary', 'daily-menu-manager'); ?></h3>
                            <div class="order-total">
                                <?php _e('Total:', 'daily-menu-manager'); ?> 
                                <span id="total-amount"><?php echo SettingsController::formatPrice(0); ?></span>
                            </div>
                        </div>

                        <div class="customer-info">
                            <div class="form-field">
                                <label for="customer_name">
                                    <?php _e('Name', 'daily-menu-manager'); ?>*
                                </label>
                                <input type="text" name="customer_name" id="customer_name" required>
                            </div>
                            <div class="form-field">
                                <label for="customer_phone">
                                    <?php _e('Phone Number', 'daily-menu-manager'); ?>
                                    <?php _e('(for possible inquiries)', 'daily-menu-manager'); ?>
                                </label>
                                <input type="tel" 
                                    name="customer_phone" 
                                    id="customer_phone" 
                                    pattern="[0-9\s\+\-()]+"
                                    placeholder="<?php _e('e.g. (555) 123-4567', 'daily-menu-manager'); ?>">
                            </div>

                            <div class="form-field">
                                <label for="consumption_type">
                                    <?php _e('Pick up or eat in', 'daily-menu-manager'); ?>*
                                </label>
                                <select name="consumption_type" id="consumption_type" required>
                                    <!-- TODO: Let user choose -->
                                    <option value=""><?php _e('Please choose', 'daily-menu-manager'); ?></option>
                                    <?php foreach (SettingsController::getConsumptionTypes() as $type): ?>
                                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="pickup_time">
                                    <?php _e('Pickup Time', 'daily-menu-manager'); ?>*
                                <select name="pickup_time" id="pickup_time" required>
                                    <option value=""><?php _e('Please choose', 'daily-menu-manager'); ?></option>
                                    <?php
                                    $timeFormat = SettingsController::getTimeFormat();
                                    foreach (self::getAvailablePickupTimes() as $time) {
                                        $timeFormatted = SettingsController::formatTime($time);
                                        printf(
                                            '<option value="%s">%s</option>',
                                            esc_attr($time),
                                            esc_html($timeFormatted)
                                        );
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="general_notes">
                                    <?php _e('Order notes', 'daily-menu-manager'); ?>
                                </label>
                                <textarea name="general_notes" id="general_notes"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="submit-order" style="background-color: <?php echo SettingsController::getMainColor(); ?>;">
                            <?php _e('Place Order', 'daily-menu-manager'); ?>
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }


    /**
     * Hilfsfunktion: Holt das Label für einen Menütyp
     */
    private static function getTypeLabel($type) {
        $types = [
            'appetizer' => __('Appetizer', 'daily-menu-manager'),
            'main_course' => __('Main Course', 'daily-menu-manager'),
            'dessert' => __('Dessert', 'daily-menu-manager')
        ];
        
        return isset($types[$type]) ? $types[$type] : ucfirst($type);
    }
    private static function getTypeLabelPlural($type) {
        $types = [
            'appetizer' => __('Appetizers', 'daily-menu-manager'),
            'main_course' => __('Main Courses', 'daily-menu-manager'),
            'dessert' => __('Desserts', 'daily-menu-manager')
        ];
        
        return isset($types[$type]) ? $types[$type] : ucfirst($type);
    }

/**
     * Gets available pickup times based on settings
     */
    private static function getAvailablePickupTimes(): array {
        $settings = Settings::getInstance();
        $order_times = $settings->get('order_times', [
            'start_time' => '11:00',
            'end_time' => '16:00',
            'interval' => 30
        ]);

        $start = strtotime($order_times['start_time']);
        $end = strtotime($order_times['end_time']);
        $interval = intval($order_times['interval']) * 60; // Convert minutes to seconds
        
        $times = [];
        for ($time = $start; $time <= $end; $time += $interval) {
            $times[] = date('H:i', $time);
        }
        return $times;
    }
}