<?php
/**
 * Template for rendering a single menu item in the admin area
 *
 * @var MenuItem $item The menu item to render
 * @var array $item_config The configuration for the menu type
 * @var bool $is_collapsed Whether the item is collapsed
 * @var string $collapse_class CSS class for collapsed state
 */

// Ensure we have access to the SettingsController
use DailyMenuManager\Controller\Admin\SettingsController;

// Safety check in case $item is not defined
if (! isset($item)) {
    return;
}
?>

<div class="menu-item <?php echo esc_attr($collapse_class); ?>" 
     data-type="<?php echo esc_attr($item->item_type); ?>"
     data-id="<?php echo esc_attr($item->id); ?>">
    
    <!-- Header Section -->
    <div class="menu-item-header">
        <!-- Left Controls -->
        <div class="menu-item-controls">
            <span class="move-handle dashicons dashicons-move" 
                  title="<?php esc_attr_e('Drag to reorder', 'daily-menu-manager'); ?>"
                  aria-label="<?php esc_attr_e('Drag handle', 'daily-menu-manager'); ?>">
            </span>
            <button type="button" 
                    class="toggle-menu-item dashicons <?php echo $is_collapsed ? 'dashicons-arrow-right' : 'dashicons-arrow-down'; ?>"
                    aria-expanded="<?php echo $is_collapsed ? 'false' : 'true'; ?>"
                    aria-label="<?php esc_attr_e('Toggle menu item', 'daily-menu-manager'); ?>"
                    title="<?php esc_attr_e('Click to expand/collapse', 'daily-menu-manager'); ?>">
            </button>
        </div>

        <!-- Title Area -->
        <div class="menu-item-title-area">
            <span class="menu-item-type-label"><?php esc_attr_e($item_config['label'], 'daily-menu-manager') . ":"; ?></span>
            <span class="menu-item-title-preview"><?php esc_attr_e($item->title ?: '(No title)', 'daily-menu-manager'); ?></span>
        </div>

        <!-- Right Controls -->
        <div class="menu-item-actions">
            <button type="button" 
                    class="copy-menu-item dashicons dashicons-move"
                    title="<?php esc_attr_e('Copy this menu item to another day', 'daily-menu-manager'); ?>"
                    aria-label="<?php esc_attr_e('Copy this menu item to another day', 'daily-menu-manager'); ?>">
            </button>  
            <button type="button" 
                    class="duplicate-menu-item dashicons dashicons-admin-page"
                    title="<?php esc_attr_e('Duplicate item', 'daily-menu-manager'); ?>"
                    aria-label="<?php esc_attr_e('Duplicate this menu item', 'daily-menu-manager'); ?>">
            </button>
            <button type="button" 
                    class="remove-menu-item dashicons dashicons-trash"
                    title="<?php esc_attr_e('Delete item', 'daily-menu-manager'); ?>"
                    aria-label="<?php esc_attr_e('Delete this menu item', 'daily-menu-manager'); ?>">
            </button>
        </div>
    </div>

    <!-- Content Section -->
    <div class="menu-item-content" <?php echo $is_collapsed ? 'style="display: none;"' : ''; ?>>
        <!-- Hidden Fields -->
        <input type="hidden" name="menu_items[<?php echo esc_attr($item->id); ?>][id]" 
               value="<?php echo esc_attr($item->id); ?>">
        <input type="hidden" name="menu_items[<?php echo esc_attr($item->id); ?>][type]" 
               value="<?php echo esc_attr($item->item_type); ?>">
        <input type="hidden" name="menu_items[<?php echo esc_attr($item->id); ?>][sort_order]" 
               value="<?php echo esc_attr($item->sort_order); ?>" 
               class="sort-order">

        <!-- Title Field -->
        <div class="menu-item-field">
            <label for="title_<?php echo esc_attr($item->id); ?>">
                <?php _e('Title', 'daily-menu-manager'); ?>
                <span class="required">*</span>
            </label>
            <input type="text" 
                   id="title_<?php echo esc_attr($item->id); ?>"
                   name="menu_items[<?php echo esc_attr($item->id); ?>][title]"
                   value="<?php echo esc_attr($item->title); ?>"
                   required
                   class="menu-item-title-input"
                   data-original-value="<?php echo esc_attr($item->title); ?>">
            <span class="field-description">
                <?php _e('Enter the name of the dish or menu item', 'daily-menu-manager'); ?>
            </span>
        </div>

        <!-- Description Field -->
        <div class="menu-item-field">
            <label for="description_<?php echo esc_attr($item->id); ?>">
                <?php _e('Description', 'daily-menu-manager'); ?>
            </label>
            <textarea id="description_<?php echo esc_attr($item->id); ?>"
                      name="menu_items[<?php echo esc_attr($item->id); ?>][description]"
                      class="menu-item-description"
                      rows="3"
                      data-original-value="<?php echo esc_attr($item->description); ?>"><?php
                echo esc_textarea($item->description);
