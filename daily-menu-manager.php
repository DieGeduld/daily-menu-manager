<?php
/**
 * Plugin Name: Tages-Menü Manager
 * Description: Ermöglicht das Verwalten von Tagesmenüs und deren Bestellungen
 * Version: 1.1
 * Author: Your Name
 */

// Sicherheitscheck
defined('ABSPATH') or die('Direkter Zugriff nicht erlaubt!');

class DailyMenuManager {
    private static $instance = null;
    private $menu_types = array(
        'appetizer' => 'Vorspeise',
        'main_course' => 'Hauptgang',
        'dessert' => 'Nachspeise'
    );

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('wp_ajax_submit_order', array($this, 'handleOrder'));
        add_action('wp_ajax_nopriv_submit_order', array($this, 'handleOrder'));
    }

    public function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Haupttabelle für Tagesmenüs
        $table_name = $wpdb->prefix . 'daily_menus';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            menu_date date NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabelle für Menüeinträge
        $menu_items_table = $wpdb->prefix . 'menu_items';
        $sql .= "CREATE TABLE IF NOT EXISTS $menu_items_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            menu_id mediumint(9) NOT NULL,
            item_type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL,
            sort_order int NOT NULL,
            PRIMARY KEY  (id),
            KEY menu_id (menu_id)
        ) $charset_collate;";

        // Tabelle für Bestellungen
        $orders_table = $wpdb->prefix . 'menu_orders';
        $sql .= "CREATE TABLE IF NOT EXISTS $orders_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            menu_id mediumint(9) NOT NULL,
            menu_item_id mediumint(9) NOT NULL,
            customer_name varchar(100) NOT NULL,
            order_date datetime NOT NULL,
            notes text,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueueAdminScripts($hook) {
        if ('toplevel_page_daily-menu-manager' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'daily-menu-admin',
            plugins_url('js/admin.js', __FILE__),
            array('jquery', 'jquery-ui-sortable'),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'daily-menu-admin-style',
            plugins_url('css/admin.css', __FILE__)
        );

        // Inline JavaScript für die Menü-Verwaltung
        wp_add_inline_script('daily-menu-admin', "
            jQuery(document).ready(function($) {
                $('.add-menu-item').on('click', function() {
                    var type = $(this).data('type');
                    var template = $('#menu-item-template-' + type).html();
                    var nextId = $('.menu-items').children().length + 1;
                    template = template.replace(/\{id\}/g, nextId);
                    $('.menu-items').append(template);
                });

                $('.menu-items').sortable({
                    handle: '.move-handle',
                    update: function() {
                        updateSortOrder();
                    }
                });

                $(document).on('click', '.remove-menu-item', function() {
                    $(this).closest('.menu-item').remove();
                    updateSortOrder();
                });

                function updateSortOrder() {
                    $('.menu-item').each(function(index) {
                        $(this).find('.sort-order').val(index + 1);
                    });
                }
            });
        ");
    }

    public function addAdminMenu() {
        add_menu_page(
            'Tages-Menü Manager',
            'Tages-Menü',
            'manage_options',
            'daily-menu-manager',
            array($this, 'displayMenuPage'),
            'dashicons-food',
            6
        );

        add_submenu_page(
            'daily-menu-manager',
            'Bestellungen',
            'Bestellungen',
            'manage_options',
            'daily-menu-orders',
            array($this, 'displayOrdersPage')
        );
    }

    public function displayMenuPage() {
        if (isset($_POST['save_menu'])) {
            $this->saveMenu($_POST);
        }

        $current_menu = $this->getCurrentMenu();
        $menu_items = $this->getMenuItems($current_menu ? $current_menu->id : 0);
        
        ?>
        <div class="wrap">
            <h1>Tages-Menü eingeben</h1>
            <form method="post" action="" class="menu-form">
                <div class="menu-controls">
                    <?php foreach ($this->menu_types as $type => $label): ?>
                        <button type="button" class="button add-menu-item" data-type="<?php echo esc_attr($type); ?>">
                            + <?php echo esc_html($label); ?> hinzufügen
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="menu-items">
                    <?php
                    if ($menu_items) {
                        foreach ($menu_items as $item) {
                            $this->renderMenuItem($item);
                        }
                    }
                    ?>
                </div>

                <?php foreach ($this->menu_types as $type => $label): ?>
                    <script type="text/template" id="menu-item-template-<?php echo esc_attr($type); ?>">
                        <?php
                        $template_item = (object)array(
                            'id' => '{id}',
                            'item_type' => $type,
                            'title' => '',
                            'description' => '',
                            'price' => '',
                            'sort_order' => '{id}'
                        );
                        $this->renderMenuItem($template_item);
                        ?>
                    </script>
                <?php endforeach; ?>

                <?php submit_button('Menü speichern', 'primary', 'save_menu'); ?>
            </form>
        </div>
        <?php
    }

    private function renderMenuItem($item) {
        ?>
        <div class="menu-item" data-type="<?php echo esc_attr($item->item_type); ?>">
            <div class="menu-item-header">
                <span class="move-handle dashicons dashicons-move"></span>
                <span class="menu-item-title"><?php echo esc_html($this->menu_types[$item->item_type]); ?></span>
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
                    <label>Titel</label>
                    <input type="text" name="menu_items[<?php echo esc_attr($item->id); ?>][title]" 
                           value="<?php echo esc_attr($item->title); ?>" required>
                </div>
                
                <div class="menu-item-field">
                    <label>Beschreibung</label>
                    <textarea name="menu_items[<?php echo esc_attr($item->id); ?>][description]"><?php 
                        echo esc_textarea($item->description); 
                    ?></textarea>
                </div>
                
                <div class="menu-item-field">
                    <label>Preis (€)</label>
                    <input type="number" step="0.01" name="menu_items[<?php echo esc_attr($item->id); ?>][price]" 
                           value="<?php echo esc_attr($item->price); ?>" required>
                </div>
            </div>
        </div>
        <?php
    }

    private function saveMenu($post_data) {
        global $wpdb;
        
        // Speichern oder Aktualisieren des Hauptmenü-Eintrags
        $menu_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}daily_menus WHERE menu_date = %s",
            current_time('Y-m-d')
        ));

        if (!$menu_id) {
            $wpdb->insert(
                $wpdb->prefix . 'daily_menus',
                array('menu_date' => current_time('Y-m-d')),
                array('%s')
            );
            $menu_id = $wpdb->insert_id;
        }

        // Bestehende Menüeinträge löschen
        $wpdb->delete(
            $wpdb->prefix . 'menu_items',
            array('menu_id' => $menu_id),
            array('%d')
        );

        // Neue Menüeinträge speichern
        if (isset($post_data['menu_items'])) {
            foreach ($post_data['menu_items'] as $item_data) {
                $wpdb->insert(
                    $wpdb->prefix . 'menu_items',
                    array(
                        'menu_id' => $menu_id,
                        'item_type' => sanitize_text_field($item_data['type']),
                        'title' => sanitize_text_field($item_data['title']),
                        'description' => sanitize_textarea_field($item_data['description']),
                        'price' => floatval($item_data['price']),
                        'sort_order' => intval($item_data['sort_order'])
                    ),
                    array('%d', '%s', '%s', '%s', '%f', '%d')
                );
            }
        }
    }

    private function getCurrentMenu() {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}daily_menus WHERE menu_date = %s",
            current_time('Y-m-d')
        ));
    }

    private function getMenuItems($menu_id) {
        if (!$menu_id) return array();
        
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}menu_items 
            WHERE menu_id = %d 
            ORDER BY sort_order ASC",
            $menu_id
        ));
    }

    public function displayOrdersPage() {
        global $wpdb;
        
        $orders = $wpdb->get_results("
            SELECT o.*, mi.title as menu_item_title, mi.price 
            FROM {$wpdb->prefix}menu_orders o
            JOIN {$wpdb->prefix}menu_items mi ON o.menu_item_id = mi.id
            ORDER BY o.order_date DESC
        ");

        ?>
        <div class="wrap">
            <h1>Bestellungen</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Name</th>
                        <th>Bestelltes Gericht</th>
                        <th>Preis</th>
                        <th>Notizen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo esc_html(date('d.m.Y H:i', strtotime($order->order_date))); ?></td>
                        <td><?php echo esc_html($order->customer_name); ?></td>
                        <td><?php echo esc_html($order->menu_item_title); ?></td>
                        <td><?php echo number_format($order->price, 2); ?> €</td>
                        <td><?php echo esc_html($order->notes); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function displayDailyMenu() {
        $menu = $this->getCurrentMenu();
        if (!$menu) {
            return '<p>Heute ist kein Menü verfügbar.</p>';
        }

        $menu_items = $this->getMenuItems($menu->id);
        if (empty($menu_items)) {
            return '<p>Heute sind keine Menüeinträge verfügbar.</p>';
        }

        ob_start();
        ?>
        <div class="daily-menu">
            <h2>Heutiges Menü</h2>
            
            <?php foreach ($this->menu_types as $type => $label): 
                $type_items = array_filter($menu_items, function($item) use ($type) {
                    return $item->item_type === $type;
                });
                
                if (!empty($type_items)):
            ?>
                <div class="menu-section">
                    <h3><?php echo esc_html($label); ?></h3>
                    <?php foreach ($type_items as $item): ?>
                        <div class="menu-item">
                            <h4><?php echo esc_html($item->title); ?> - <?php echo number_format($item->price, 2); ?> €</h4>
                            <?php if ($item->description): ?>
                                <p><?php echo nl2br(esc_html($item->description)); ?></p>
                            <?php endif; ?>
                            <button class="order-button" data-item-id="<?php echo esc_attr($item->id); ?>">
                                Bestellen</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; 
            endforeach; ?>

            <div id="order-form" class="order-form" style="display: none;">
                <h3>Ihre Bestellung</h3>
                <form id="menu-order-form">
                    <input type="hidden" name="menu_id" value="<?php echo esc_attr($menu->id); ?>">
                    <input type="hidden" name="menu_item_id" id="selected-item-id" value="">
                    <div class="form-field">
                        <label for="customer_name">Ihr Name</label>
                        <input type="text" name="customer_name" id="customer_name" required>
                    </div>
                    <div class="form-field">
                        <label for="notes">Anmerkungen zur Bestellung</label>
                        <textarea name="notes" id="notes"></textarea>
                    </div>
                    <?php wp_nonce_field('menu_order_nonce'); ?>
                    <button type="submit">Bestellung absenden</button>
                </form>
            </div>
        </div>

        <style>
            .daily-menu {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            .menu-section {
                margin-bottom: 30px;
            }
            .menu-item {
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 5px;
            }
            .menu-item h4 {
                margin: 0 0 10px 0;
            }
            .menu-item p {
                margin: 0 0 10px 0;
            }
            .order-button {
                background: #0073aa;
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 3px;
                cursor: pointer;
            }
            .order-form {
                margin-top: 20px;
                padding: 20px;
                background: #f1f1f1;
                border-radius: 5px;
            }
            .form-field {
                margin-bottom: 15px;
            }
            .form-field label {
                display: block;
                margin-bottom: 5px;
            }
            .form-field input,
            .form-field textarea {
                width: 100%;
                padding: 8px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.order-button').on('click', function() {
                var itemId = $(this).data('item-id');
                $('#selected-item-id').val(itemId);
                $('#order-form').slideDown();
            });

            $('#menu-order-form').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: $(this).serialize() + '&action=submit_order',
                    success: function(response) {
                        if (response.success) {
                            alert('Ihre Bestellung wurde erfolgreich aufgenommen!');
                            $('#order-form').slideUp();
                            $('#menu-order-form')[0].reset();
                        } else {
                            alert('Es gab einen Fehler bei der Bestellung. Bitte versuchen Sie es erneut.');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handleOrder() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'menu_order_nonce')) {
            wp_send_json_error('Ungültiger Sicherheitstoken');
        }

        global $wpdb;
        
        $menu_id = intval($_POST['menu_id']);
        $menu_item_id = intval($_POST['menu_item_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $wpdb->insert(
            $wpdb->prefix . 'menu_orders',
            array(
                'menu_id' => $menu_id,
                'menu_item_id' => $menu_item_id,
                'customer_name' => $customer_name,
                'order_date' => current_time('mysql'),
                'notes' => $notes
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );

        wp_send_json_success('Bestellung erfolgreich aufgenommen');
    }
}

// Plugin initialisieren
DailyMenuManager::getInstance();