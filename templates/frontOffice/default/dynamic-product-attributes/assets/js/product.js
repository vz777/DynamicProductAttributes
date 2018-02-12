// Inject dynamic product fields
$(function() {
    var $formProductDetails = $('#form-product-details');
    
    if ($formProductDetails.length > 0) {
        var $insert = $('.dynamic-attributes-form-fields');
    
        if ($insert.length) {
            $('fieldset.product-cart', $formProductDetails).before($insert.html());
        }
    }
    
    // Process "Added to cart" bootbox, to add dynamic attributes values to attribute list
    $(document).on("shown.bs.modal", function (event) {
        var $productDesc = $('.bootbox-body table tr:nth-child(2) td:nth-child(2)');
        
        $('.dynamic-attribute').each(function() {
            var $zis = $(this);
            
            if ($zis.val() !== '') {
                $productDesc.append("<p>" + $zis.data('attribute-title') + ' : ' + $zis.val() + "</p>");
            }
        });
        
    });
});
