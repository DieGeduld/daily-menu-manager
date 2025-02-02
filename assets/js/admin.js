jQuery(document).ready(function($) {

    window.dailyMenuAdmin = window.dailyMenuAdmin || {
        ajaxurl: ajaxurl, // WordPress stellt ajaxurl standardmäßig zur Verfügung
        nonce: '',
        messages: {
                    <div class="menu-item-field">
                        <label><?php _e('Verfügbare Menge', 'daily-menu-manager'); ?></label>
                        <input type="number" name="menu_items[new-{id}][available_quantity]" min="0" value="0" required>
                    </div>
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
        handle: '.menu-item-header',
        placeholder: 'menu-item-placeholder',
        tolerance: 'pointer',
        distance: 5, // Minimale Pixelanzahl bevor Drag startet
        cursor: 'move',
        axis: 'y', // Nur vertikales Sortieren erlauben
        opacity: 0.8, // Transparenz während des Ziehens
        
        // Beim Start des Ziehens
        start: function(e, ui) {
            ui.placeholder.height(ui.item.height());
            ui.helper.addClass('is-dragging')
                     .css('box-shadow', '0 2px 8px rgba(0,0,0,0.1)');
        },
        
        // Während des Ziehens
        sort: function(e, ui) {
            // Optional: Scroll-Verhalten anpassen
            var top = e.pageY - $(window).scrollTop();
            if (top < 50) {
                window.scrollBy(0, -5);
            } else if (top > $(window).height() - 50) {
                window.scrollBy(0, 5);
            }
        },
        
        // Nach dem Loslassen
        stop: function(e, ui) {
            ui.item.removeClass('is-dragging')
                  .css('box-shadow', '');
        },
        
        update: function() {
            updateSortOrder();
        }
    });

    $(document).on('click', '.menu-item-header button', function(e) {
        e.stopPropagation(); // Verhindert Bubble-Up zum Header
    });

    // Hover-Effekt für den Header
    $('.menu-item-header').hover(
        function() {
            $(this).addClass('header-hover');
        },
        function() {
            $(this).removeClass('header-hover');
        }
    );


    // Zähler für neue Menü-Items
    let newItemCounter = 0;

    // Hinzufügen neuer Menü-Items
    $('.add-menu-item').on('click', function() {
        const type = $(this).data('type');
        let template = $('#menu-item-template-' + type).html();
        
        // Eindeutige ID für das neue Item
        newItemCounter++;
        template = template.replace(/\{id\}/g, newItemCounter);
        
        // Template in jQuery-Objekt umwandeln für einfachere Manipulation
        const $newItem = $(template);
        
        // Füge Toggle-Button und Controls zum Template hinzu
        const $header = $newItem.find('.menu-item-header');
        const $controls = $('<div class="menu-item-controls"></div>');
        
        // Move-Handle und Toggle-Button zu Controls hinzufügen
        $controls.append(`
            <span class="move-handle dashicons dashicons-move"></span>
            <button type="button" class="toggle-menu-item dashicons dashicons-arrow-down" aria-expanded="true"></button>
        `);
        
        // Bestehenden move-handle entfernen und neue Controls einfügen
        $header.find('.move-handle').remove();
        $header.prepend($controls);
        
        // Stelle sicher, dass der Content-Bereich initial sichtbar ist
        $newItem.find('.menu-item-content').show();
        
        // Event-Handler für Preisformatierung hinzufügen
        $newItem.find('input[type="number"][step="0.01"]').on('change', function() {
            let value = parseFloat($(this).val());
            if (!isNaN(value)) {
                $(this).val(value.toFixed(2));
            }
        });
        
        // Item zur Liste hinzufügen
        $('.menu-items').append($newItem);
        
        // Sortierreihenfolge aktualisieren
        updateSortOrder();
        
        // Optional: Smooth Scroll zum neuen Item
        $('html, body').animate({
            scrollTop: $newItem.offset().top - 100
        }, 500);
        
        // Fokus auf das erste Eingabefeld setzen
        $newItem.find('input[type="text"]').first().focus();
        
        // Event-Handler für Validation
        $newItem.find('input[required]').on('input', function() {
            if ($(this).val()) {
                $(this).removeClass('error');
            }
        });
        
        // Tooltip-Initialisierung für neue Elemente
        $newItem.find('.help-tip').each(function() {
            const $tip = $(this);
            const tipText = $tip.data('tip');
            
            if (tipText) {
                $tip.attr('title', tipText)
                    .attr('aria-label', tipText);
            }
        });
        
        // Optional: Animation beim Hinzufügen
        $newItem.hide().slideDown(300);
        
        // Speichere den Zustand im localStorage
        const itemKey = 'menuItem_new_' + newItemCounter;
        localStorage.setItem(itemKey + '_collapsed', 'false');
        
        // Tastaturnavigation aktivieren
        $newItem.attr('tabindex', '0').on('keydown', function(e) {
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
        
        // Error-Handler für required Felder
        $newItem.find('input[required]').on('invalid', function() {
            $(this).addClass('error');
        });
        
        // Auto-Save Trigger
        $newItem.find('input, textarea').on('change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                // Auto-Save Logik hier implementieren
                console.log('Auto-Save würde jetzt ausgeführt...');
            }, 30000);
        });
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
    
        // Ajax-Update der Sortierung
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
                    if (response.success) {
                        // Eigene Highlight-Animation
                        $('.menu-items').addClass('highlight-success');
                        setTimeout(function() {
                            $('.menu-items').removeClass('highlight-success');
                        }, 1000);
                    } else {
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
                        _ajax_nonce: dailyMenuAdmin.nonce
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

    $('.menu-item').each(function() {
        const $menuItem = $(this);
        const itemId = $menuItem.find('input[name*="[id]"]').val();
        
        if (itemId) {
            const isCollapsed = localStorage.getItem('menuItem_' + itemId + '_collapsed') === 'true';
            if (isCollapsed) {
                const $toggle = $menuItem.find('.toggle-menu-item');
                $toggle
                    .removeClass('dashicons-arrow-down')
                    .addClass('dashicons-arrow-right')
                    .attr('aria-expanded', 'false');
                $menuItem.find('.menu-item-content').hide();
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

    $(document).on('click', '.toggle-menu-item', function(e) {
        e.preventDefault();
        const $menuItem = $(this).closest('.menu-item');
        const $content = $menuItem.find('.menu-item-content');
        const isExpanded = $content.is(':visible');
        
        // Toggle Content
        $content.slideToggle(200);
        
        // Update Button Icon and ARIA state
        $(this)
            .toggleClass('dashicons-arrow-down dashicons-arrow-right')
            .attr('aria-expanded', !isExpanded);
        
        // Optional: Speichere den Zustand im localStorage
        const itemId = $menuItem.find('input[name*="[id]"]').val();
        if (itemId) {
            localStorage.setItem('menuItem_' + itemId + '_collapsed', isExpanded);
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