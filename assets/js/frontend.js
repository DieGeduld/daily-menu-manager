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

    // Handle quantity buttons
    $('.quantity-btn').on('click', function() {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val()) || 0;
        
        if ($(this).hasClass('minus')) {
            value = Math.max(0, value - 1); // Prevent negative values
        } else if ($(this).hasClass('plus')) {
            value += 1;
        }
        
        input.val(value).trigger('change');
        updateQuantityVisibility(input);
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
        // Ensure non-negative integers
        let value = parseInt($(this).val()) || 0;
        if (value < 0) value = 0;
        $(this).val(value);
        
        updateQuantityVisibility($(this));
    });

    // Initial state
    $('.item-notes').hide();
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
            alert(dailyMenuAjax.messages.emptyOrder);
            return;
        }

        $('.submit-order').prop('disabled', true).text('Wird gesendet...');

        $.ajax({
            url: dailyMenuAjax.ajaxurl,
            type: 'POST',
            data: $(this).serialize() + '&action=submit_order',
            success: function(response) {
                if (response.success) {
                    $('#order-number').text(response.data.order_number);
                    
                    // Build order details
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
                    
                    $('#menu-order-form').slideUp();
                    $('#order-confirmation').slideDown();
                    
                    $('html, body').animate({
                        scrollTop: $('#order-confirmation').offset().top - 100
                    }, 500);
                } else {
                    alert(dailyMenuAjax.messages.orderError);
                }
            },
            error: function() {
                alert(dailyMenuAjax.messages.orderError);
            },
            complete: function() {
                $('.submit-order').prop('disabled', false).text('Bestellung aufgeben');
            }
        });
    });

    // Add "New Order" button to confirmation
    $('#order-confirmation').append(
        '<button class="new-order-btn" onclick="location.reload()">' +
        'Neue Bestellung aufgeben</button>'
    );
});