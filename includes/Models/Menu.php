<?php
namespace DailyMenuManager\Models;

class Menu {
    private static $instance = null;
    
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
                sort_order int NOT NULL,
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
            
            // Lösche existierende Menüeinträge
            $wpdb->delete(
                $wpdb->prefix . 'menu_items',
                ['menu_id' => $menu_id],
                ['%d']
            );
            
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
                            'sort_order' => $sort_order++,
                            'available_quantity' => intval($item_data['available_quantity'])
                        ],
                        ['%d', '%s', '%s', '%s', '%f', '%d', '%d']
                    );
                    
                    if ($inserted === false) {
                        throw new \Exception('Fehler beim Speichern der Menüeinträge');
                    }
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
            $menu->items = $this->getMenuItems($menu->id);
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
    public function getMenuItems($menu_id) {
        global $wpdb;
        
        if (!$menu_id) return [];
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}menu_items 
            WHERE menu_id = %d 
            ORDER BY sort_order ASC",
            $menu_id
        ));
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
     * Kopiert ein Menü auf ein anderes Datum
     * 
     * @param int $menu_id
     * @param string $new_date
     * @return int|WP_Error Neue Menu ID oder Fehler
     */
    public function copyMenu($menu_id, $new_date) {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            // Erstelle neues Menü
            $wpdb->insert(
                $wpdb->prefix . 'daily_menus',
                ['menu_date' => $new_date],
                ['%s']
            );
            $new_menu_id = $wpdb->insert_id;
            
            // Kopiere alle Menüeinträge
            $items = $this->getMenuItems($menu_id);
            foreach ($items as $item) {
                unset($item->id);
                $item->menu_id = $new_menu_id;
                
                $wpdb->insert(
                    $wpdb->prefix . 'menu_items',
                    (array)$item,
                    ['%d', '%s', '%s', '%s', '%f', '%d']
                );
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

            foreach ($order_items as $item_id => $quantity) {
                $updated = $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}menu_items SET available_quantity = available_quantity - %d WHERE id = %d AND available_quantity >= %d",
                    $quantity, $item_id, $quantity
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
     * Updates the available quantities of menu items when orders are placed.
     *
     * @param array $order_items Array of item_id => quantity ordered
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function updateAvailableQuantities($order_items) {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
    
            foreach ($order_items as $item_id => $quantity) {
                $updated = $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}menu_items SET available_quantity = available_quantity - %d WHERE id = %d AND available_quantity >= %d",
                    $quantity, $item_id, $quantity
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
}