<?php defined('ABSPATH') or die('Direct access not allowed!'); ?>

<div class="wrap">
    <h1><?php _e('Bestellungen', 'daily-menu-manager'); ?></h1>

    <!-- Statistik-Boxen -->
    <div class="order-summary-boxes">
        <div class="summary-box">
            <h3><?php _e('Heutige Bestellungen', 'daily-menu-manager'); ?></h3>
            <p class="big-number"><?php echo esc_html($stats['total_orders']); ?></p>
        </div>
        <div class="summary-box">
            <h3><?php _e('Heutiger Umsatz', 'daily-menu-manager'); ?></h3>
            <p class="big-number"><?php echo number_format($stats['total_revenue'], 2); ?> €</p>
        </div>
        <div class="summary-box">
            <h3><?php _e('Bestellte Gerichte', 'daily-menu-manager'); ?></h3>
            <p class="big-number"><?php echo esc_html($stats['total_items']); ?></p>
        </div>
    </div>

    <!-- Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" class="filter-form">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
                
                <label for="filter_date"><?php _e('Datum:', 'daily-menu-manager'); ?></label>
                <input type="date" 
                       id="filter_date" 
                       name="filter_date" 
                       value="<?php echo esc_attr($filters['date']); ?>">
                
                <label for="filter_order"><?php _e('Bestellnummer:', 'daily-menu-manager'); ?></label>
                <input type="text" 
                       id="filter_order" 
                       name="filter_order" 
                       value="<?php echo esc_attr($filters['order_number']); ?>" 
                       placeholder="z.B. 20241110-001">
                
                <label for="filter_name"><?php _e('Name:', 'daily-menu-manager'); ?></label>
                <input type="text" 
                       id="filter_name" 
                       name="filter_name" 
                       value="<?php echo esc_attr($filters['customer_name']); ?>" 
                       placeholder="Kundenname">
                
                <input type="submit" class="button" value="<?php _e('Filtern', 'daily-menu-manager'); ?>">
                <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>" class="button">
                    <?php _e('Filter zurücksetzen', 'daily-menu-manager'); ?>
                </a>
            </form>
        </div>
    </div>

    <!-- Bestellungen Tabelle -->
    <?php if (empty($orders)): ?>
        <div class="notice notice-info">
            <p><?php _e('Keine Bestellungen gefunden.', 'daily-menu-manager'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Bestellnummer', 'daily-menu-manager'); ?></th>
                    <th><?php _e('Datum/Uhrzeit', 'daily-menu-manager'); ?></th>
                    <th><?php _e('Name', 'daily-menu-manager'); ?></th>
                    <th><?php _e('Bestellte Gerichte', 'daily-menu-manager'); ?></th>
                    <th><?php _e('Gesamtbetrag', 'daily-menu-manager'); ?></th>
                    <th><?php _e('Aktionen', 'daily-menu-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $current_order = '';
                $order_total = 0;
                
                foreach ($orders as $order): 
                    // Neue Bestellung beginnt
                    if ($current_order !== $order->order_number): 
                        if ($current_order !== ''): // Vorherige Bestellung abschließen ?>
                            <tr class="order-total">
                                <td colspan="4"><strong><?php _e('Gesamtbetrag:', 'daily-menu-manager'); ?></strong></td>
                                <td colspan="2"><strong><?php echo number_format($order_total, 2); ?> €</strong></td>
                            </tr>
                        <?php endif;
                        
                        $current_order = $order->order_number;
                        $order_total = 0;
                        ?>
                        
                        <tr class="order-header">
                            <td><strong><?php echo esc_html($order->order_number); ?></strong></td>
                            <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($order->order_date))); ?></td>
                            <td><?php echo esc_html($order->customer_name); ?></td>
                            <td colspan="3">
                                <?php if ($order->general_notes): ?>
                                    <div class="general-notes">
                                        <strong><?php _e('Anmerkungen:', 'daily-menu-manager'); ?></strong> 
                                        <?php echo esc_html($order->general_notes); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; 
                    
                    $item_total = $order->quantity * $order->price;
                    $order_total += $item_total;
                    ?>
                    
                    <tr class="order-item">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>
                            <strong><?php echo esc_html($order->quantity); ?>x</strong> 
                            <?php echo esc_html($order->menu_item_title); ?>
                            <?php if ($order->notes): ?>
                                <br><small><?php _e('Anmerkung:', 'daily-menu-manager'); ?> <?php echo esc_html($order->notes); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($item_total, 2); ?> €</td>
                        <td>
                            <?php if ($order->id === $order->first_item_in_order): ?>
                                <button class="button print-order" data-order="<?php echo esc_attr($order->order_number); ?>">
                                    <span class="dashicons dashicons-print"></span>
                                </button>
                                <button class="button delete-order" data-order="<?php echo esc_attr($order->order_number); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; 
                
                // Letzte Bestellung abschließen
                if ($current_order !== ''): ?>
                    <tr class="order-total">
                        <td colspan="4"><strong><?php _e('Gesamtbetrag:', 'daily-menu-manager'); ?></strong></td>
                        <td colspan="2"><strong><?php echo number_format($order_total, 2); ?> €</strong></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Druckvorschau Dialog -->
<div id="print-preview-dialog" style="display: none;">
    <div id="print-content"></div>
</div>