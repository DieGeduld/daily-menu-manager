<?php
namespace DailyMenuManager\Models;

class Menu {
    private static $instance = null;
    
    /**
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * @var string
     */
    private $table_name;

    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'daily_menus';
    }
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Erstellt die notwendigen Datenbanktabellen
     */
    public static function createTables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
            // Haupttabelle für Tagesmenüs
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}daily_menus (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                menu_date date NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY menu_date (menu_date)
            ) $charset_collate",

            // Tabelle für Menüeinträge
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}menu_items (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                menu_id mediumint(9) NOT NULL,
                item_type varchar(50) NOT NULL,
                title varchar(255) NOT NULL,
                description text,
                price decimal(10,2) NOT NULL,
                available_quantity int(11) NOT NULL DEFAULT 0,
                properties text DEFAULT NULL,
                allergens text DEFAULT NULL,
                sort_order int NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY menu_id (menu_id)
            ) $charset_collate"
        ];
        
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Speichert oder aktualisiert ein Menü
     * 
     * @param array $menu_data
     * @return int|WP_Error Menu ID oder Fehler
     */
    public function saveMenu($menu_data) {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            $menu_date = sanitize_text_field($menu_data['menu_date']);
            
            // Prüfe ob Menü bereits existiert
            $menu_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}daily_menus WHERE menu_date = %s",
                $menu_date
            ));
            
            // Erstelle oder aktualisiere Menü
            if (!$menu_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'daily_menus',
                    ['menu_date' => $menu_date],
                    ['%s']
                );
                $menu_id = $wpdb->insert_id;
            }
            
            // Hole IDs der vorhandenen Menüeinträge für dieses Menü
            $existing_items = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}menu_items WHERE menu_id = %d",
                $menu_id
            ));
            
            // Sammle IDs der übermittelten Menüeinträge
            $updated_item_ids = [];
            
            // Füge neue Menüeinträge hinzu oder aktualisiere bestehende
            if (isset($menu_data['menu_items']) && is_array($menu_data['menu_items'])) {
                $sort_order = 1;
                
                // Gehe durch alle übermittelten Menüeinträge, unabhängig vom Schlüsselnamen
                foreach ($menu_data['menu_items'] as $key => $item_data) {
                    // Überspringe leere oder ungültige Einträge
                    if (!is_array($item_data) || empty($item_data['title'])) {
                        continue;
                    }
                    
                    $props = !empty($item_data["properties"]) ? wp_json_encode($item_data["properties"]) : null;
                    $allergens = !empty($item_data["allergens"]) ? sanitize_textarea_field($item_data["allergens"]) : null;
                    
                    $data = [
                        'menu_id' => $menu_id,
                        'item_type' => sanitize_text_field($item_data['type']),
                        'title' => sanitize_text_field($item_data['title']),
                        'description' => isset($item_data['description']) ? sanitize_textarea_field($item_data['description']) : '',
                        'price' => floatval($item_data['price']),
                        'available_quantity' => isset($item_data['available_quantity']) ? intval($item_data['available_quantity']) : 0,
                        'properties' => $props,
                        'allergens' => $allergens,
                        'sort_order' => $sort_order++
                    ];
                    
                    $formats = ['%d', '%s', '%s', '%s', '%f', '%d', '%s', '%s', '%d'];
                    
                    // Prüfe, ob es sich um ein bestehendes Element handelt oder ein neues
                    // Wenn der Schlüssel mit "new-" beginnt, ist es wahrscheinlich ein neues Element
                    // Andernfalls prüfe auf die ID im Element selbst
                    $existing_id = null;
                    if (isset($item_data['id']) && !empty($item_data['id']) && is_numeric($item_data['id'])) {
                        $existing_id = intval($item_data['id']);
                    } elseif (is_numeric($key) && in_array($key, $existing_items)) {
                        $existing_id = intval($key);
                    }
                    
                    if ($existing_id) {
                        // Aktualisiere vorhandenen Eintrag
                        $updated_item_ids[] = $existing_id;
                        
                        $wpdb->update(
                            $wpdb->prefix . 'menu_items',
                            $data,
                            ['id' => $existing_id],
                            $formats,
                            ['%d']
                        );
                    } else {
                        // Füge neuen Eintrag hinzu
                        $wpdb->insert(
                            $wpdb->prefix . 'menu_items',
                            $data,
                            $formats
                        );
                        $updated_item_ids[] = $wpdb->insert_id;
                    }
                }
            }
            
            // Lösche Menüeinträge, die nicht mehr in den übermittelten Daten enthalten sind
            foreach ($existing_items as $item_id) {
                if (!in_array($item_id, $updated_item_ids)) {
                    $wpdb->delete(
                        $wpdb->prefix . 'menu_items',
                        ['id' => $item_id],
                        ['%d']
                    );
                }
            }
            
            $wpdb->query('COMMIT');
            return $menu_id;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('menu_save_failed', $e->getMessage());
        }
    }

    /**
     * Holt das Menü für ein bestimmtes Datum
     * 
     * @param string $date
     * @return object|null
     */
    public function getMenuForDate($date) {
        global $wpdb;
        
        $menu = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}daily_menus WHERE menu_date = %s",
            $date
        ));
        
        if ($menu) {
            $items = $this->getMenuItems($menu->id);
            if ($items) {
                $menu->items = $items;
            } else {
                $menu = null;
            }
        }
        
        return $menu;
    }

    /**
     * Holt das aktuelle Menü
     * 
     * @return object|null
     */
    public function getCurrentMenu() {
        return $this->getMenuForDate(current_time('Y-m-d'));
    }

    /**
     * Holt alle Menüeinträge für ein Menü
     * 
     * @param int $menu_id
     * @return array
     */
    public function getMenuItems(int $menu_id) {
        global $wpdb;
        
        if (!$menu_id) return [];
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}menu_items 
            WHERE menu_id = %d 
            ORDER BY sort_order ASC",
            $menu_id
        ));
        
        // Decode JSON properties if they exist
        foreach ($items as &$item) {
            if (!empty($item->properties)) {
                $item->properties = json_decode($item->properties, true);
            }
        }
        
        return $items;
    }

    /**
     * Aktualisiert die Sortierreihenfolge der Menüeinträge
     * 
     * @param array $item_orders Array von ID => Position
     * @return bool
     */
    public function updateItemOrder($item_orders) {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            foreach ($item_orders as $item_id => $position) {
                $updated = $wpdb->update(
                    $wpdb->prefix . 'menu_items',
                    ['sort_order' => intval($position)],
                    ['id' => intval($item_id)],
                    ['%d'],
                    ['%d']
                );
                
                if ($updated === false) {
                    throw new \Exception('Fehler beim Aktualisieren der Sortierreihenfolge');
                }
            }
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Löscht ein Menü und alle zugehörigen Einträge
     * 
     * @param int $menu_id
     * @return bool
     */
    public function deleteMenu($menu_id) {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            // Lösche alle Menüeinträge
            $wpdb->delete(
                $wpdb->prefix . 'menu_items',
                ['menu_id' => $menu_id],
                ['%d']
            );
            
            // Lösche das Menü selbst
            $wpdb->delete(
                $wpdb->prefix . 'daily_menus',
                ['id' => $menu_id],
                ['%d']
            );
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Prüft ob ein Menü für ein bestimmtes Datum existiert
     * 
     * @param string $date
     * @return bool
     */
    public function menuExists($date) {
        global $wpdb;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}daily_menus WHERE menu_date = %s",
            $date
        ));
        
        return $exists > 0;
    }

    /**
     * Gibt ein Array von Daten zurück, an denen ein Menü existiert
     * 
     * @return array
     */
    public static function getMenuDates() {
        global $wpdb;
        
        $dates = $wpdb->get_col("
            SELECT DISTINCT dm.menu_date
            FROM {$wpdb->prefix}daily_menus dm
            INNER JOIN {$wpdb->prefix}menu_items mi
                ON dm.id = mi.menu_id
            ORDER BY dm.menu_date ASC
        ");
        
        return $dates;
    }


    /**
     * Kopiert ein Menü auf ein anderes Datum
     * 
     * @param int $menu_id
     * @param string $new_date
     * @return int|WP_Error Neue Menu ID oder Fehler
     */
    public function copyMenu(int $menu_id, $new_date) {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            // Erstelle neues Menü
            // Prüfe ob Menü bereits existiert
            $existing_menu = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}daily_menus WHERE menu_date = %s",
                $new_date
            ));
            
            if (!$existing_menu) {
                $wpdb->insert(
                    $wpdb->prefix . 'daily_menus',
                    ['menu_date' => $new_date],
                    ['%s']
                );
                $new_menu_id = $wpdb->insert_id;
            } else {
                $new_menu_id = $existing_menu;
            }
            
            // Kopiere alle Menüeinträge
            $items = $this->getMenuItems($menu_id);
            foreach ($items as $item) {
                $item_array = (array)$item;
                
                // Remove the ID to create a new record
                unset($item_array['id']);
                
                // Set the new menu ID
                $item_array['menu_id'] = $new_menu_id;
                
                // Remove created_at and updated_at if present
                unset($item_array['created_at']);
                unset($item_array['updated_at']);
                
                // Encode properties back to JSON if it was decoded
                if (isset($item_array['properties']) && is_array($item_array['properties'])) {
                    $item_array['properties'] = wp_json_encode($item_array['properties']);
                }
                
                $wpdb->insert(
                    $wpdb->prefix . 'menu_items',
                    $item_array,
                    ['%d', '%s', '%s', '%s', '%f', '%d', '%s', '%s', '%d']
                );

                // `menu_id` mediumint(9) NOT NULL,
                // `item_type` varchar(50) NOT NULL,
                // `title` varchar(255) NOT NULL,
                // `description` text DEFAULT NULL,
                // `price` decimal(10,2) NOT NULL,
                // `available_quantity` int(11) NOT NULL DEFAULT 0,
                // `properties` varchar(255) DEFAULT NULL,
                // `sort_order` int(11) NOT NULL,
                // `allergens` text DEFAULT NULL,
                // `created_at` datetime DEFAULT current_timestamp(),
                // `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),

            }
            
            $wpdb->query('COMMIT');
            return $new_menu_id;
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('menu_copy_failed', $e->getMessage());
        }
    }

    /**
     * Holt Menüstatistiken für einen Zeitraum
     * 
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function getMenuStats($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d');
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                dm.menu_date,
                COUNT(DISTINCT mi.id) as total_items,
                MIN(mi.price) as min_price,
                MAX(mi.price) as max_price,
                AVG(mi.price) as avg_price,
                SUM(mi.available_quantity) as total_available_items,
                COUNT(DISTINCT o.order_number) as total_orders
            FROM {$wpdb->prefix}daily_menus dm
            LEFT JOIN {$wpdb->prefix}menu_items mi ON dm.id = mi.menu_id
            LEFT JOIN {$wpdb->prefix}menu_orders o ON dm.id = o.menu_id
            WHERE dm.menu_date BETWEEN %s AND %s
            GROUP BY dm.menu_date
            ORDER BY dm.menu_date DESC
        ", $start_date, $end_date));
    }

    /**
     * Updates the available quantities of menu items when orders are placed.
     *
     * @param array $order_items Array of item_id => quantity ordered
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function updateAvailableQuantities($order_items) {
        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');

            foreach ($order_items as $item_id => $item) {
                $updated = $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}menu_items SET available_quantity = available_quantity - %d WHERE id = %d AND available_quantity >= %d",
                    $item["quantity"], $item_id, $item["quantity"]
                ));

                if ($updated === false) {
                    throw new \Exception('Fehler beim Aktualisieren der verfügbaren Mengen');
                }
            }

            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('quantity_update_failed', $e->getMessage());
        }
    }
    
    /**
     * Aktualisiert die Allergene eines Menüeintrags
     *
     * @param int $item_id
     * @param string $allergens
     * @return bool
     */
    public function updateItemAllergens($item_id, $allergens) {
        global $wpdb;
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'menu_items',
            ['allergens' => sanitize_textarea_field($allergens)],
            ['id' => intval($item_id)],
            ['%s'],
            ['%d']
        );
        
        return $updated !== false;
    }
    
    /**
     * Aktualisiert die verfügbare Menge eines Menüeintrags
     *
     * @param int $item_id
     * @param int $quantity
     * @return bool
     */
    public function updateItemQuantity($item_id, $quantity) {
        global $wpdb;
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'menu_items',
            ['available_quantity' => intval($quantity)],
            ['id' => intval($item_id)],
            ['%d'],
            ['%d']
        );
        
        return $updated !== false;
    }
}