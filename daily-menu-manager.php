<?php
/**
 * Plugin Name: Tages-Menü Manager
 * Description: Ermöglicht das Verwalten von Tagesmenüs und deren Bestellungen
 * Version: 1.1
 * Author: Your Name
 */

// Sicherheitscheck
defined('ABSPATH') or die('Direkter Zugriff nicht erlaubt!');


function activate_daily_menu_manager() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = array();

    // Haupttabelle für Tagesmenüs
    $table_name = $wpdb->prefix . 'daily_menus';
    $sql[] = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        menu_date date NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Tabelle für Menüeinträge
    $menu_items_table = $wpdb->prefix . 'menu_items';
    $sql[] = "CREATE TABLE IF NOT EXISTS $menu_items_table (
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

    // AKTUALISIERTE Tabelle für Bestellungen
    $orders_table = $wpdb->prefix . 'menu_orders';
    $sql[] = "CREATE TABLE IF NOT EXISTS $orders_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        menu_id mediumint(9) NOT NULL,
        menu_item_id mediumint(9) NOT NULL,
        order_number varchar(50) NOT NULL,
        customer_name varchar(100) NOT NULL,
        quantity int NOT NULL DEFAULT 1,
        notes text,
        general_notes text,
        order_date datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY order_number (order_number)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Jede Tabelle einzeln erstellen
    foreach($sql as $query) {
        dbDelta($query);
    }

    // Option setzen um zu prüfen, ob Tabellen erstellt wurden
    update_option('daily_menu_manager_db_version', '1.1');
}

// Deaktivierungsfunktion
function deactivate_daily_menu_manager() {
    // drop
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}daily_menus");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_items");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}menu_orders");
}



class DailyMenuManager {
    private static $instance = null;
    
    public function enqueueFrontendAssets() {
        wp_enqueue_style(
            'daily-menu-frontend',
            plugins_url('css/frontend.css', __FILE__),
            array(),
            '1.0.0'
        );
    
        wp_enqueue_script(
            'daily-menu-frontend',
            plugins_url('js/frontend.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
    
        // AJAX URL für JavaScript verfügbar machen
        wp_localize_script('daily-menu-frontend', 'dailyMenuAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('daily_menu_nonce')
        ));
    }
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
        add_action('wp_ajax_submit_order', array($this, 'handleOrder'));
        add_action('wp_ajax_nopriv_submit_order', array($this, 'handleOrder'));

        // Check und erstelle Tabellen falls sie nicht existieren        
        $this->check_tables();
        
        // Shortcode registrieren
        add_shortcode('daily_menu', array($this, 'displayDailyMenu'));
        
        // Frontend Assets registrieren
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendAssets'));
    }

