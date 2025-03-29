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
});
</script>