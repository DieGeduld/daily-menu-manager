<?php
namespace DailyMenuManager\Frontend;

use DailyMenuManager\Models\Menu;

class ShortcodeController {
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        add_shortcode('daily_menu', [self::class, 'renderMenu']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('wp_ajax_submit_order', [self::class, 'handleOrder']);
        add_action('wp_ajax_nopriv_submit_order', [self::class, 'handleOrder']);
    }

    /**
     * Lädt die benötigten CSS und JavaScript Dateien
     */
    public static function enqueueAssets() {
        // CSS laden
        wp_enqueue_style(
            'daily-menu-frontend',
            plugins_url('assets/css/frontend.css', dirname(__DIR__)),
            [],
            DMM_VERSION
        );
    
        // JavaScript laden
        wp_enqueue_script(
            'daily-menu-frontend',
            plugins_url('assets/js/frontend.js', dirname(__DIR__)),
            ['jquery'],
            DMM_VERSION,
            true
        );
    
        // AJAX URL und Nonce für JavaScript verfügbar machen
        wp_localize_script('daily-menu-frontend', 'dailyMenuAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('daily_menu_nonce'),
            'messages' => [
                'orderSuccess' => __('Ihre Bestellung wurde erfolgreich aufgegeben!', 'daily-menu-manager'),
                'orderError' => __('Es gab einen Fehler bei der Bestellung. Bitte versuchen Sie es erneut.', 'daily-menu-manager'),
                'emptyOrder' => __('Bitte wählen Sie mindestens ein Gericht aus.', 'daily-menu-manager'),
                'requiredFields' => __('Bitte füllen Sie alle Pflichtfelder aus.', 'daily-menu-manager')
            ]
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
            'title' => __('Heutiges Menü', 'daily-menu-manager')
        ], $atts, 'daily_menu');

        // Hole das Menü
        $menu = new Menu();
        $current_menu = $menu->getMenuForDate($atts['date']);
        
        if (!$current_menu) {
            return '<p class="no-menu">' . __('Heute ist kein Menü verfügbar.', 'daily-menu-manager') . '</p>';
        }
        
        $menu_items = $menu->getMenuItems($current_menu->id);
        if (empty($menu_items)) {
            return '<p class="no-menu">' . __('Heute sind keine Menüeinträge verfügbar.', 'daily-menu-manager') . '</p>';
        }

        // Output Buffer starten
        ob_start();
        ?>
        <div class="daily-menu">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            
            <?php if ($atts['show_order_form']): ?>
            <form id="menu-order-form" class="menu-order-form">
                <input type="hidden" name="menu_id" value="<?php echo esc_attr($current_menu->id); ?>">
                <?php wp_nonce_field('menu_order_nonce'); ?>
            <?php endif; ?>
                
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
                $type_label = self::getTypeLabel($type);
            ?>
                <div class="menu-section menu-section-<?php echo esc_attr($type); ?>">
                    <h3><?php echo esc_html($type_label); ?></h3>
                    
                    <?php foreach ($items as $item): ?>
                        <div class="menu-item" data-item-id="<?php echo esc_attr($item->id); ?>">
                            <div class="menu-item-header">
                                <span class="menu-item-title"><?php echo esc_html($item->title); ?></span>
                                <span class="menu-item-price"><?php echo number_format($item->price, 2); ?> €</span>
                            </div>
                            
                            <?php if ($item->description): ?>
                                <p class="menu-item-description">
                                    <?php echo nl2br(esc_html($item->description)); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_order_form']): ?>
                                <div class="menu-item-order">
                                    <div class="quantity-control">
                                        <label for="quantity_<?php echo esc_attr($item->id); ?>">
                                            <?php _e('Anzahl:', 'daily-menu-manager'); ?>
                                        </label>
                                        <button type="button" class="quantity-btn minus">-</button>
                                        <input type="number" 
                                               class="quantity-input"
                                               name="items[<?php echo esc_attr($item->id); ?>][quantity]" 
                                               id="quantity_<?php echo esc_attr($item->id); ?>"
                                               min="0" 
                                               value="0"
                                               data-price="<?php echo esc_attr($item->price); ?>">
                                        <button type="button" class="quantity-btn plus">+</button>
                                    </div>
                                           
                                    <div class="item-notes">
                                        <label for="notes_<?php echo esc_attr($item->id); ?>">
                                            <?php _e('Anmerkungen:', 'daily-menu-manager'); ?>
                                        </label>
                                        <input type="text" 
                                               name="items[<?php echo esc_attr($item->id); ?>][notes]" 
                                               id="notes_<?php echo esc_attr($item->id); ?>"
                                               placeholder="<?php _e('z.B. ohne Zwiebeln', 'daily-menu-manager'); ?>">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($atts['show_order_form']): ?>
                <div class="order-summary">
                    <h3><?php _e('Bestellübersicht', 'daily-menu-manager'); ?></h3>
                    <div class="order-total">
                        <?php _e('Gesamtbetrag:', 'daily-menu-manager'); ?> 
                        <span id="total-amount">0,00 €</span>
                    </div>
                </div>

                <div class="customer-info">
                    <h3><?php _e('Ihre Informationen', 'daily-menu-manager'); ?></h3>
                    <div class="form-field">
                        <label for="customer_name">
                            <?php _e('Name', 'daily-menu-manager'); ?>*
                        </label>
                        <input type="text" name="customer_name" id="customer_name" required>
                    </div>
                    <div class="form-field">
                        <label for="general_notes">
                            <?php _e('Allgemeine Anmerkungen zur Bestellung', 'daily-menu-manager'); ?>
                        </label>
                        <textarea name="general_notes" id="general_notes"></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-order">
                    <?php _e('Bestellung aufgeben', 'daily-menu-manager'); ?>
                </button>
            </form>

            <div id="order-confirmation" class="order-confirmation" style="display: none;">
                <h3><?php _e('Bestellung erfolgreich aufgegeben!', 'daily-menu-manager'); ?></h3>
                <p><?php _e('Ihre Bestellnummer:', 'daily-menu-manager'); ?> <strong id="order-number"></strong></p>
                <p><?php _e('Bitte nennen Sie diese Nummer bei der Abholung.', 'daily-menu-manager'); ?></p>
                <div class="confirmation-details"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Verarbeitet eingehende Bestellungen via AJAX
     */
    public static function handleOrder() {
        check_ajax_referer('menu_order_nonce');
        
        if (empty($_POST['items'])) {
            wp_send_json_error(['message' => __('Keine Gerichte ausgewählt.', 'daily-menu-manager')]);
        }

        $order = new \DailyMenuManager\Models\Order();
        $result = $order->createOrder($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Hilfsfunktion: Holt das Label für einen Menütyp
     */
    private static function getTypeLabel($type) {
        $types = [
            'appetizer' => __('Vorspeise', 'daily-menu-manager'),
            'main_course' => __('Hauptgang', 'daily-menu-manager'),
            'dessert' => __('Nachspeise', 'daily-menu-manager')
        ];
        
        return isset($types[$type]) ? $types[$type] : ucfirst($type);
    }
}