    public static function activate() {
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

    private function check_tables() {
        global $wpdb;
        
        // Prüfe ob die Haupttabelle existiert
        $table_name = $wpdb->prefix . 'daily_menus';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Wenn die Tabelle nicht existiert, führe die Aktivierung erneut aus
            activate_daily_menu_manager();
        }
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
        
        // Filter-Logik
        $where_clauses = array();
        $where_values = array();
        
        // Datum Filter - Standardmäßig heutiges Datum, wenn kein Filter gesetzt
        $current_date = current_time('Y-m-d');
        $filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : $current_date;
    
        if ($filter_date == 'all') {
            // Wenn 'all' ausgewählt wurde, keinen Datumsfilter anwenden
        } else {
            $where_clauses[] = "DATE(o.order_date) = %s";
            $where_values[] = $filter_date;
        }
        
        // Bestellnummer Filter
        if (!empty($_GET['filter_order'])) {
            $where_clauses[] = "o.order_number LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like(sanitize_text_field($_GET['filter_order'])) . '%';
        }
    
        // Name Filter
        if (!empty($_GET['filter_name'])) {
            $where_clauses[] = "o.customer_name LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like(sanitize_text_field($_GET['filter_name'])) . '%';
        }
    
        // Base query
        $base_query = "
            SELECT 
                o.*,
                mi.title as menu_item_title,
                mi.price,
                mi.item_type,
                COUNT(*) OVER (PARTITION BY o.order_number) as items_in_order,
                MIN(o.id) OVER (PARTITION BY o.order_number) as first_item_in_order
            FROM {$wpdb->prefix}menu_orders o
            JOIN {$wpdb->prefix}menu_items mi ON o.menu_item_id = mi.id
        ";
    
        if (!empty($where_clauses)) {
            $base_query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $base_query .= " ORDER BY o.order_date DESC, o.order_number, mi.item_type, mi.title";
        
        if (!empty($where_values)) {
            $orders = $wpdb->get_results($wpdb->prepare($base_query, $where_values));
        } else {
            $orders = $wpdb->get_results($base_query);
        }
    
        // Gruppiere Bestellungen nach Datum für die Zusammenfassung
        $date_groups = array();
        $counted_orders = array();
    
        foreach ($orders as $order) {
            $date = date('Y-m-d', strtotime($order->order_date));
            
            if (!isset($date_groups[$date])) {
                $date_groups[$date] = 0;
            }
            
            if (!isset($counted_orders[$date . $order->order_number])) {
                $date_groups[$date]++;
                $counted_orders[$date . $order->order_number] = true;
            }
        }
    
        ?>
        <div class="wrap">
            <h1>Bestellungen</h1>
    
            <!-- Tages-Zusammenfassung -->
            <div class="order-summary-boxes">
                <?php foreach ($date_groups as $date => $count): ?>
                    <div class="summary-box">
                        <h3><?php echo date_i18n('d.m.Y', strtotime($date)); ?></h3>
                        <p><?php echo $count; ?> <?php echo $count === 1 ? 'Bestellung' : 'Bestellungen'; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
    
            <!-- Filter-Optionen -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" class="filter-form">
                        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
                        
                        <label for="filter_date">Datum:</label>
                        <input type="date" 
                               id="filter_date"
                               name="filter_date" 
                               value="<?php echo esc_attr($filter_date); ?>">
                        <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&filter_date=all" class="button">Alle Bestellungen</a>
                        
                        <label for="filter_order">Bestellnummer:</label>
                        <input type="text" 
                               id="filter_order"
                               name="filter_order" 
                               placeholder="z.B. 20241110" 
                               value="<?php echo isset($_GET['filter_order']) ? esc_attr($_GET['filter_order']) : ''; ?>">
                        
                        <label for="filter_name">Name:</label>
                        <input type="text" 
                               id="filter_name"
                               name="filter_name" 
                               placeholder="Kundenname" 
                               value="<?php echo isset($_GET['filter_name']) ? esc_attr($_GET['filter_name']) : ''; ?>">
                        
                        <input type="submit" class="button" value="Filtern">
                        <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>" class="button">Heute anzeigen</a>
                    </form>
                </div>
            </div>
    
            <?php if (empty($orders)): ?>
                <div class="notice notice-info">
                    <p>Keine Bestellungen gefunden.</p>
                </div>
            <?php else: ?>
    
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Bestellnummer</th>
                        <th>Datum/Uhrzeit</th>
                        <th>Name</th>
                        <th>Bestellte Gerichte</th>
                        <th>Gesamtbetrag</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_order = '';
                    $order_total = 0;
                    $row_class = 'order-row-even';
                    
                    foreach ($orders as $order):
                        // Wenn eine neue Bestellung beginnt
                        if ($current_order !== $order->order_number):
                            if ($current_order !== ''): // Schließe die vorherige Bestellung ab
                                ?>
                                <tr class="order-total <?php echo $row_class; ?>">
                                    <td colspan="3"></td>
                                    <td><strong>Gesamtbetrag:</strong></td>
                                    <td colspan="2"><strong><?php echo number_format($order_total, 2); ?> €</strong></td>
                                </tr>
                                <?php
                            endif;
                            
                            $row_class = ($row_class === 'order-row-even') ? 'order-row-odd' : 'order-row-even';
                            $current_order = $order->order_number;
                            $order_total = 0;
                            ?>
                            <tr class="order-header <?php echo $row_class; ?>">
                                <td><strong><?php echo esc_html($order->order_number); ?></strong></td>
                                <td><?php echo esc_html(date('d.m.Y H:i', strtotime($order->order_date))); ?></td>
                                <td><?php echo esc_html($order->customer_name); ?></td>
                                <td colspan="3">
                                    <?php if ($order->general_notes): ?>
                                        <div class="general-notes">
                                            <strong>Allgemeine Anmerkungen:</strong> <?php echo esc_html($order->general_notes); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; 
                        
                        $item_total = $order->quantity * $order->price;
                        $order_total += $item_total;
                        ?>
                        
                        <tr class="order-item <?php echo $row_class; ?>">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <strong><?php echo esc_html($order->quantity); ?>x</strong> 
                                <?php echo esc_html($order->menu_item_title); ?>
                                <?php if ($order->notes): ?>
                                    <br><small>Anmerkung: <?php echo esc_html($order->notes); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($item_total, 2); ?> €</td>
                            <td>
                                <?php if ($order->id === $order->first_item_in_order): ?>
                                    <button class="button print-order" data-order="<?php echo esc_attr($order->order_number); ?>">
                                        Drucken
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; 
                    
                    // Schließe die letzte Bestellung ab
                    if ($current_order !== ''): ?>
                        <tr class="order-total <?php echo $row_class; ?>">
                            <td colspan="3"></td>
                            <td><strong>Gesamtbetrag:</strong></td>
                            <td colspan="2"><strong><?php echo number_format($order_total, 2); ?> €</strong></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    
        <style>
            .order-summary-boxes {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
            }
            .summary-box {
                background: #fff;
                padding: 15px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                flex: 1;
                max-width: 200px;
            }
            .summary-box h3 {
                margin: 0 0 10px 0;
            }
            .summary-box p {
                margin: 0;
                font-size: 1.2em;
                font-weight: bold;
                color: #2c5282;
            }
            .order-row-even {
                background-color: #f8f9fa;
            }
            .order-row-odd {
                background-color: #e9ecef;
            }
            .order-header {
                background-color: inherit;
                border-top: 2px solid #dee2e6;
            }
            .order-item td {
                padding-left: 20px;
            }
            .order-total {
                border-bottom: 2px solid #dee2e6;
            }
            .general-notes {
                font-size: 0.9em;
                color: #666;
                margin-top: 5px;
            }
            .filter-form {
                display: flex;
                gap: 15px;
                align-items: center;
                background: #fff;
                padding: 15px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                flex-wrap: wrap;
            }
            .filter-form label {
                font-weight: 500;
            }
            .filter-form input[type="date"],
            .filter-form input[type="text"] {
                padding: 5px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                height: 35px;
            }
            .filter-form .button {
                height: 35px;
                line-height: 33px;
            }
        </style>
    
        <script>
        jQuery(document).ready(function($) {
            $('.print-order').on('click', function() {
                const orderNumber = $(this).data('order');
                const orderElements = $(this).closest('tr').prevAll('.order-header').first()
                    .add($(this).closest('tr').prevAll('.order-item').addBack())
                    .add($(this).closest('tr').nextAll('.order-item, .order-total').addBack());
    
                const printWindow = window.open('', '', 'height=600,width=800');
                printWindow.document.write('<html><head><title>Bestellung ' + orderNumber + '</title>');
                printWindow.document.write('<style>');
                printWindow.document.write(`
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    td, th { padding: 8px; border: 1px solid #ddd; }
                    .order-total { font-weight: bold; }
                `);
                printWindow.document.write('</style></head><body>');
                printWindow.document.write('<h2>Bestellung: ' + orderNumber + '</h2>');
                printWindow.document.write('<table>' + orderElements.html() + '</table>');
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            });
        });
        </script>
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
            
            <form id="menu-order-form" class="menu-order-form">
                <input type="hidden" name="menu_id" value="<?php echo esc_attr($menu->id); ?>">
                <?php wp_nonce_field('menu_order_nonce'); ?>
                
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
                                <div class="menu-item-header">
                                    <span class="menu-item-title"><?php echo esc_html($item->title); ?></span>
                                    <span class="menu-item-price"><?php echo number_format($item->price, 2); ?> €</span>
                                </div>
                                
                                <?php if ($item->description): ?>
                                    <p class="menu-item-description">
                                        <?php echo nl2br(esc_html($item->description)); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="menu-item-order">
                                    <label for="quantity_<?php echo esc_attr($item->id); ?>">Anzahl:</label>
                                    <input type="number" 
                                           class="quantity-input"
                                           name="items[<?php echo esc_attr($item->id); ?>][quantity]" 
                                           id="quantity_<?php echo esc_attr($item->id); ?>"
                                           min="0" 
                                           value="0"
                                           data-price="<?php echo esc_attr($item->price); ?>">
                                           
                                    <div class="item-notes">
                                        <label for="notes_<?php echo esc_attr($item->id); ?>">Anmerkungen:</label>
                                        <input type="text" 
                                               name="items[<?php echo esc_attr($item->id); ?>][notes]" 
                                               id="notes_<?php echo esc_attr($item->id); ?>"
                                               placeholder="z.B. ohne Zwiebeln">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; 
                endforeach; ?>
    
                <div class="order-summary">
                    <h3>Bestellübersicht</h3>
                    <div class="order-total">Gesamtbetrag: <span id="total-amount">0,00 €</span></div>
                </div>
    
                <div class="customer-info">
                    <h3>Ihre Informationen</h3>
                    <div class="form-field">
                        <label for="customer_name">Name*</label>
                        <input type="text" name="customer_name" id="customer_name" required>
                    </div>
                    <div class="form-field">
                        <label for="general_notes">Allgemeine Anmerkungen zur Bestellung</label>
                        <textarea name="general_notes" id="general_notes"></textarea>
                    </div>
                </div>
    
                <button type="submit" class="submit-order">Bestellung aufgeben</button>
            </form>
    
            <div id="order-confirmation" class="order-confirmation" style="display: none;">
                <h3>Bestellung erfolgreich aufgegeben!</h3>
                <p>Ihre Bestellnummer: <strong id="order-number"></strong></p>
                <p>Bitte nennen Sie diese Nummer bei der Abholung.</p>
                <div class="confirmation-details"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Angepasste handleOrder Methode
    public function handleOrder() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'menu_order_nonce')) {
            wp_send_json_error('Ungültiger Sicherheitstoken');
        }
    
