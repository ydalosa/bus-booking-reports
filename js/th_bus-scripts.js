jQuery(document).ready(function () {
    return;
    jQuery(document).on('click', '.single_add_to_cart_button', function (e) {
        e.preventDefault();
        
        var post_id = jQuery(this).data('product_id');//store product id in post id variable
        var qty = jQuery(this).val('qty');//store quantity in qty variable

        console.log(jQuery(this));
        return;

        jQuery.ajax({
            url: addtocart.ajax_url, //ajax object of localization
            type: 'post', //post method to access data
            data:
            {
                action: 'prefix_ajax_add_foobar', //action on prefix_ajax_add_foobar function
                post_id: post_id,
                quantity: qty
            },

            success: function (response) {

                jQuery('.site-header .quantity').html(response.qty);//get quantity
                jQuery('.site-header .total').html(response.total);//get total

                //loaderContainer.remove();
                alert("Product Added successfully..");
            }

        });

        return false;
    });
});