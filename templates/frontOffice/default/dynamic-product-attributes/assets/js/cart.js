// Inject dynamic product values in the cart
$(function() {
    $('.dynamic-attributes-values').each(function() {
        $('.product .dl-horizontal', '#cart_item_id_' + $(this).data('cart-item-id')).append($(this).html());
    });
});
