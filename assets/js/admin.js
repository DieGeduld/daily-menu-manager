jQuery(document).ready(function($) {

  var notyf = new Notyf({
    position: {
      x: 'right',
      y: 'top',
    },
  });

  // Template Management
    let templateItem;
    const $existingItem = $('.menu-item').first();
    if ($existingItem.length) {
        templateItem = $existingItem.clone();
    }

    // Zähler für neue Menü-Items
    let newItemCounter = 0;

    // Event Handlers
    function handlePriceInput() {
        let value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    }

    function handleQuantityInput() {
        let value = parseInt($(this).val());
        if (isNaN(value) || value < 0) {
            $(this).val(0);
        }
    }

    function handleRequiredInput() {
        $(this).toggleClass('error', !$(this).val().trim());
    }

    // Zentrale Event-Initialisierung
    function initializeMenuItemEvents($item) {
        $item.find('input[type="number"][step="0.01"]').on('change', handlePriceInput);
        $item.find('.menu-item-available-quantity').on('change', handleQuantityInput);
        $item.find('input[required]').on('input', handleRequiredInput);
    }

    // State Management
    function updateItemState($item, isCollapsed) {
        const itemId = $item.find('input[name*="[id]"]').val();
        if (itemId) {
            localStorage.setItem('menuItem_' + itemId + '_collapsed', isCollapsed);
            $item.find('.toggle-menu-item')
                .toggleClass('dashicons-arrow-down dashicons-arrow-right')
                .attr('aria-expanded', !isCollapsed);
            $item.find('.menu-item-content')[isCollapsed ? 'slideUp' : 'slideDown'](200);
        }
    }

    // Validierung
    function validateMenuItem($item) {
        let isValid = true;
        const errors = [];
        
        const price = parseFloat($item.find('input[name*="[price]"]').val());
        if (isNaN(price) || price < 0) {
            errors.push('Ungültiger Preis');
            isValid = false;
        }
        
        const title = $item.find('input[name*="[title]"]').val().trim();
        if (!title) {
            errors.push('Titel ist erforderlich');
            isValid = false;
        }

        const quantity = parseInt($item.find('input[name*="[available_quantity]"]').val());
        if (isNaN(quantity) || quantity < 0) {
            errors.push('Ungültige Menge');
            isValid = false;
        }
        
        return { isValid, errors };
    }

    // Accessibility
    function makeMenuItemAccessible($item) {
        const itemId = $item.find('input[name*="[id]"]').val() || 'new-' + newItemCounter;
        const $toggle = $item.find('.toggle-menu-item');
        
        $toggle.attr({
            'aria-controls': 'content-' + itemId,
            'aria-expanded': 'true',
            'aria-label': 'Menü-Item ein-/ausklappen'
        });
        
        $item.find('.menu-item-content').attr({
            'id': 'content-' + itemId,
            'role': 'region',
            'aria-labelledby': 'header-' + itemId
        });
    }

    // Benutzer-Feedback
    function showFeedback(message, type = 'success') {
      if (type === 'error') {
        notyf.error(message);
      } else {
        notyf.success(message);
      }
    }

    // Sortierung
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
        
        stop: function(e, ui) {
            ui.item.removeClass('is-dragging')
                  .css('box-shadow', '');
        },
        
        update: function() {
            updateSortOrder();
            showFeedback('Reihenfolge aktualisiert');
        }
    });

    // Hinzufügen neuer Menü-Items
    $('.add-menu-item').on('click', function() {
        const type = $(this).data('type');

        // Template-Verfügbarkeit prüfen
        if (!$('#menu-item-template-' + type).length && !templateItem) {
            console.error('Kein Template für Menü-Typ gefunden:', type);
            showFeedback('Fehler beim Erstellen des Menü-Items', 'error');
            return;
        }

        newItemCounter++;


        let template = $('#menu-item-template-' + type).html();
        template = template.replace(/\{id\}/g, newItemCounter);
        let $newItem = $(template);
        
        // Type aktualisieren
        $newItem.attr('data-type', type)
                .find('input[name*="[type]"]').val(type);
        
        // Label aktualisieren

        const menuType = window.dailyMenuAdmin.menuTypes[type]?.label || type;
        $newItem.find('.menu-item-type-label').text(menuType + ':');
        
        // Events initialisieren
        initializeMenuItemEvents($newItem);
        makeMenuItemAccessible($newItem);
        
        // Item hinzufügen
        $('.menu-items').append($newItem);
        updateSortOrder();
        
        // UI Feedback
        $newItem.hide().slideDown(300);
        showFeedback('Neues Menü-Item hinzugefügt');
        
        // Scroll zum neuen Item
        $('html, body').animate({
            scrollTop: $newItem.offset().top - 100
        }, 500);
        
        // Fokus setzen
        $newItem.find('input[type="text"]').first().focus();
    });

    // Event Delegation für bestehende Items
    $('.menu-items')
        .on('click', '.remove-menu-item', function() {
            const $item = $(this).closest('.menu-item');
            const itemId = $item.find('input[name*="[id]"]').val();
            
            Swal.fire({
              title: "Are you sure?",
              text: "You won't be able to revert this!",
              icon: "warning",
              showCancelButton: true,
              confirmButtonText: "Yes, delete it!",
              cancelButtonText: "No, cancel!",
              reverseButtons: true
            }).then((result) => {
              if (result.isConfirmed) {
                if (itemId && !itemId.startsWith('new-')) {
                  $.ajax({
                      url: window.dailyMenuAdmin.ajaxurl,
                      type: 'POST',
                      data: {
                          action: 'delete_menu_item',
                          item_id: itemId,
                          _ajax_nonce: window.dailyMenuAdmin.nonce
                      },
                      beforeSend: function() {
                          $item.addClass('deleting');
                      },
                      success: function(response) {
                          if (response.success) {
                              $item.fadeOut(300, function() {
                                  localStorage.removeItem('menuItem_' + itemId + '_collapsed');
                                  $(this).remove();
                                  updateSortOrder();
                                  showFeedback(response.data.message || 'Menü-Item entfernt');
                              });
                          } else {
                              $item.removeClass('deleting');
                              showFeedback(response.data.message || 'Fehler beim Löschen', 'error');
                          }
                      },
                      error: function() {
                          $item.removeClass('deleting');
                          showFeedback('Fehler beim Löschen', 'error');
                      }
                  });
              } else {
                  // For new items that haven't been saved yet, just remove from DOM
                  $item.fadeOut(300, function() {
                      $(this).remove();
                      updateSortOrder();
                      showFeedback('Menü-Item entfernt');
                  });
              }

              }
            })




        })
        .on('click', '.duplicate-menu-item', function(e) {
            e.preventDefault();
            
            const menuItem = $(this).closest('.menu-item');
            const itemId = menuItem.data('id');
            const itemContainer = $(this).closest('.menu-item');
            
            if (!itemId) {
                alert(dailyMenuAdmin.messages.saveError);
                return;
            }
            
            $.ajax({
                url: dailyMenuAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'duplicate_menu_item',
                    item_id: itemId,
                    _ajax_nonce: window.dailyMenuAdmin.nonce

                },
                success: function(response) {
                    if (response.success) {
                        // Append the new item to the menu items container
                        itemContainer.after(response.data.html);
                        
                        // Show success message
                        showFeedback(response.data.message, 'success');
                        
                        // Initialize any necessary functionality for the new item
                        //initMenuItemFunctionality();
                    } else {
                        showFeedback(response.data.message, 'error');
                    }
                },
                error: function() {
                    showFeedback(dailyMenuAdmin.messages.saveError, 'error');
                }
            });
        })
        .on('click', '.menu-item-header button.toggle-menu-item', function(e) {
            e.preventDefault();
            const $menuItem = $(this).closest('.menu-item');
            const $content = $menuItem.find('.menu-item-content');
            const isCollapsed = $content.is(':visible');
            updateItemState($menuItem, isCollapsed);
        });

    // Formular-Validierung
    $('.menu-form').on('submit', function(e) {
        let isValid = true;
        let firstError = null;

        // if ($('.menu-item').length === 0) {
        //     showFeedback(window.dailyMenuAdmin.messages.noItems, 'error');
        //     return false;
        // }

        $('.menu-item').each(function() {
            const validation = validateMenuItem($(this));
            if (!validation.isValid) {
                isValid = false;
                $(this).addClass('has-error');
                if (!firstError) {
                    firstError = $(this);
                }
                validation.errors.forEach(error => {
                    showFeedback(error, 'error');
                });
            } else {
                $(this).removeClass('has-error');
            }
        });

        if (!isValid) {
            e.preventDefault();
            if (firstError) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }
            return false;
        }
    });

    // Sortierreihenfolge aktualisieren
    function updateSortOrder() {
        $('.menu-item').each(function(index) {
            $(this).find('.sort-order').val(index + 1);
        });
    
        const itemOrder = {};
        $('.menu-item').each(function(index) {
            const itemId = $(this).data('id');
            if (itemId) {
                itemOrder[itemId] = index + 1;
            }
        });
    
        if (Object.keys(itemOrder).length > 0) {
            $.ajax({
                url: window.dailyMenuAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_menu_order',
                    item_order: itemOrder,
                    _ajax_nonce: window.dailyMenuAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showFeedback('Reihenfolge gespeichert');
                    } else {
                        showFeedback('Fehler beim Speichern der Reihenfolge', 'error');
                    }
                }
            });
        }
    }

    // Menü kopieren Dialog
    $('#copy-menu-dialog-to, #copy-menu-dialog-from').dialog({
        autoOpen: false,
        modal: true,
        width: 400,
        resizable: false,
        closeOnEscape: true,
        buttons: {
            [window.dailyMenuAdmin.messages.copy || "Kopieren"]: function() {
                const dialog = $(this);
                const menuId = $('.copy-menu').data('menu-id');
                const type = $(this).find('input[name="type"]').val();
                let selectedDate;
                if (type == "to") {
                    selectedDate = $('#selectedDateTo').val();
                } else  {
                    selectedDate = $('#selectedDateFrom').val();
                }
                const currentDate = $('#menu_date').val();

                
                if (!selectedDate) {
                    showFeedback(window.dailyMenuAdmin.messages.selectDate, 'error');
                    return;
                }

                $.ajax({
                    url: window.dailyMenuAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'copy_menu',
                        menu_id: menuId,
                        selectedDate: selectedDate,
                        currentDate: currentDate,
                        type: type,
                        _ajax_nonce: window.dailyMenuAdmin.nonce
                    },
                    beforeSend: function() {
                        dialog.find('button').prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            showFeedback(window.dailyMenuAdmin.messages.copySuccess);
                            if (type == "to") {
                                window.location.href = window.location.pathname + '?page=daily-menu-manager&menu_date=' + selectedDate;
                            } else {
                                window.location.href = window.location.pathname + '?page=daily-menu-manager&menu_date=' + currentDate;
                            }
                        } else {
                            showFeedback(response.data.message || window.dailyMenuAdmin.messages.copyError, 'error');
                        }
                    },
                    error: function() {
                        showFeedback(window.dailyMenuAdmin.messages.copyError, 'error');
                    },
                    complete: function() {
                        dialog.find('button').prop('disabled', false);
                        dialog.dialog('close');
                    }
                });
            },
            [window.dailyMenuAdmin.messages.cancel || "Abbrechen"]: function() {
                $(this).dialog('close');
            }
        }
    });

    // Copy Button Click Handler
    $('.copy-menu').on('click', function() {
        if ($(this).data('menu-id') === 0) {
            $('#copy-menu-dialog-from').dialog('open');
        } else {
            $('#copy-menu-dialog-to').dialog('open');
        } 
    });

    // Initialisiere bestehende Items
    $('.menu-item').each(function() {
        const $item = $(this);
        initializeMenuItemEvents($item);
        makeMenuItemAccessible($item);
        
        // Stelle collapsed Status wieder her
        const itemId = $item.find('input[name*="[id]"]').val();
        if (itemId) {
            const isCollapsed = localStorage.getItem('menuItem_' + itemId + '_collapsed') === 'true';
            if (isCollapsed) {
                updateItemState($item, true);
            }
        }
    });

    // Select2 Initialisierung (falls vorhanden)
    if ($.fn.select2) {
        $('.select2-enable').select2();
    }

    // Error-Klasse bei Fokus entfernen
    $(document).on('focus', 'input.error', function() {
        $(this).removeClass('error');
    });

    // Initialize Tooltips
    function initializeTooltips($context = $(document)) {
        $context.find('.help-tip').each(function() {
            const $tip = $(this);
            const tipText = $tip.data('tip');
            if (tipText) {
                $tip.attr({
                    'title': tipText,
                    'aria-label': tipText
                });
            }
        });
    }
    
    initializeTooltips();

    // Tastaturnavigation für Menu Items
    function handleMenuItemKeyboardNav(e, $item) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'ArrowUp':
                    e.preventDefault();
                    if ($item.prev('.menu-item').length) {
                        $item.insertBefore($item.prev('.menu-item'));
                        updateSortOrder();
                        showFeedback('Item nach oben verschoben');
                    }
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    if ($item.next('.menu-item').length) {
                        $item.insertAfter($item.next('.menu-item'));
                        updateSortOrder();
                        showFeedback('Item nach unten verschoben');
                    }
                    break;
            }
        }
    }

    $('.menu-items').on('keydown', '.menu-item', function(e) {
        handleMenuItemKeyboardNav(e, $(this));
    });


    
    // Header Click Handler
    // $(document).on('click', '.menu-item-header button.toggle-menu-item', function(e) {
    //     e.stopPropagation();
    // });

    // Hover-Effekt für Header
    $('.menu-item-header').hover(
        function() { $(this).addClass('header-hover'); },
        function() { $(this).removeClass('header-hover'); }
    );

    $('.menu-item').each(function() {
        restoreItemState($(this));
    });

    function restoreItemState($item) {
        const itemId = getItemId($item);
        if (!itemId) return;
    
        const isCollapsed = localStorage.getItem(getStorageKey(itemId)) === 'true';
        if (isCollapsed) {
            const $toggle = $item.find('.toggle-menu-item');
            const $content = $item.find('.menu-item-content');
            
            $toggle
                .removeClass('dashicons-arrow-down')
                .addClass('dashicons-arrow-right')
                .attr('aria-expanded', 'false');
            
            $content.hide();
        }
    }

    function getItemId($item) {
        // Versuche zuerst die ID aus dem data-id Attribut zu holen
        let itemId = $item.data('id');
        
        // Falls nicht vorhanden, suche nach verstecktem ID Input
        if (!itemId) {
            const $idInput = $item.find('input[name*="[id]"]');
            itemId = $idInput.length ? $idInput.val() : null;
        }
        
        // Falls immer noch keine ID, suche nach new-X Pattern in Namen
        if (!itemId) {
            const nameMatch = $item.find('input[name*="menu_items"]').first().attr('name')?.match(/\[new-(\d+)\]/);
            itemId = nameMatch ? 'new-' + nameMatch[1] : null;
        }
        
        return itemId;
    }
    
    function getStorageKey(itemId) {
        return 'menuItem_' + itemId + '_collapsed';
    }

    const mainPickr = flatpickr(".flatpickr-wrapper", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: window.dailyMenuAdmin ? window.dailyMenuAdmin.dateFormat : "d.m.Y",
        weekNumbers: true,
        wrap: true,

        onDayCreate: function(dObj, dStr, fp, dayElem) {
            try {
                const dateStr = flatpickr.formatDate(dayElem.dateObj, "Y-m-d");
                if (window.dailyMenuAdmin && window.dailyMenuAdmin.menus && 
                    window.dailyMenuAdmin.menus.includes(dateStr)) {
                    dayElem.classList.add("has-event");
                }
            } catch (innerError) {
                console.log("Fehler beim Markieren der Tage:", innerError);
            }
        }
    });

    const selectedDateTo = flatpickr(".selectedDateTo", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: window.dailyMenuAdmin ? window.dailyMenuAdmin.dateFormat : "d.m.Y",
        weekNumbers: true,
        wrap: true,
        disable: window.dailyMenuAdmin.menus ?? [],
    });
    const selectedDateFrom = flatpickr(".selectedDateFrom", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: window.dailyMenuAdmin ? window.dailyMenuAdmin.dateFormat : "d.m.Y",
        weekNumbers: true,
        wrap: true,
        enable: window.dailyMenuAdmin.menus ?? [],
    });

    const time_start = flatpickr('.order-time-field-start', {
      minuteIncrement: 5,
      wrap: false,
    });

    const time_end = flatpickr('.order-time-field-end', {
      minuteIncrement: 5,
      wrap: false,
    });

    


    $(document).on('input keydown', '.menu-item input[name*="[title]"]', function() {
        const $field = $(this).closest('.menu-item').first();
        const type = $field.data('type');
        const menuType = window.dailyMenuAdmin.menuTypes[type];
        //if (!menuType) return;
        const text = $field.find('input[name*="[title]"]').val();

        $field.find('.menu-item-type-label').text(menuType.label + ':');
        $field.find('.menu-item-title-preview').text(text);
    });


        



    $("#settings-tabs").tabs();




});