?></textarea>
            <span class="field-description">
                <?php _e('Optional: Add ingredients or other details about this item', 'daily-menu-manager'); ?>
            </span>
        </div>

        <!-- Price Field -->
        <div class="menu-item-field">
            <label for="price_<?php echo esc_attr($item->id); ?>">
                <?php _e('Price', 'daily-menu-manager'); ?>
                <span class="required">*</span>
            </label>
            <div class="price-input-wrapper">
                <span class="currency-symbol"><?php echo esc_html(SettingsController::getCurrencySymbol()); ?></span>
                <!-- Todo: Format price in selected format -->
                <input type="number" 
                    id="price_<?php echo esc_attr($item->id); ?>"
                    name="menu_items[<?php echo esc_attr($item->id); ?>][price]"
                    value="<?php echo esc_attr(number_format($item->price, 2, '.', '')); ?>"
                    step="0.01"
                    min="0"
                    required
                    class="menu-item-price">
            </div>
            <span class="field-description">
                <?php _e('Enter the price for this item (e.g., 12,50)', 'daily-menu-manager'); ?>
            </span>
        </div>

        <!-- Available Quantity Field -->
        <div class="menu-item-field">
            <label for="available_quantity_<?php echo esc_attr($item->id); ?>">
                <?php _e('Available Quantity', 'daily-menu-manager'); ?>
            </label>
            <input type="number" 
                id="available_quantity_<?php echo esc_attr($item->id); ?>"
                name="menu_items[<?php echo esc_attr($item->id); ?>][available_quantity]"
                value="<?php echo esc_attr($item->available_quantity); ?>"
                min="0"
                class="menu-item-available-quantity">
            <span class="field-description">
                <?php _e('Enter the available quantity for this item', 'daily-menu-manager'); ?>
            </span>
        </div>

        <!-- Additional Options Field -->
        <div class="menu-item-field">
            <label for="options_<?php echo esc_attr($item->id); ?>">
                <?php _e('Additional Options', 'daily-menu-manager'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=daily-menu-manager-settings')); ?>"><?php _e('Manage additional options', 'daily-menu-manager'); ?></a>
            </label>
            <div class="options-grid">
            <?php
                $allProps = SettingsController::getMenuProperties() ?? [];
$props = $item->properties ?? [];

foreach ($allProps as $prop): ?>
                <label class="checkbox-label">
                    <input type="checkbox" 
                           name="menu_items[<?php echo esc_attr($item->id); ?>][properties][<?php echo esc_attr($prop); ?>]"
                           <?php checked(isset($props[$prop]) && $props[$prop]); ?>>
                    <?php echo esc_html($prop); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Allergen Information Field -->
        <div class="menu-item-field">
            <label for="allergens_<?php echo esc_attr($item->id); ?>">
                <?php _e('Allergen Information', 'daily-menu-manager'); ?>
            </label>
            <textarea id="allergens_<?php echo esc_attr($item->id); ?>"
                      name="menu_items[<?php echo esc_attr($item->id); ?>][allergens]"
                      class="menu-item-allergens"
                      rows="2"><?php
echo esc_textarea($item->allergens);
?></textarea>
            <span class="field-description">
                <?php _e('List any allergens present in this dish', 'daily-menu-manager'); ?>
            </span>
        </div>

        <!-- Advanced Settings (Initially Hidden) -->
        <div class="advanced-settings" style="display: none;">
            <button type="button" class="toggle-advanced-settings">
                <?php _e('Advanced Settings', 'daily-menu-manager'); ?>
            </button>
            <div class="advanced-settings-content">
                <!-- Availability Times -->
                <div class="menu-item-field">
                    <label>
                        <?php _e('Availability Times', 'daily-menu-manager'); ?>
                    </label>
                    <div class="time-range-inputs">
                        <input type="time" 
                               name="menu_items[<?php echo esc_attr($item->id); ?>][available_from]"
                               value="<?php echo esc_attr(isset($item->available_from) ? $item->available_from : ''); ?>">
                        <span>-</span>
                        <input type="time" 
                               name="menu_items[<?php echo esc_attr($item->id); ?>][available_until]"
                               value="<?php echo esc_attr(isset($item->available_until) ? $item->available_until : ''); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>