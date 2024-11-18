jQuery(document).ready(function($) {
    $('.add-menu-item').on('click', function() {
        var type = $(this).data('type');
        var template = $('#menu-item-template-' + type).html();
        var nextId = $('.menu-items').children().length + 1;
        template = template.replace(/\{id\}/g, nextId);
        $('.menu-items').append(template);
    });

    $('.menu-items').sortable({
        handle: '.move-handle',
        update: function() {
            updateSortOrder();
        }
    });

    $(document).on('click', '.remove-menu-item', function() {
        $(this).closest('.menu-item').remove();
        updateSortOrder();
    });

    function updateSortOrder() {
        $('.menu-item').each(function(index) {
            $(this).find('.sort-order').val(index + 1);
        });
    }
});