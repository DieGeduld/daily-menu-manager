jQuery(document).ready(function($) {
    function updateTotal() {
        let total = 0;
        $('.quantity-input').each(function() {
            const quantity = parseInt($(this).val()) || 0;
            const price = parseFloat($(this).data('price')) || 0;
            total += quantity * price;
        });
        $('#total-amount').text(total.toFixed(2) + ' €');
    }

    // Aktualisiere den Gesamtbetrag bei Änderung der Mengen
    $('.quantity-input').on('change', updateTotal);

    // Bestellformular absenden
    $('#menu-order-form').on('submit', function(e) {
        e.preventDefault();
        
        // Prüfe ob mindestens ein Gericht bestellt wurde
        let hasItems = false;
        $('.quantity-input').each(function() {
            if (parseInt($(this).val()) > 0) {
                hasItems = true;
                return false;
            }
        });

        if (!hasItems) {
            alert('Bitte wählen Sie mindestens ein Gericht aus.');
            return;
        }

        $('.submit-order').prop('disabled', true).text('Wird gesendet...');

        $.ajax({
            url: dailyMenuAjax.ajaxurl,
            type: 'POST',
            data: $(this).serialize() + '&action=submit_order',
            success: function(response) {
                if (response.success) {
                    // Bestellbestätigung anzeigen
                    $('#order-number').text(response.data.order_number);
                    
                    // Bestelldetails zusammenstellen
                    let detailsHtml = '<h4>Bestellte Gerichte:</h4><ul>';
                    response.data.items.forEach(function(item) {
                        detailsHtml += `<li>${item.quantity}x ${item.title} (${(item.price * item.quantity).toFixed(2)} €)`;
                        if (item.notes) {
                            detailsHtml += `<br><small>Anmerkung: ${item.notes}</small>`;
                        }
                        detailsHtml += '</li>';
                    });
                    detailsHtml += '</ul>';
                    detailsHtml += `<p><strong>Gesamtbetrag: ${response.data.total_amount.toFixed(2)} €</strong></p>`;
                    
                    $('.confirmation-details').html(detailsHtml);
                    
                    // Formular ausblenden und Bestätigung anzeigen
                    $('#menu-order-form').slideUp();
                    $('#order-confirmation').slideDown();
                    
                    // Zum Anfang der Bestätigung scrollen
                    $('html, body').animate({
                        scrollTop: $('#order-confirmation').offset().top - 100
                    }, 500);
                } else {
                    alert('Es gab einen Fehler bei der Bestellung. Bitte versuchen Sie es erneut.');
                }
            },
            error: function() {
                alert('Es gab einen Fehler bei der Übertragung. Bitte versuchen Sie es erneut.');
            },
            complete: function() {
                $('.submit-order').prop('disabled', false).text('Bestellung aufgeben');
            }
        });
    });

    // Zusätzliche Funktionen für bessere Benutzerfreundlichkeit
    $('.quantity-input').on('input', function() {
        // Negative Werte verhindern
        if ($(this).val() < 0) {
            $(this).val(0);
        }
        
        // Anmerkungsfeld ein-/ausblenden
        const notesField = $(this).closest('.menu-item-order').find('.item-notes');
        if (parseInt($(this).val()) > 0) {
            notesField.slideDown();
        } else {
            notesField.slideUp();
        }
        
        updateTotal();
    });

    // Initial Anmerkungsfelder ausblenden
    $('.item-notes').hide();

    // "Neue Bestellung" Button zur Bestätigung hinzufügen
    $('#order-confirmation').append(
        '<button class="new-order-btn" onclick="location.reload()">Neue Bestellung aufgeben</button>'
    );
});