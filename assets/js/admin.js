jQuery(document).ready(function($) {

    window.dailyMenuAdmin = window.dailyMenuAdmin || {
        ajaxurl: ajaxurl, // WordPress stellt ajaxurl standardmäßig zur Verfügung
        nonce: '',
        messages: {
            copySuccess: 'Menü wurde erfolgreich kopiert!',
            copyError: 'Fehler beim Kopieren des Menüs.',
            saveSuccess: 'Menü wurde gespeichert!',
            saveError: 'Fehler beim Speichern des Menüs.',
            deleteConfirm: 'Möchten Sie dieses Menü-Item wirklich löschen?',
            selectDate: 'Bitte wählen Sie ein Datum.',
            noItems: 'Bitte fügen Sie mindestens ein Menü-Item hinzu.',
            requiredFields: 'Bitte füllen Sie alle Pflichtfelder aus.',
            copy: 'Kopieren',
            cancel: 'Abbrechen'
        }
    };

    // Menü-Items sortierbar machen
    $('.menu-items').sortable({
        handle: '.move-handle',
        update: function() {
            updateSortOrder();
        }
    });

    // Zähler für neue Menü-Items
    let newItemCounter = 0;

    // Hinzufügen neuer Menü-Items
    $('.add-menu-item').on('click', function() {
        const type = $(this).data('type');
        let template = $('#menu-item-template-' + type).html();
        
        // Eindeutige ID für das neue Item
        newItemCounter++;
        template = template.replace(/\{id\}/g, newItemCounter);
        
        $('.menu-items').append(template);
        updateSortOrder();
    });

    // Entfernen von Menü-Items
    $(document).on('click', '.remove-menu-item', function() {
        if (confirm(dailyMenuAdmin.messages.deleteConfirm)) {
            $(this).closest('.menu-item').remove();
            updateSortOrder();
        }
    });

    // Aktualisieren der Sortierreihenfolge
    function updateSortOrder() {
        $('.menu-item').each(function(index) {
            $(this).find('.sort-order').val(index + 1);
        });

        // Optional: AJAX-Update der Sortierung
        const itemOrder = {};
        $('.menu-item').each(function(index) {
            const itemId = $(this).data('id');
            if (itemId) {
                itemOrder[itemId] = index + 1;
            }
        });

        if (Object.keys(itemOrder).length > 0) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_menu_order',
                    item_order: itemOrder,
                    _ajax_nonce: dailyMenuAdmin.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        console.error('Fehler beim Speichern der Sortierung');
                    }
                }
            });
        }
    }

    // Menü kopieren Dialog
    $('#copy-menu-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 400,
        resizable: false,
        closeOnEscape: true,
        buttons: {
            [dailyMenuAdmin.messages.copy || "Kopieren"]: function() {
                const dialog = $(this);
                const newDate = $('#copy-menu-date').val();
                const menuId = $('.copy-menu').data('menu-id');
                
                if (!newDate) {
                    alert(dailyMenuAdmin.messages.selectDate || 'Bitte wählen Sie ein Datum.');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'copy_menu',
                        menu_id: menuId,
                        new_date: newDate,
                        nonce: dailyMenuAdmin.nonce
                    },
                    beforeSend: function() {
                        dialog.find('button').prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(dailyMenuAdmin.messages.copySuccess);
                            window.location.href = window.location.pathname + 
                                '?page=daily-menu-manager&menu_date=' + newDate;
                        } else {
                            alert(response.data.message || dailyMenuAdmin.messages.copyError);
                        }
                    },
                    error: function() {
                        alert(dailyMenuAdmin.messages.copyError);
                    },
                    complete: function() {
                        dialog.find('button').prop('disabled', false);
                        dialog.dialog('close');
                    }
                });
            },
            [dailyMenuAdmin.messages.cancel || "Abbrechen"]: function() {
                $(this).dialog('close');
            }
        }
    });

    // Copy Button Click Handler
    $('.copy-menu').on('click', function() {
        $('#copy-menu-dialog').dialog('open');
    });

    // Preisformatierung
    $(document).on('change', 'input[type="number"][step="0.01"]', function() {
        let value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });

    // Formvalidierung vor dem Speichern
    $('.menu-form').on('submit', function(e) {
        let valid = true;
        let firstError = null;

        // Prüfe ob mindestens ein Menü-Item existiert
        if ($('.menu-item').length === 0) {
            alert(dailyMenuAdmin.messages.noItems || 'Bitte fügen Sie mindestens ein Menü-Item hinzu.');
            return false;
        }

        // Validiere alle Pflichtfelder
        $('.menu-item').each(function() {
            const $item = $(this);
            $item.find('input[required]').each(function() {
                if (!$(this).val()) {
                    valid = false;
                    $(this).addClass('error');
                    if (!firstError) firstError = $(this);
                } else {
                    $(this).removeClass('error');
                }
            });
        });

        if (!valid) {
            e.preventDefault();
            alert(dailyMenuAdmin.messages.requiredFields || 'Bitte füllen Sie alle Pflichtfelder aus.');
            if (firstError) {
                firstError.focus();
            }
            return false;
        }

        // Speicherbestätigung
        //return confirm(dailyMenuAdmin.messages.saveConfirm || 'Möchten Sie das Menü speichern?');
    });

    // Datum-Navigation
    $('#menu_date').on('change', function() {
        if ($(this).closest('form').find('input[name="save_menu"]').length === 0) {
            $(this).closest('form').submit();
        } else {
            if (confirm(dailyMenuAdmin.messages.unsavedChanges || 'Es gibt ungespeicherte Änderungen. Möchten Sie die Seite wirklich verlassen?')) {
                window.location.href = window.location.pathname + 
                    '?page=daily-menu-manager&menu_date=' + $(this).val();
            }
        }
    });

    // Error-Klasse bei Fokus entfernen
    $(document).on('focus', 'input.error', function() {
        $(this).removeClass('error');
    });

    // Initialize Tooltips
    $('.help-tip').each(function() {
        const $tip = $(this);
        const tipText = $tip.data('tip');
        
        if (tipText) {
            $tip.attr('title', tipText)  // Für native Browser Tooltips
                .attr('aria-label', tipText);  // Für Accessibility
        }
    });

    // Tastaturnavigation für Menü-Items
    $('.menu-items').on('keydown', '.menu-item', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'ArrowUp':
                    e.preventDefault();
                    $(this).insertBefore($(this).prev('.menu-item'));
                    updateSortOrder();
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    $(this).insertAfter($(this).next('.menu-item'));
                    updateSortOrder();
                    break;
            }
        }
    });

    // Auto-Save Feature (optional)
    let autoSaveTimer = null;
    $('.menu-form').on('change', 'input, textarea', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Auto-Save Logik hier implementieren
            console.log('Auto-Save würde jetzt ausgeführt...');
        }, 30000); // Auto-Save nach 30 Sekunden Inaktivität
    });

    // Initialisiere Select2 für bessere Dropdown-Menüs (falls verwendet)
    if ($.fn.select2) {
        $('.select2-enable').select2();
    }
});