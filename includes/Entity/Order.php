<?php

namespace DailyMenuManager\Entity;

class Order extends AbstractEntity
{
    protected $menu_id;
    protected $order_number;
    protected $customer_name;
    protected $customer_phone;
    protected $consumption_type;
    protected $pickup_time;
    protected $customer_email;
    protected $notes;
    protected $status;
    protected $order_date;

    private static $instance = null;

    /**
     * Constructor to create an Order entity from array data
     *
     * @param array $data Array of order data
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->menu_id = $data['menu_id'] ?? null;
        $this->order_number = $data['order_number'] ?? '';
        $this->customer_name = $data['customer_name'] ?? '';
        $this->customer_phone = $data['customer_phone'] ?? '';
        $this->consumption_type = $data['consumption_type'] ?? null;
        $this->pickup_time = $data['pickup_time'] ?? null;
        $this->customer_email = $data['customer_email'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->status = $data['status'] ?? 'pending';
        $this->order_date = $data['order_date'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /**
     * Singleton instance initializer
     *
     * @return Order
     */
    public static function init()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Spezifische Getter/Setter für Order

    /**
     * Get order number
     *
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->order_number;
    }

    /**
     * Set order number
     *
     * @param string $orderNumber
     * @return $this
     */
    public function setOrderNumber($orderNumber)
    {
        $this->order_number = $orderNumber;

        return $this;
    }

    /**
     * Erstellt eine neue Bestellung
     *
     * @param array $data Bestellungsdaten
     * @return array|WP_Error
     */
    public static function createOrder($data)
    {
        global $wpdb;

        try {
            // Generiere fortlaufende Bestellnummer (000-999)
            $last_order = $wpdb->get_var(
                "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '-', -1) AS UNSIGNED)) 
                FROM {$wpdb->prefix}ddm_orders"
            );

            // Wenn keine Bestellung existiert oder der letzte Wert kein gültiger Integer ist
            if ($last_order === false || !is_numeric($last_order)) {
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
                        $wpdb->prefix . 'ddm_orders',
                        [
                            'menu_id' => intval($data['menu_id']),
                            'order_number' => $order_number,
                            'customer_name' => sanitize_text_field($data['customer_name']),
                            'customer_phone' => sanitize_text_field($data['customer_phone']),
                            'consumption_type' => sanitize_text_field($data['consumption_type']),
                            'pickup_time' => sanitize_text_field($data['pickup_time']),
                            'quantity' => $quantity,
                            'notes' => sanitize_textarea_field($item_data['notes'] ?? ''),
                            'order_date' => current_time('mysql'),
                        ],
                        ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
                    );

                    if ($inserted === false) {
                        error_log($wpdb->last_error); // TODO!

                        throw new \Exception('Fehler beim Speichern der Bestellung');
                    }

                    // Hole Item-Details für die Bestätigung
                    $item_details = $wpdb->get_row($wpdb->prepare(
                        "SELECT title, price FROM {$wpdb->prefix}ddm_menu_items WHERE id = %d",
                        $item_id
                    ));

                    if ($item_details) {
                        $order_items[] = [
                            'title' => $item_details->title,
                            'quantity' => $quantity,
                            'price' => $item_details->price,
                            'notes' => $item_data['notes'] ?? '',
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
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'pickup_time' => $data['pickup_time'],
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
    public static function getOrders($filters = [])
    {
        global $wpdb;

        $where_clauses = [];
        $where_values = [];

        // Datum Filter mit explizitem Format
        if (!empty($filters['date']) && $filters['date'] !== 'all') {
            // Konvertiere beide Datumsformate in das Format YYYY-MM-DD für den Vergleich
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

        // Telefon Filter
        if (!empty($filters['customer_phone'])) {
            $where_clauses[] = "o.customer_phone LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($filters['customer_phone']) . '%';
        }

        $query = "
        SELECT 
            o.*,
            mi.title as menu_item_title,
            mi.price,
            mi.item_type,
            COUNT(*) OVER (PARTITION BY o.order_number) as items_in_order,
            MIN(o.id) OVER (PARTITION BY o.order_number) as first_item_in_order
        FROM {$wpdb->prefix}ddm_orders o
        JOIN {$wpdb->prefix}ddm_menu_items mi ON o.menu_item_id = mi.id
        "; // Todo: wie können wir nicht mehr über menu_item_id joinen, ordes hat das nicht mehr

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
    public static function getOrderStats($start_date = null, $end_date = null)
    {
        global $wpdb;

        if (!$start_date) {
            $start_date = date('Y-m-d');
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(o.order_date) as date,
                COUNT(DISTINCT o.order_number) as total_orders,
                SUM(o.quantity * mi.price) as total_revenue,
                COUNT(o.id) as total_items
            FROM {$wpdb->prefix}ddm_orders o
            JOIN {$wpdb->prefix}ddm_menu_items mi ON o.menu_item_id = mi.id
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
    public static function getOrderByNumber($order_number)
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                o.*,
                mi.title as menu_item_title,
                mi.price,
                mi.item_type
            FROM {$wpdb->prefix}ddm_orders o
            JOIN {$wpdb->prefix}ddm_menu_items mi ON o.menu_item_id = mi.id
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
    public static function deleteOrder($order_number)
    {
        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . 'ddm_orders',
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
    public static function updateOrderStatus($order_number, $status)
    {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'ddm_orders',
            ['status' => $status],
            ['order_number' => $order_number],
            ['%s'],
            ['%s']
        );
    }
}
