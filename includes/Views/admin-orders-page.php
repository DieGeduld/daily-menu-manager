<?php

defined('ABSPATH') or die('Direct access not allowed!');

use DailyMenuManager\Controller\Admin\SettingsController;

?>

<div class="wrap">
    <h1><?php _e('Orders', 'daily-dish-manager'); ?></h1>

    <!-- Statistik-Boxen -->
    <div class="order-summary-table">
        <table class="summary-table">
            <tr>
                <td><?php _e('Today\'s orders', 'daily-dish-manager'); ?></td>
                <td class="value"><?php echo esc_html($stats['total_orders']); ?></td>
            </tr>
            <tr>
                <td><?php _e('Today\'s revenue', 'daily-dish-manager'); ?></td>
                <td class="value"><?php echo SettingsController::formatPrice($stats['total_revenue']); ?></td>
            </tr>
            <tr>
                <td><?php _e('Ordered items', 'daily-dish-manager'); ?></td>
                <td class="value"><?php echo esc_html($stats['total_items']); ?></td>
            </tr>
        </table>
    </div>

    <!-- Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" class="filter-form">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">

                <label for="filter_date"><?php _e('Date:', 'daily-dish-manager'); ?></label>
                <input type="date"
                    id="filter_date"
                    name="filter_date"
                    value="<?php echo esc_attr($filters['date']); ?>">

                <label for="filter_order"><?php _e('Order Number:', 'daily-dish-manager'); ?></label>
                <input type="text"
                    id="filter_order"
                    name="filter_order"
                    value="<?php echo esc_attr($filters['order_number']); ?>"
                    placeholder="e.g. 20241110-001">

                <label for="filter_name"><?php _e('Name:', 'daily-dish-manager'); ?></label>
                <input type="text"
                    id="filter_name"
                    name="filter_name"
                    value="<?php echo esc_attr($filters['customer_name']); ?>"
                    placeholder="Customer Name">

                <label for="filter_phone"><?php _e('Phone:', 'daily-dish-manager'); ?></label>
                <input type="text"
                    id="filter_phone"
                    name="filter_phone"
                    value="<?php echo esc_attr($filters['customer_phone']); ?>"
                    placeholder="Phone Number">

                <input type="submit" class="button" value="<?php _e('Filter', 'daily-dish-manager'); ?>">
                <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>" class="button">
                    <?php _e('Reset Filter', 'daily-dish-manager'); ?>
                </a>
            </form>
        </div>
    </div>

    <!-- Bestellungen Tabelle -->
    <?php if (empty($orders)): ?>
        <div class="notice notice-info">
            <p><?php _e('No orders found.', 'daily-dish-manager'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Order Number', 'daily-dish-manager'); ?></th>
                    <th><?php _e('Date/Time', 'daily-dish-manager'); ?></th>
                    <th><?php _e('Name', 'daily-dish-manager'); ?></th>
                    <th><?php _e('Phone', 'daily-dish-manager'); ?></th>
                    <th><?php _e('Type', 'daily-dish-manager'); ?></th>
                    <th><?php _e('Pickup Time', 'daily-dish-manager'); ?></th>
                    <th><?php _e('Ordered Items', 'daily-dish-manager'); ?></th>
                    <th><?php _e('Total', 'daily-dish-manager'); ?></th>
                    <th><?php _e('Actions', 'daily-dish-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $current_order = '';
        $order_total = 0;

        foreach ($orders as $order):
            // Neue Bestellung beginnt
            if ($current_order !== $order->order_number):
                if ($current_order !== ''): // Vorherige Bestellung abschließen
                    ?>
                            <tr class="order-total">
                                <td colspan="6"><strong><?php _e('Total:', 'daily-dish-manager'); ?></strong></td>
                                <td colspan="2"><strong><?php echo SettingsController::formatPrice($order_total); ?></strong></td>
                            </tr>
                        <?php endif;

                $current_order = $order->order_number;
                $order_total = 0;
                ?>

                        <tr class="order-header">
                            <td><strong><?php echo esc_html($order->order_number); ?></strong></td>
                            <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($order->order_date))); ?></td>
                            <td><?php echo esc_html($order->customer_name); ?></td>
                            <td><?php echo esc_html($order->customer_phone); ?></td>
                            <td><?php echo esc_html($order->consumption_type); ?></td>
                            <td><?php echo date('H:i', strtotime($order->pickup_time)); ?></td>
                            <td colspan="3">
                                <?php if (!empty($order->general_notes)): ?>
                                    <div class="general-notes">
                                        <strong><?php _e('Notes:', 'daily-dish-manager'); ?></strong>
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
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>
                            <strong><?php echo esc_html($order->quantity); ?>x</strong>
                            <?php echo esc_html($order->menu_item_title); ?>
                            <?php if (!empty($order->notes)): ?>
                                <br><small><?php _e('Notes:', 'daily-dish-manager'); ?> <?php echo esc_html($order->notes); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo SettingsController::formatPrice($item_total); ?></td>
                        <td>
                            <?php if ($order->order_item_id == $order->first_item_in_order): ?>
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
                        <td colspan="6"><strong><?php _e('Total:', 'daily-dish-manager'); ?></strong></td>
                        <td colspan="2"><strong><?php echo SettingsController::formatPrice($order_total); ?></strong></td>
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