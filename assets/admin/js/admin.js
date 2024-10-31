jQuery(document).ready( function($) {
    'use strict';

    $( ".rad-mvc-product-action" ).on('click', function() {
        var $this = $(this),
            $status_field = $this.parents('.column-cbs').siblings('.column-status').find('.rad-mvc-product-status'),
            data = {
                'action': 'update_campaign_product_status',
                'security': rad_mvc_object.security,
                'campaign_product_id': $this.parent().data('campaignid'),
                'status' : $this.data('status')
            };

        $status_field.addClass('isloading');

        $.post(rad_mvc_object.ajaxurl, data, function(response) { 
            if(response !== false)
            {
                if(response == 1)
                {
                    $status_field.removeClass('pending rejected isloading').addClass('approved');
                }
                else if(response == 2)
                {
                    $status_field.removeClass('pending approved isloading').addClass('rejected');
                }
                
            }

        }).always(function() {
            $status_field.removeClass('isloading')
        });
    });

    var input_number_label = function($field)
    {
        var value = $field.val();
        if(value > 0)
        {
            $field.siblings('.input_number_label').removeClass('negative').addClass('positive');
            $field.siblings('.input_number_label').find('.value').html(Math.abs(value));
        }
        else if(value < 0)
        {
            $field.siblings('.input_number_label').removeClass('positive').addClass('negative');
            $field.siblings('.input_number_label').find('.value').html(Math.abs(value));
        }
        else
        {
            $field.siblings('.input_number_label').removeClass('positive negative');
        }
    }

    $('input.labeled_input_number').on('keyup mouseup', function(){
        var $this = $(this);
        input_number_label($this);
    }).each(function(){
        input_number_label($(this));
    });

    
    function rule_type_handler() {
        var $rule_type_field = $("#rule_type"),
            $rule_operator_tr = $("#rule_operator_field"),
            $rule_value_tr = $("#rule_value_field");

        if($rule_type_field.find('option').filter(":selected").val() == 'no_rule')
        {
            $rule_value_tr.hide();
            $rule_operator_tr.hide();
        }
        else
        {
            $rule_value_tr.show();
            $rule_operator_tr.show();
        }
    }

    $("#rule_type").on('change',function(){
        rule_type_handler();
    });

    rule_type_handler();

    $('#upload_thumb_button').on('click', function() {
        tb_show('Upload a thumbnail image', 'media-upload.php?referer=rad_mvc_campaigns&type=image&TB_iframe=true&post_id=0', false);
        return false;
    });

    window.send_to_editor = function(html) {
        var image_url = "";

        if ($(html).attr('src')) {
            image_url = $(html).attr('src');
        } else {
            image_url = $(html).find('img').attr('src');
        }


        $('#thumbnail').val(image_url);
        tb_remove();
    }

});