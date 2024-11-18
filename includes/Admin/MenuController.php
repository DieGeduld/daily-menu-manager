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
        if ('toplevel_page_daily-menu-manager' !== $hook) {
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
    
        // Lokalisierung - WICHTIG: Muss nach dem Enqueue des Scripts erfolgen
        wp_localize_script(
            'daily-menu-admin',
            'dailyMenuAdmin',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('daily_menu_admin_nonce'),
                'messages' => [
                    'copySuccess' => __('Menü wurde erfolgreich kopiert!', 'daily-menu-manager'),
                    'copyError' => __('Fehler beim Kopieren des Menüs.', 'daily-menu-manager'),
                    'saveSuccess' => __('Menü wurde gespeichert!', 'daily-menu-manager'),
                    'saveError' => __('Fehler beim Speichern des Menüs.', 'daily-menu-manager'),
                    'deleteConfirm' => __('Möchten Sie dieses Menü-Item wirklich löschen?', 'daily-menu-manager'),
                    'selectDate' => __('Bitte wählen Sie ein Datum.', 'daily-menu-manager'),
                    'noItems' => __('Bitte fügen Sie mindestens ein Menü-Item hinzu.', 'daily-menu-manager'),
                    'requiredFields' => __('Bitte füllen Sie alle Pflichtfelder aus.', 'daily-menu-manager'),
                    'copy' => __('Kopieren', 'daily-menu-manager'),
                    'cancel' => __('Abbrechen', 'daily-menu-manager')
                ]
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
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'daily-menu-manager')]);
        }

        $item_order = $_POST['item_order'] ?? [];
        if (empty($item_order)) {
            wp_send_json_error(['message' => __('Keine Sortierinformationen erhalten.', 'daily-menu-manager')]);
        }

        $menu = new Menu();
        $result = $menu->updateItemOrder($item_order);

        if ($result) {
            wp_send_json_success(['message' => __('Reihenfolge aktualisiert.', 'daily-menu-manager')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Aktualisieren der Reihenfolge.', 'daily-menu-manager')]);
        }
    }

    /**
     * AJAX Handler für das Kopieren eines Menüs
     */
    public static function handleCopyMenu() {
        check_ajax_referer('daily_menu_admin_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'daily-menu-manager')]);
        }

        $menu_id = intval($_POST['menu_id']);
        $new_date = sanitize_text_field($_POST['new_date']);

        if (!$menu_id || !$new_date) {
            wp_send_json_error(['message' => __('Ungültige Parameter.', 'daily-menu-manager')]);
        }

        $menu = new Menu();
        
        // Prüfe ob bereits ein Menü für das Zieldatum existiert
        if ($menu->menuExists($new_date)) {
            wp_send_json_error(['message' => __('Für dieses Datum existiert bereits ein Menü.', 'daily-menu-manager')]);
        }

        $result = $menu->copyMenu($menu_id, $new_date);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success([
                'message' => __('Menü erfolgreich kopiert.', 'daily-menu-manager'),
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
        $menu_types = self::getMenuTypes();
        ?>
        <div class="menu-item" data-id="<?php echo esc_attr($item->id); ?>">
            <div class="menu-item-header">
                <span class="move-handle dashicons dashicons-move"></span>
                <span class="menu-item-title">
                    <?php echo esc_html($menu_types[$item->item_type]['label']); ?>
                </span>
                <button type="button" class="remove-menu-item button-link">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
            <div class="menu-item-content">
                <input type="hidden" name="menu_items[<?php echo esc_attr($item->id); ?>][type]" 
                       value="<?php echo esc_attr($item->item_type); ?>">
                <input type="hidden" name="menu_items[<?php echo esc_attr($item->id); ?>][sort_order]" 
                       value="<?php echo esc_attr($item->sort_order); ?>" class="sort-order">
                
                <div class="menu-item-field">
                    <label><?php _e('Title', 'daily-menu-manager'); ?></label>
                    <input type="text" 
                           name="menu_items[<?php echo esc_attr($item->id); ?>][title]" 
                           value="<?php echo esc_attr($item->title); ?>" 
                           required>
                </div>
                
                <div class="menu-item-field">
                    <label><?php _e('Description', 'daily-menu-manager'); ?></label>
                    <textarea name="menu_items[<?php echo esc_attr($item->id); ?>][description]"><?php 
                        echo esc_textarea($item->description); 
                    ?></textarea>
                </div>
                
                <div class="menu-item-field">
                    <label><?php _e('Price', 'daily-menu-manager'); ?> (€)</label>
                    <input type="number" 
                           step="0.01" 
                           name="menu_items[<?php echo esc_attr($item->id); ?>][price]" 
                           value="<?php echo esc_attr($item->price); ?>" 
                           required>
                </div>
            </div>
        </div>
        <?php
    }
}