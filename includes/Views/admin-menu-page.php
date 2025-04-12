<?php
    defined('ABSPATH') or die('Direct access not allowed!');
    use DailyMenuManager\Admin\SettingsController;
?>

<div class="wrap">
    <h1><?php _e('Manage Daily Menu', 'daily-menu-manager'); ?></h1>
    
    <?php settings_errors('daily_menu_manager'); ?>
    
    <!-- Date Selection -->
    <div class="date-selection">
        <form method="get" class="date-selector-form">
            <input type="hidden" name="page" value="daily-menu-manager">
            <label for="menu_date"><?php _e('Select Date:', 'daily-menu-manager'); ?></label>

            <div class="flatpickr-wrapper">
                <input type="date"
                    id="menu_date" 
                    name="menu_date" 
                    value="<?php echo esc_attr($selected_date); ?>"
                    onchange="this.form.submit()"
                    data-input>

                    <a class="input-button" title="toggle" data-toggle>
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </a>

                </div>
            <?php if ($selected_date !== current_time('Y-m-d')): ?>
                <button type="submit" 
                        class="button" 
                        onclick="document.getElementById('menu_date').value='<?php echo esc_attr(current_time('Y-m-d')); ?>'; return true;">
                    <?php _e('Today', 'daily-menu-manager'); ?>
                </button>
            <?php endif; ?>

        </form>

        <?php if ($current_menu): ?>
        <div class="menu-actions">
            <button type="button" class="button copy-menu" data-menu-id="<?php echo esc_attr($current_menu->id); ?>">
                <?php _e('Copy Menu', 'daily-menu-manager'); ?>
            </button>
        </div>
        <?php else: ?>
            <div class="menu-actions">
            <button type="button" class="button copy-menu" data-menu-id="0">
                <?php _e('Import Menu', 'daily-menu-manager'); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Menü-Item Templates -->
    <?php foreach (self::getMenuTypes() as $type => $labels): ?>
        <script type="text/template" id="menu-item-template-<?php echo esc_attr($type); ?>">
            <div class="menu-item" data-type="<?php echo esc_attr($type); ?>">
                <div class="menu-item-header">
                    <span class="move-handle dashicons dashicons-move"></span>
                    <span class="menu-item-title"><?php echo esc_html($labels['label']); ?></span>
                    <button type="button" class="remove-menu-item button-link">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
                <div class="menu-item-content">
                    <input type="hidden" name="menu_items[new-{id}][type]" value="<?php echo esc_attr($type); ?>">
                    <input type="hidden" name="menu_items[new-{id}][sort_order]" value="0" class="sort-order">
                    
                    <div class="menu-item-field">
                        <label><?php _e('Title', 'daily-menu-manager'); ?></label>
                        <input type="text" name="menu_items[new-{id}][title]" required>
                    </div>
                    
                    <div class="menu-item-field">
                        <label><?php _e('Description', 'daily-menu-manager'); ?></label>
                        <textarea name="menu_items[new-{id}][description]"></textarea>
                    </div>
                    
                    <div class="menu-item-field">
                        <label><?php _e('Price', 'daily-menu-manager'); ?> (<?php echo SettingsController::getCurrencySymbol(); ?>)</label>
                        <input type="number" step="0.01" name="menu_items[new-{id}][price]" required>
                    </div>
                    
                    <div class="menu-item-field">
                        <label><?php _e('Quantity', 'daily-menu-manager'); ?></label>
                        <input type="number" step="0.01" name="menu_items[new-{id}][available_quantity]" required>
                    </div>

                </div>
            </div>
        </script>
    <?php endforeach; ?>

    <!-- Menü Formular -->
    <form method="post" action="" class="menu-form">
        <?php wp_nonce_field('save_menu_nonce'); ?>
        <input type="hidden" name="menu_date" value="<?php echo esc_attr($selected_date); ?>">
        
        <div class="menu-controls">
            <?php foreach (self::getMenuTypes() as $type => $labels): ?>
                <button type="button" class="button add-menu-item <?php echo sanitize_title($labels['label']); ?>" data-type="<?php echo esc_attr($type); ?>">
                    + <?php echo esc_html($labels['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="menu-items">
            <?php
            if (!empty($menu_items)) {
                foreach ($menu_items as $item) {
                    self::renderMenuItem($item);
                }
            }
            ?>
        </div>

        <?php submit_button(__('Save Menu', 'daily-menu-manager'), 'primary', 'save_menu'); ?>
    </form>
</div>

<!-- Kopier-Dialog to -->
<div id="copy-menu-dialog-to" style="display: none;" title="<?php _e('Copy Menu', 'daily-menu-manager'); ?>">
    <p><?php _e('Select the target date for copying:', 'daily-menu-manager'); ?></p>

    <div class="selectedDateTo">
        <input type="hidden" name="type" value="to">
        <input type="date" 
            name="selectedDateTo" 
            id="selectedDateTo" 
            value="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>"
            data-input>

            <a class="input-button" title="toggle" data-toggle>
                <span class="dashicons dashicons-calendar-alt"></span>
            </a>
    </div>
</div>

<!-- Kopier-Dialog from -->
<div id="copy-menu-dialog-from" style="display: none;" title="<?php _e('Import Menu', 'daily-menu-manager'); ?>">
    <p><?php _e('Select the source date:', 'daily-menu-manager'); ?></p>
    <div class="selectedDateFrom">
        <input type="hidden" name="type" value="from">
        <input type="date" 
            name="selectedDateFrom" 
            id="selectedDateFrom" 
            value="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>"
            data-input>

            <a class="input-button" title="toggle" data-toggle>
                <span class="dashicons dashicons-calendar-alt"></span>
            </a>
    </div>
</div>



<?php if ($current_menu): ?>
    <button type="button" class="button copy-menu" data-menu-id="<?php echo esc_attr($current_menu->id); ?>">
        <?php _e('Copy Menu', 'daily-menu-manager'); ?>
    </button>
<?php endif; ?>