        global $wpdb;
        
        // Generiere eine Bestellnummer (Datum + fortlaufende Nummer)
        $order_date = current_time('Ymd');
        $next_number = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '-', -1) AS UNSIGNED)) + 1 
             FROM {$wpdb->prefix}menu_orders 
             WHERE order_number LIKE %s",
            $order_date . '-%'
        ));
        
        if (!$next_number) $next_number = 1;
        
        $order_number = $order_date . '-' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
        
        // Sammle die Bestelldaten
        $menu_id = intval($_POST['menu_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $general_notes = sanitize_textarea_field($_POST['general_notes']);
        $items = isset($_POST['items']) ? $_POST['items'] : array();
        
        $order_items = array();
        $total_amount = 0;
        
        // Speichere jedes bestellte Item
        foreach ($items as $item_id => $item_data) {
            $quantity = intval($item_data['quantity']);
            if ($quantity > 0) {
                $item_notes = sanitize_text_field($item_data['notes']);
                
                $wpdb->insert(
                    $wpdb->prefix . 'menu_orders',
                    array(
                        'menu_id' => $menu_id,
                        'menu_item_id' => $item_id,
                        'customer_name' => $customer_name,
                        'order_number' => $order_number,
                        'quantity' => $quantity,
                        'notes' => $item_notes,
                        'general_notes' => $general_notes,
                        'order_date' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s')
                );
                
                // Hole Item-Details für die Bestätigung
                $item_details = $wpdb->get_row($wpdb->prepare(
                    "SELECT title, price FROM {$wpdb->prefix}menu_items WHERE id = %d",
                    $item_id
                ));
                
                if ($item_details) {
                    $order_items[] = array(
                        'title' => $item_details->title,
                        'quantity' => $quantity,
                        'price' => $item_details->price,
                        'notes' => $item_notes
                    );
                    $total_amount += $quantity * $item_details->price;
                }
            }
        }
    
        wp_send_json_success(array(
            'order_number' => $order_number,
            'items' => $order_items,
            'total_amount' => $total_amount,
            'customer_name' => $customer_name
        ));
    }
}

// Hooks registrieren
register_activation_hook(__FILE__, 'activate_daily_menu_manager');
register_deactivation_hook(__FILE__, 'deactivate_daily_menu_manager');

// Plugin initialisieren
$dailyMenuManager = DailyMenuManager::getInstance();