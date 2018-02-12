// Inject dynamic product values in the table usin the cart item index
// as the <tr> ha no ID like in the cart.html template (so bad).
$(function() {
    $('.dynamic-attributes-values').each(function() {
        var index = 1 + $(this).data('cart-item-index');
    
        $('.table-cart tr:nth-child('+index+') td.product .dl-horizontal').append($(this).html());
    });
});
