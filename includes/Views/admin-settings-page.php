<?php
/**
 * Admin Settings Page Template
 *
 * @package DailyMenuManager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$properties = self::getMenuProperties();
$main_color = self::getMainColor();
$date_format = self::getDateFormat();
$available_date_formats = self::getAvailableDateFormats();
$consumption_types = self::getConsumptionTypes();

// Display any settings errors
settings_errors('daily_menu_properties');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('daily_menu_settings_nonce'); ?>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Menu properties', 'daily-menu-manager'); ?></th>
                <td>
                    <div id="menu-properties-container">
                        <?php if (!empty($properties)) : ?>
                            <?php foreach ($properties as $index => $property) : ?>
                                <div class="property-row">
                                    <input type="text" 
                                           name="daily_menu_properties[]" 
                                           value="<?php echo esc_attr($property); ?>" 
                                           class="regular-text" />
                                    <button type="button" class="button remove-property"><?php _e('Remove', 'daily-menu-manager'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="property-row">
                                <input type="text" 
                                       name="daily_menu_properties[]" 
                                       value="" 
                                       class="regular-text" />
                                <button type="button" class="button remove-property"><?php _e('Remove', 'daily-menu-manager'); ?></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button add-property"><?php _e('Add property', 'daily-menu-manager'); ?></button>
                    <p class="description"><?php _e('Define the properties that can be selected for menus here.', 'daily-menu-manager'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Main color', 'daily-menu-manager'); ?></th>
                <td>
                    <input type="color" 
                           name="daily_menu_main_color" 
                           value="<?php echo esc_attr($main_color); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Select a main color.', 'daily-menu-manager'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Date format', 'daily-menu-manager'); ?></th>
                <td>
                    <select name="daily_menu_date_format" class="regular-text">
                        <?php foreach ($available_date_formats as $format_key => $format_description) : ?>
                            <option value="<?php echo esc_attr($format_key); ?>" <?php selected($date_format, $format_key); ?>>
                                <?php echo esc_html($format_description); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Choose how dates should be displayed in the menu.', 'daily-menu-manager'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Consumption types', 'daily-menu-manager'); ?></th>
                <td>
                    <div id="consumption-types-container">
                        <?php if (!empty($consumption_types)) : ?>
                            <?php foreach ($consumption_types as $index => $type) : ?>
                                <div class="consumption-type-row">
                                    <input type="text" 
                                           name="daily_menu_consumption_types[]" 
                                           value="<?php echo esc_attr($type); ?>" 
                                           class="regular-text" />
                                    <button type="button" class="button remove-consumption-type"><?php _e('Remove', 'daily-menu-manager'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="consumption-type-row">
                                <input type="text" 
                                       name="daily_menu_consumption_types[]" 
                                       value="" 
                                       class="regular-text" />
                                <button type="button" class="button remove-consumption-type"><?php _e('Remove', 'daily-menu-manager'); ?></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button add-consumption-type"><?php _e('Add consumption type', 'daily-menu-manager'); ?></button>
                    <p class="description"><?php _e('Define the consumption types for orders (e.g., Pick up, Eat in).', 'daily-menu-manager'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="save_menu_settings" class="button-primary" value="<?php _e('Save settings', 'daily-menu-manager'); ?>" />
        </p>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Add property
    $('.add-property').on('click', function() {
        var newRow = '<div class="property-row">' +
            '<input type="text" name="daily_menu_properties[]" value="" class="regular-text" />' +
            '<button type="button" class="button remove-property"><?php _e('Remove', 'daily-menu-manager'); ?></button>' +
            '</div>';
        $('#menu-properties-container').append(newRow);
    });
    
    // Remove property
    $('#menu-properties-container').on('click', '.remove-property', function() {
        if ($('.property-row').length > 1) {
            $(this).parent().remove();
        } else {
            $(this).prev('input').val('');
        }
    });
    
    // Add consumption type
    $('.add-consumption-type').on('click', function() {
        var newRow = '<div class="consumption-type-row">' +
            '<input type="text" name="daily_menu_consumption_types[]" value="" class="regular-text" />' +
            '<button type="button" class="button remove-consumption-type"><?php _e('Remove', 'daily-menu-manager'); ?></button>' +
            '</div>';
        $('#consumption-types-container').append(newRow);
    });
    
    // Remove consumption type
    $('#consumption-types-container').on('click', '.remove-consumption-type', function() {
        if ($('.consumption-type-row').length > 1) {
            $(this).parent().remove();
        } else {
            $(this).prev('input').val('');
        }
    });
});
</script>