jQuery(document).ready(function($) {
    // Prevent form submission on Enter in input fields
    $('#menu-order-form input[type="text"]').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            return false;
        }
        updateAvailability($(this));
    });

    function updateTotal() {
        let total = 0;
        $('.quantity-input').each(function() {
            const quantity = parseInt($(this).val()) || 0;
            const price = parseFloat($(this).data('price')) || 0;
            total += quantity * price;
        });
        $('#total-amount').text(total.toFixed(2) + ' €');
    }

    function updateAvailability(input) {
        const item = input.closest('.menu-item');
        const availability = parseInt(item.data('availability')) || 0;
        const quantity = parseInt(input.val()) || 0;
        const remaining = availability - quantity;
        item.find('.menu-item-availability').text('Verfügbar: ' + remaining);
    }

    // Handle quantity buttons
    $('.quantity-btn').on('click', function() {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val()) || 0;
        
        if ($(this).hasClass('minus')) {
            value = Math.max(0, value - 1);
        } else if ($(this).hasClass('plus')) {
            value += 1;
        }
        
        input.val(value).trigger('change');
        updateQuantityVisibility(input);
        updateAvailability(input);
    });

    function updateQuantityVisibility(input) {
        const quantity = parseInt(input.val()) || 0;
        const notesField = input.closest('.menu-item').find('.item-notes');
        
        if (quantity > 0) {
            notesField.slideDown();
        } else {
            notesField.slideUp();
        }
        
        updateTotal();
    }

    // Handle direct input changes
    $('.quantity-input').on('change input', function() {
        let value = parseInt($(this).val()) || 0;
        if (value < 0) value = 0;
        $(this).val(value);
        
        updateQuantityVisibility($(this));
    });

    // Initial state
    $('.item-notes').hide();
    $('.menu-item').each(function() {
        const item = $(this);
        const availability = parseInt(item.data('availability')) || 0;
        item.find('.menu-item-availability').text('Verfügbar: ' + availability);
    });
    updateTotal();

    // Form submission handling
    $('#menu-order-form').on('submit', function(e) {
        e.preventDefault();
        
        // Check if any items are ordered
        let hasItems = false;
        $('.quantity-input').each(function() {
            if (parseInt($(this).val()) > 0) {
                hasItems = true;
                return false;
            }
        });

        if (!hasItems) {
            Swal.fire({
                title: dailyMenuAjax.messages.emptyOrder,
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Wird gesendet...',
            didOpen: () => {
                Swal.showLoading();
                $('.submit-order').prop('disabled', true);
            }
        });

        $.ajax({
            url: dailyMenuAjax.ajaxurl,
            type: 'POST',
            data: $(this).serialize() + '&action=submit_order',
            success: function(response) {
                if (response.success) {
                    // Build order details
                    let detailsHtml = '<h4>Bestellte Gerichte:</h4><ul style="text-align: left; list-style-type: none; padding-left: 0;">';
                    response.data.items.forEach(function(item) {
                        detailsHtml += `<li style="margin-bottom: 10px;">
                            ${item.quantity}x ${item.title} (${(item.price * item.quantity).toFixed(2)} €)`;
                        if (item.notes) {
                            detailsHtml += `<br><small>Anmerkung: ${item.notes}</small>`;
                        }
                        detailsHtml += '</li>';
                    });
                    detailsHtml += '</ul>';
                    detailsHtml += `<p><strong>Gesamtbetrag: ${response.data.total_amount.toFixed(2)}&nbsp;€</strong></p>`;
                    
                    Swal.fire({
                        title: 'Bestellung erfolgreich aufgegeben!',
                        html: `
                            <p>Ihre Bestellnummer: <strong>${response.data.order_number}</strong></p>
                            <p>Bitte nennen Sie diese Nummer bei der Abholung.</p>
                            ${detailsHtml}
                        `,
                        icon: 'success',
                        confirmButtonText: 'Schließen',
                    }).then((result) => {
                        // Allways reload
                        // if (result.isConfirmed) {
                            location.reload();
                        // }
                    });

                    // Update availability counts after successful order
                    response.data.items.forEach(function(item) {
                        const menuItem = $('.menu-item[data-item-id="' + item.id + '"]');
                        const currentAvailability = parseInt(menuItem.data('availability')) || 0;
                        const newAvailability = currentAvailability - item.quantity;
                        menuItem.data('availability', newAvailability);
                        menuItem.find('.menu-item-availability').text('Verfügbar: ' + newAvailability);
                    });
                } else {
                    Swal.fire({
                        title: dailyMenuAjax.messages.orderError,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: dailyMenuAjax.messages.orderError,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $('.submit-order').prop('disabled', false).text('Bestellung aufgeben');
            }
        });
    });
});