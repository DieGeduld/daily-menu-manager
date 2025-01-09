<?php
namespace DailyMenuManager\Models;

class Order {
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Erstellt eine neue Bestellung
     * 
     * @param array $data Bestellungsdaten
     * @return array|WP_Error
     */
    public static function createOrder($data) {
        global $wpdb;
        
        try {
            // Generiere fortlaufende Bestellnummer (0000-9999)
            $last_order = $wpdb->get_var(
                "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '-', -1) AS UNSIGNED)) 
                FROM {$wpdb->prefix}menu_orders"
            );
                        
            // Wenn keine Bestellung existiert oder der letzte Wert kein gültiger Integer ist
            if (!$last_order || !is_numeric($last_order)) {
                $next_number = 0;
            } else {
                $next_number = intval($last_order) + 1;
                if ($next_number > 999) {
                    $next_number = 0;
                }
            }
            
            // Formatiere die Bestellnummer mit führenden Nullen
            $order_number = str_pad($next_number, 3, '0', STR_PAD_LEFT);
            
            // Rest des bestehenden Codes bleibt gleich
            $order_items = [];
            $total_amount = 0;
            
            $wpdb->query('START TRANSACTION');
            
            foreach ($data['items'] as $item_id => $item_data) {
                $quantity = intval($item_data['quantity']);
                if ($quantity > 0) {
                    // Einzelnes Bestellitem speichern
                    $inserted = $wpdb->insert(
                        $wpdb->prefix . 'menu_orders',
                        [
                            'menu_id' => intval($data['menu_id']),
                            'menu_item_id' => $item_id,
                            'order_number' => $order_number,
                            'customer_name' => sanitize_text_field($data['customer_name']),
                            'quantity' => $quantity,
                            'notes' => sanitize_textarea_field($item_data['notes'] ?? ''),
                            'general_notes' => sanitize_textarea_field($data['general_notes'] ?? ''),
                            'order_date' => current_time('mysql')
                        ],
                        ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s']
                    );
                    
                    if ($inserted === false) {
                        throw new \Exception('Fehler beim Speichern der Bestellung');
                    }
                    
                    // Hole Item-Details für die Bestätigung
                    $item_details = $wpdb->get_row($wpdb->prepare(
                        "SELECT title, price FROM {$wpdb->prefix}menu_items WHERE id = %d",
                        $item_id
                    ));
                    
                    if ($item_details) {
                        $order_items[] = [
                            'title' => $item_details->title,
                            'quantity' => $quantity,
                            'price' => $item_details->price,
                            'notes' => $item_data['notes'] ?? ''
                        ];
                        $total_amount += $quantity * $item_details->price;
                    }
                }
            }
            
            $wpdb->query('COMMIT');
            
            return [
                'success' => true,
                'order_number' => $order_number,
                'items' => $order_items,
                'total_amount' => $total_amount,
                'customer_name' => $data['customer_name']
            ];
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('order_creation_failed', $e->getMessage());
        }
    }

    /**
     * Holt Bestellungen mit optionalen Filtern
     * 
     * @param array $filters Filteroptionen
     * @return array
     */
    public static function getOrders($filters = []) {
        global $wpdb;
        
        $where_clauses = [];
        $where_values = [];
        
        // Datum Filter
        if (!empty($filters['date']) && $filters['date'] !== 'all') {
            $where_clauses[] = "DATE(o.order_date) = %s";
            $where_values[] = $filters['date'];
        }
        
        // Bestellnummer Filter
        if (!empty($filters['order_number'])) {
            $where_clauses[] = "o.order_number LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($filters['order_number']) . '%';
        }
        
        // Name Filter
        if (!empty($filters['customer_name'])) {
            $where_clauses[] = "o.customer_name LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($filters['customer_name']) . '%';
        }
        
        $query = "
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
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $query .= " ORDER BY o.order_date DESC, o.order_number, mi.item_type, mi.title";
        
        if (!empty($where_values)) {
            $orders = $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            $orders = $wpdb->get_results($query);
        }
        
        return $orders;
    }

    /**
     * Holt Bestellstatistiken für einen Zeitraum
     * 
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public static function getOrderStats($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d');
        
        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(o.order_date) as date,
                COUNT(DISTINCT o.order_number) as total_orders,
                SUM(o.quantity * mi.price) as total_revenue,
                COUNT(o.id) as total_items
            FROM {$wpdb->prefix}menu_orders o
            JOIN {$wpdb->prefix}menu_items mi ON o.menu_item_id = mi.id
            WHERE DATE(o.order_date) BETWEEN %s AND %s
            GROUP BY DATE(o.order_date)
            ORDER BY date DESC
        ", $start_date, $end_date));
        
        return $stats;
    }

    /**
     * Holt eine einzelne Bestellung anhand der Bestellnummer
     * 
     * @param string $order_number
     * @return object|null
     */
    public static function getOrderByNumber($order_number) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                o.*,
                mi.title as menu_item_title,
                mi.price,
                mi.item_type
            FROM {$wpdb->prefix}menu_orders o
            JOIN {$wpdb->prefix}menu_items mi ON o.menu_item_id = mi.id
            WHERE o.order_number = %s
            ORDER BY mi.item_type, mi.title
        ", $order_number));
    }

    /**
     * Löscht eine Bestellung
     * 
     * @param string $order_number
     * @return bool
     */
    public static function deleteOrder($order_number) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'menu_orders',
            ['order_number' => $order_number],
            ['%s']
        );
    }

    /**
     * Aktualisiert den Status einer Bestellung
     * 
     * @param string $order_number
     * @param string $status
     * @return bool
     */
    public static function updateOrderStatus($order_number, $status) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'menu_orders',
            ['status' => $status],
            ['order_number' => $order_number],
            ['%s'],
            ['%s']
        );
    }
}