<?php
/**
 * Admin Settings Page Template
 *
 * @package DailyMenuManager
 */

use DailyMenuManager\Controller\Admin\SettingsController;
use DailyMenuManager\Helper\DateUtils;

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$properties = self::getMenuProperties();
$main_color = self::getMainColor();
$currency = self::getCurrency();
$available_currencies = self::getAvailableCurrencies();
$custom_currency_symbol = self::getCustomCurrencySymbol();
$price_format = self::getPriceFormat();
$available_price_formats = self::getAvailablePriceFormats();
$consumption_types = self::getConsumptionTypes();
$menu_types = self::getMenuTypes();

// Get database version info
$current_version = get_option('daily_menu_manager_version', '0.0.0');
$needs_update = version_compare($current_version, DMM_VERSION, '<');

// Display any settings errors
settings_errors('daily_menu_properties');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div id="settings-tabs">
        <ul>
            <li><a href="#tab-general"><?php _e('General Settings', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-menu-properties"><?php _e('Menu Properties', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-menu-types"><?php _e('Menu Types', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-appearance"><?php _e('Appearance', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-consumption-types"><?php _e('Consumption Types', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-order-times"><?php _e('Order Times', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-database"><?php _e('Database', 'daily-menu-manager'); ?></a></li>
        </ul>
        
        <form method="post" action="">
            <?php wp_nonce_field('daily_menu_settings_nonce'); ?>
            
            <div id="tab-general">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Currency', 'daily-menu-manager'); ?></th>
                        <td>
                            <select name="daily_menu_currency" id="daily_menu_currency" class="regular-text">
                                <?php foreach ($available_currencies as $currency_code => $currency_name) : ?>
                                    <option value="<?php echo esc_attr($currency_code); ?>" <?php selected($currency, $currency_code); ?>>
                                        <?php echo esc_html($currency_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="custom_currency_container" style="margin-top: 10px; <?php echo ($currency !== 'custom') ? 'display: none;' : ''; ?>">
                                <label for="daily_menu_custom_currency_symbol"><?php _e('Custom currency symbol:', 'daily-menu-manager'); ?></label>
                                <input type="text" 
                                    name="daily_menu_custom_currency_symbol" 
                                    id="daily_menu_custom_currency_symbol"
                                    value="<?php echo esc_attr($custom_currency_symbol); ?>" 
                                    class="regular-text" 
                                    placeholder="<?php echo SettingsController::getCurrencySymbol(); ?>" />
                                <p class="description"><?php _e('Enter your custom currency symbol (e.g. €, $, £).', 'daily-menu-manager'); ?></p>
                            </div>
                            <p class="description"><?php _e('Select the currency for displaying prices.', 'daily-menu-manager'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Price format', 'daily-menu-manager'); ?></th>
                        <td>
                            <select name="daily_menu_price_format" class="regular-text">
                                <?php foreach ($available_price_formats as $format_key => $format_name) : ?>
                                    <option value="<?php echo esc_attr($format_key); ?>" <?php selected($price_format, $format_key); ?>>
                                        <?php 
                                            echo esc_html($format_name); 
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Choose how prices should be displayed in the menu.', 'daily-menu-manager'); ?></p>
                            <p class="description">
                                <?php _e('Current price format example:', 'daily-menu-manager'); ?> 
                                <strong><?php echo self::formatPrice(9.99); ?></strong>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Time format', 'daily-menu-manager'); ?></th>
                        <td>
                            <select name="daily_menu_time_format" class="regular-text">
                                <option value="H:i" <?php selected(self::getTimeFormat(), 'H:i'); ?>>
                                    <?php _e('24-hour format (14:30)', 'daily-menu-manager'); ?>
                                </option>
                                <option value="g:i A" <?php selected(self::getTimeFormat(), 'g:i A'); ?>>
                                    <?php _e('12-hour format with AM/PM (2:30 PM)', 'daily-menu-manager'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Choose how times should be displayed throughout the plugin.', 'daily-menu-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-menu-properties">
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
                </table>
            </div>

            <div id="tab-menu-types">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Menu types', 'daily-menu-manager'); ?></th>
                        <td>
                            <div id="menu-types-container">
                                <?php if (!empty($menu_types)) : ?>
                                    <?php foreach ($menu_types as $type_key => $type_data) : ?>
                                        <div class="menu-type-row">
                                            <input type="text" 
                                                name="daily_menu_types_labels[]" 
                                                value="<?php echo esc_attr($type_data['label']); ?>" 
                                                class="regular-text menu-type-label" 
                                                placeholder="<?php _e('Menu Type Name (Singular)', 'daily-menu-manager'); ?>" />
                                            <input type="text" 
                                                name="daily_menu_types_plurals[]" 
                                                value="<?php echo esc_attr($type_data['plural'] ?? ''); ?>" 
                                                class="regular-text menu-type-plural" 
                                                placeholder="<?php _e('Menu Type Name (Plural)', 'daily-menu-manager'); ?>" />
                                            <button type="button" class="button remove-menu-type"><?php _e('Remove', 'daily-menu-manager'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="menu-type-row">
                                        <input type="text" 
                                            name="daily_menu_types_labels[]" 
                                            value="" 
                                            class="regular-text menu-type-label" 
                                            placeholder="<?php _e('Menu Type Name (Singular)', 'daily-menu-manager'); ?>" />
                                        <input type="text" 
                                            name="daily_menu_types_plurals[]" 
                                            value="" 
                                            class="regular-text menu-type-plural" 
                                            placeholder="<?php _e('Menu Type Name (Plural)', 'daily-menu-manager'); ?>" />
                                        <button type="button" class="button remove-menu-type"><?php _e('Remove', 'daily-menu-manager'); ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button add-menu-type"><?php _e('Add menu type', 'daily-menu-manager'); ?></button>
                            <p class="description"><?php _e('Define the types of menu items (e.g., Appetizer/Appetizers, Main Course/Main Courses, Dessert/Desserts).', 'daily-menu-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-appearance">
                <table class="form-table">
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
            </div>

            <div id="tab-consumption-types">
                <table class="form-table">
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
            </div>

            <div id="tab-order-times">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Order time settings', 'daily-menu-manager'); ?></th>
                        <td>
                            <?php $order_times = self::getOrderTimes(); ?>
                            <div class="order-times-container">
                                <div class="order-time-field order-time-field-start-container">
                                    <label for="daily_menu_order_times[start_time]"><?php _e('Start Time', 'daily-menu-manager'); ?></label>
                                    <input data-no-calendar="true" 
                                            data-alt-format="<?php echo esc_attr(self::getTimeFormat() == "g:i A" ? "h:i K" : self::getTimeFormat()); ?>"
                                            data-time_24hr="<?php echo esc_attr((self::getTimeFormat() == "H:i") ? 'true' : 'false'); ?>"
                                            data-alt-input=true
                                            data-date-format="H:i"
                                            data-enable-time=true
                                    type="time" 
                                        name="daily_menu_order_times[start_time]" 
                                        value="<?php echo esc_attr(DateUtils::convertTimeToFormat($order_times['start_time'], self::getTimeFormat())); ?>" 
                                        class="order-time-field-start" />
                                </div>
                                <div class="order-time-field order-time-field-end-container">
                                    <label for="daily_menu_order_times[end_time]"><?php _e('End Time', 'daily-menu-manager'); ?></label>
                                    <input data-no-calendar="true" 
                                            data-alt-format="<?php echo esc_attr(self::getTimeFormat() == "g:i A" ? "h:i K" : self::getTimeFormat()); ?>"
                                            data-time_24hr="<?php echo esc_attr((self::getTimeFormat() == "H:i") ? 'true' : 'false'); ?>"
                                            data-alt-input=true
                                            data-date-format="H:i"
                                            data-enable-time=true
                                    type="time" 
                                        name="daily_menu_order_times[end_time]" 
                                        value="<?php echo esc_attr(DateUtils::convertTimeToFormat($order_times['end_time']), self::getTimeFormat()); ?>" 
                                        class="order-time-field-end" />
                                </div>
                                <div class="order-time-field order-time-field-interval-container">
                                    <label for="daily_menu_order_times[interval]"><?php _e('Time Interval (minutes)', 'daily-menu-manager'); ?></label>
                                    <input type="number" 
                                        name="daily_menu_order_times[interval]" 
                                        value="<?php echo esc_attr($order_times['interval']); ?>" 
                                        min="5"
                                        max="120"
                                        step="5"
                                        class="order-time-field-interval" />
                                </div>
                            </div>
                            <p class="description"><?php _e('Define when orders can be placed and picked up. The time interval determines the available pickup time slots.', 'daily-menu-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-database">
                <h2><?php _e('Database Management', 'daily-menu-manager'); ?></h2>
                
                <?php if ($needs_update): ?>
                    <div class="dmm-migration-notice">
                        <p><strong><?php _e('Database Update Required', 'daily-menu-manager'); ?></strong></p>
                        <p>
                            <?php printf(
                                esc_html__('The database needs to be updated from version %s to %s. Please back up your database before proceeding with the update.', 'daily-menu-manager'),
                                esc_html($current_version),
                                esc_html(DMM_VERSION)
                            ); ?>
                        </p>
                        <!-- Single button within the same form -->
                        <input type="submit" name="run_migrations" class="button button-primary" 
                            value="<?php _e('Update Database', 'daily-menu-manager'); ?>" />
                    </div>
                <?php else: ?>
                    <div class="notice notice-success inline">
                        <p>
                            <?php printf(
                                esc_html__('Database is up to date (Version %s)', 'daily-menu-manager'),
                                esc_html($current_version)
                            ); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <p class="submit">
                <input type="submit" name="save_menu_settings" class="button-primary" value="<?php _e('Save settings', 'daily-menu-manager'); ?>" />
            </p>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize tabs
    $("#settings-tabs").tabs();
    
    // Show/hide custom currency input based on selection
    $('#daily_menu_currency').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom_currency_container').show();
        } else {
            $('#custom_currency_container').hide();
        }
    });
    
    // Live update price format example when currency or format changes
    $('#daily_menu_currency, select[name="daily_menu_price_format"], #daily_menu_custom_currency_symbol').on('change keyup', function() {
        updatePriceFormatExamples();
    });
    
    function updatePriceFormatExamples() {
        const currencySelect = $('#daily_menu_currency');
        const selectedCurrency = currencySelect.val();
        const customSymbol = $('#daily_menu_custom_currency_symbol').val();
        
        // Update the examples in the dropdown options
        // This would ideally be done with AJAX, but for simplicity,
        // we're just showing the user that they need to save to see updates
        $('p.description strong').text(function() {
            return '<?php _e("Save settings to update example", "daily-menu-manager"); ?>';
        });
    }
    
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
    
    // Add menu type
    $('.add-menu-type').on('click', function() {
        var newRow = '<div class="menu-type-row">' +
            '<input type="text" name="daily_menu_types_labels[]" value="" class="regular-text menu-type-label" placeholder="<?php _e('Menu Type Name (Singular)', 'daily-menu-manager'); ?>" />' +
            '<input type="text" name="daily_menu_types_plurals[]" value="" class="regular-text menu-type-plural" placeholder="<?php _e('Menu Type Name (Plural)', 'daily-menu-manager'); ?>" />' +
            '<button type="button" class="button remove-menu-type"><?php _e('Remove', 'daily-menu-manager'); ?></button>' +
            '</div>';
        $('#menu-types-container').append(newRow);
    });
    
    // Remove menu type
    $('#menu-types-container').on('click', '.remove-menu-type', function() {
        if ($('.menu-type-row').length > 1) {
            $(this).parent().remove();
        } else {
            $(this).siblings('input').val('');
        }
    });
});
</script>