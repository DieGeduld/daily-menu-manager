<?php
/**
 * Admin Settings Page Template
 *
 * @package DailyMenuManager
 */

use DailyMenuManager\Admin\SettingsController;

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

// Display any settings errors
settings_errors('daily_menu_properties');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div id="settings-tabs">
        <ul>
            <li><a href="#tab-general"><?php _e('General Settings', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-menu-properties"><?php _e('Menu Properties', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-appearance"><?php _e('Appearance', 'daily-menu-manager'); ?></a></li>
            <li><a href="#tab-consumption-types"><?php _e('Consumption Types', 'daily-menu-manager'); ?></a></li>
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
});
</script>