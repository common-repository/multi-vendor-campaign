
jQuery(document).ready(function($) {

    $( ".products-input-search" ).on('keypress', function() {
        var $this = $(this),
            $product_search_result = $this.siblings('.product-search-result'),
            $products_input_container = $this.parents('.products-input-container'),
            $product_selected_items = $this.parents('.campaign-item').find('.selected-items');


        if(this.value.length >= 2)
		{
            $products_input_container.addClass('searching')

            var data = {
                'action': 'search_products_by_title',
                'security': rad_mvc_object.security,
                'title': this.value ,
                'campaign_id': $this.data('campaignid')
            };

            $.ajax({
                data: data,
                type: 'POST',
                url: rad_mvc_object.ajaxurl,
                dataType: 'json',
                success: function (response) {
                    var item = "",
                        image = "";

                    for(var i in response){
                        if($product_selected_items.find('li[data-productid="' + i +'"]').length <= 0)
                        {
                            item += response[i]['item'];
                        }
                    }
                    $products_input_container.removeClass('searching');
                    $product_search_result.empty().append($(item));
                }
            });

		}
    });

    $(document).on('click','.product-search-result li.newitem', function() {

        var $this = $(this);
        $this.parents('.campaign-item').find('.selected-items').append($this);
        $this.parents('.campaign-item').find('.button_subscribe_campaign').removeClass('disable');


    });

    $(document).on('click','.selected-items li .remove', function() {

        var $this = $(this);
        if($this.parents('li').hasClass('newitem'))
        {
            $this.parents('li').remove();
        }
        else
        {
            $this.siblings('.status').removeClass('pending').addClass('isloading');
            var data = {
                'action': 'unsubscribe_product',
                'security': rad_mvc_object.security,
                'product_id': $this.parents('li').data('productid'),
                'campaign_id': $this.parents('.campaign-item').data('campaignid'),
            };
            $.ajax({
                data: data,
                type: 'POST',
                url: rad_mvc_object.ajaxurl,
                dataType: 'json',
                success: function (response) {
                    if(response == true)
                    {
                        $this.parents('li').remove();
                    }
                    else
                    {
                        $this.siblings('.status').removeClass('isloading').addClass('pending');
                    }
                }
            });
        }
        

    });



    $( ".button_subscribe_campaign" ).on('click', function() {

        var $this = $(this),
            $product_selected_items = $this.parents('.campaign-item').find('.selected-items li.newitem');

        var product_ids = $product_selected_items.map(function() {
            return $(this).data("productid");
        }).get();

        if($this.hasClass('disable') || product_ids.length <= 0)
            return;


        $this.addClass('showloading');

        var data = {
            'action': 'subscribe_products_to_campaign',
            'security': rad_mvc_object.security,
            'campaign_id': $this.data('campaignid'),
            'product_ids': product_ids.join(",")
        };

        $.ajax({
            data: data,
            type: 'POST',
            url: rad_mvc_object.ajaxurl,
            dataType: 'json',
            success:function(response) {
                $this.siblings('loading').removeClass('show');
                
                for(var i in response){
                    if($product_selected_items.filter('li[data-productid="' + i +'"]').length > 0 && response[i]) {
                        $product_selected_items.filter('li[data-productid="' + i + '"]').removeClass('newitem');
                    }
                }
                $this.addClass('disable')
            },
            complete: function() {
                $this.removeClass('showloading');
            }
        });

    });

    $(document).on('keypress', '.campaign-products-wrapper .products-input-search', function() {
        productsSearchVisibility( 'show' );
    });

    $(document).on('focusout', '.campaign-products-wrapper .products-input-search', function() {
        productsSearchVisibility( 'hide' )
    });

    $(document).on('click', '.campaign-products-wrapper .product-search-result li', function() {

        const product_id = $(this).data('productid');
        const product_title = $(this).text();

        $('.campaign-products-wrapper .products-input-search').val(product_title);
        $('.campaign-products-wrapper .subscribe-product-btn').data('productid', product_id);

        productsSearchVisibility( 'hide' );
    });

    $(document).on('click', '.campaign-products-wrapper .subscribe-product-btn', function(e) {

        e.preventDefault();

        const campaign_id = $(this).data('campaignid');
        const product_id = $(this).data('productid');

        if ( !product_id || !campaign_id ) return;

        var data = {
            'action': 'subscribe_products_to_campaign',
            'security': rad_mvc_object.security,
            'campaign_id': campaign_id,
            'product_ids': product_id,
            'status': 1 // No need for admin approval
        };

        $.ajax({
            data: data,
            type: 'POST',
            url: rad_mvc_object.ajaxurl,
            dataType: 'json',
            complete: function() {
                window.location.reload();
            }
        });
    });

    function productsSearchVisibility( state ) {
        const searchResultContainer = $('.campaign-products-wrapper .product-search-result');
        if ( state === 'show' ) {
            searchResultContainer.fadeIn();
        } else if ( state === 'hide' ) {
            searchResultContainer.fadeOut();
        }
    }

});