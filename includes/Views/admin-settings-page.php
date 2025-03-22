<?php defined('ABSPATH') or die('Direct access not allowed!'); ?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="daily-menu-settings-container">
        <form method="post" action="" id="daily-menu-settings-form">
            <?php wp_nonce_field('daily_menu_settings_nonce'); ?>
            <input type="hidden" name="save_menu_settings" value="1">
            
            <div class="daily-menu-settings-section">
                <h2><?php _e('Menü-Eigenschaften verwalten', 'daily-menu-manager'); ?></h2>
                <p><?php _e('Hier können Sie benutzerdefinierte Eigenschaften für Menü-Items wie "Vegetarisch", "Vegan", "Glutenfrei" hinzufügen und entfernen.', 'daily-menu-manager'); ?></p>
                
                <table class="form-table" id="daily-menu-properties-table">
                    <thead>
                        <tr>
                            <th><?php _e('Eigenschaftsname', 'daily-menu-manager'); ?></th>
                            <th><?php _e('Aktionen', 'daily-menu-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $properties = \DailyMenuManager\Admin\SettingsController::getMenuProperties();
                        if (!empty($properties)) {
                            foreach ($properties as $key => $property) {
                                ?>
                                <tr class="property-row">
                                    <td>
                                        <input type="text" name="daily_menu_properties[<?php echo $key; ?>]" 
                                               value="<?php echo esc_attr($property); ?>" required>
                                    </td>
                                    <td>
                                        <button type="button" class="button remove-property">
                                            <?php _e('Entfernen', 'daily-menu-manager'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                        <tr id="no-properties-message" style="<?php echo !empty($properties) ? 'display:none;' : ''; ?>">
                            <td colspan="2"><?php _e('Keine Eigenschaften definiert. Fügen Sie unten eine neue Eigenschaft hinzu.', 'daily-menu-manager'); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="daily-menu-add-property">
                    <h3><?php _e('Neue Menü-Eigenschaft hinzufügen', 'daily-menu-manager'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Eigenschaftsname', 'daily-menu-manager'); ?></th>
                            <td>
                                <input type="text" id="new-property-name" placeholder="z.B. Vegetarisch">
                                <p class="description"><?php _e('Name der Eigenschaft, z.B. "Vegetarisch", "Vegan", "Glutenfrei"', 'daily-menu-manager'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <button type="button" class="button button-primary" id="add-property-button">
                        <?php _e('Eigenschaft hinzufügen', 'daily-menu-manager'); ?>
                    </button>
                </div>
                
                <div class="daily-menu-settings-actions">
                    <?php submit_button(__('Einstellungen speichern', 'daily-menu-manager'), 'primary', 'submit', false); ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Neue Eigenschaft hinzufügen
    $('#add-property-button').on('click', function() {
        var name = $('#new-property-name').val();
        if (!name) {
            alert('<?php _e("Bitte geben Sie einen Namen für die Eigenschaft ein.", "daily-menu-manager"); ?>');
            return;
        }
        
        // Neue Zeilennummer generieren
        var rowCount = $('.property-row').length;
        var newIndex = rowCount ? rowCount : 0;
        
        // HTML für die neue Zeile erstellen
        var newRow = '<tr class="property-row">' +
            '<td><input type="text" name="daily_menu_properties[' + newIndex + ']" value="' + name + '" required></td>' +
            '<td><button type="button" class="button remove-property"><?php _e("Entfernen", "daily-menu-manager"); ?></button></td>' +
            '</tr>';
        
        // Neue Zeile zur Tabelle hinzufügen
        $('#no-properties-message').hide();
        $('#daily-menu-properties-table tbody').append(newRow);
        
        // Formularfelder zurücksetzen
        $('#new-property-name').val('');
    });
    
    // Eigenschaft entfernen
    $(document).on('click', '.remove-property', function() {
        if (confirm('<?php _e("Möchten Sie diese Eigenschaft wirklich entfernen?", "daily-menu-manager"); ?>')) {
            $(this).closest('tr').remove();
            
            if ($('.property-row').length === 0) {
                $('#no-properties-message').show();
            }
            
            // Indizes neu nummerieren
            $('.property-row').each(function(index) {
                $(this).find('input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        }
    });
});
</script>

<style>
.daily-menu-settings-container {
    max-width: 100%;
    margin-top: 20px;
}

.daily-menu-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

#daily-menu-properties-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

#daily-menu-properties-table th {
    background-color: #f9f9f9;
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

#daily-menu-properties-table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.daily-menu-add-property {
    background-color: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    margin-top: 20px;
}

.daily-menu-settings-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}
</style>