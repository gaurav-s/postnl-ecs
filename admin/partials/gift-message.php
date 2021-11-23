<?php

/*
*
*
* Extra field for card types
*/
/**
 *
 */
function woocommerce_postnl_fulfillment_add_card_text_field() {
    global $wp_query;
    global $product;
    global $post;

    $EcsOrderSettings = ecsOrderSettings::init();
    $settingID = $EcsOrderSettings->getSettingId();
    if(!empty($settingID)) {
        $statesmeta = $EcsOrderSettings->loadOrderSettings($settingID);

        if(!$statesmeta || empty($statesmeta))
            return;

        foreach ($statesmeta as $k) {

            if ($k->keytext == "giftcard_attribute") {
                $giftMessageAttribute = trim($k->value);
                $giftMessageAttribute = strtolower($giftMessageAttribute);
                $giftMessageAttribute = preg_replace('/\s+/', '-', $giftMessageAttribute);
            }
            if ($k->keytext == "giftcard_attribute_value") {
                $giftMessageAttributeValue = trim($k->value);
            }
        }

        if(!isset($giftMessageAttribute) || !$giftMessageAttribute || empty($giftMessageAttribute))
            return;


        if(!isset($giftMessageAttributeValue) || !$giftMessageAttributeValue || empty($giftMessageAttributeValue))
            $giftMessageAttributeValue = 'Yes';


        $customText = $product->get_attribute($giftMessageAttribute);
        $message = '';


        if(empty($customText))
            return;


        if(isset($_REQUEST['card-text-message'])) {
            if(!isset($_REQUEST['add-to-cart']))
                $message = $_REQUEST['card-text-message'];

        }



        echo '<table class="variations postnlecs-card" style="display:none;" cellspacing="0">
                    <tbody>
                        <tr>
                        <td class="label"><label for="postnl-fulfilment-card"> '. __("Message for the card",'woocommercepostnlfulfillment').'</label></td>
                        <td class="value">
                             <input id="postnl-fulfilment-card" type="text" name="card-text-message" style="width:100%;" value="'. $message .'" />  
                                                 
                        </td>
                    </tr>                               
                    </tbody>
                </table>';

        echo '<script type="text/javascript"> 
                    var cardattributevalue = "'.$giftMessageAttributeValue.'";
                    var cardattribute = "'.$giftMessageAttribute.'";
                    jQuery(function() {
                        jQuery( ".variations_form" ).on( "show_variation", function (event,variations) {
                            
                            postnlcheckcardattribute();
                        });
                    });
                    
                    function postnlcheckcardattribute(){
                        
                        var cardatt = jQuery(".variations #"+cardattribute).val();
                        if(cardatt == cardattributevalue) {
                            jQuery(".postnlecs-card").show();
                            jQuery("#postnl-fulfilment-card").attr("required",true);
                        }                            
                        else {
                            jQuery(".postnlecs-card").hide();
                            jQuery("#postnl-fulfilment-card").removeAttr("required");
                        }
                            
                    }
                    

                </script>';

    }











}
add_action( 'woocommerce_before_add_to_cart_button', 'woocommerce_postnl_fulfillment_add_card_text_field' );


/*
* Validation for custom field
*/

function woocommerce_postnl_fulfillment_card_message_validation() {
    /*if ( empty( $_REQUEST['card-text-message'] ) ) {
        wc_add_notice( __( 'Please enter message for card&hellip;', 'woocommerce' ), 'error' );
        return false;
    }*/
    if ( !empty( $_REQUEST['card-text-message'] ) ) {
        if(strlen($_REQUEST['card-text-message']) > 255) {
            wc_add_notice( __( 'Card message should be less than 255 characters', 'woocommercepostnlfulfillment' ), 'error' );
            return false;
        }
    }
    return true;
}
add_action( 'woocommerce_add_to_cart_validation', 'woocommerce_postnl_fulfillment_card_message_validation', 10, 3 );


/*
* add card message in cart field
*/

function woocommerce_postnl_fulfillment_save_card_message_field( $cart_item_data, $product_id ) {
    if( isset( $_REQUEST['card-text-message'] ) && !empty(trim($_REQUEST['card-text-message']))) {
        $cart_item_data[ 'card_message_text' ] = sanitize_text_field($_REQUEST['card-text-message']);
        /* below statement make sure every add to cart action as unique line item */
        $cart_item_data['unique_key'] = md5( microtime().rand() );
    }
    return $cart_item_data;
}
add_action( 'woocommerce_add_cart_item_data', 'woocommerce_postnl_fulfillment_save_card_message_field', 10, 2 );

/*
* add card message on checkouot
*/

function woocommerce_postnl_fulfillment_render_meta_on_cart_and_checkout( $cart_data, $cart_item = null ) {
    $custom_items = array();
    /* Woo 2.4.2 updates */
    if( !empty( $cart_data ) ) {
        $custom_items = $cart_data;
    }
    if( isset( $cart_item['card_message_text'] ) && !empty(trim($cart_item['card_message_text']))) {

        $custom_items[] = array( "name" => __('Message for the card','woocommercepostnlfulfillment'), "value" => $cart_item['card_message_text'] );
    }
    return $custom_items;
}
add_filter( 'woocommerce_get_item_data', 'woocommerce_postnl_fulfillment_render_meta_on_cart_and_checkout', 10, 2 );



/*
* add card message in order meta
*/
function woocommerce_postnlfulfillment_card_message_order_meta_handler( $item, $cart_item_key, $values, $order ) {

    //error_log(print_r($order,true));
    if( isset( $values['card_message_text'] ) ) {
        $item->update_meta_data( 'card_message_text', sanitize_text_field($values['card_message_text']) );
    }

}
add_action( 'woocommerce_checkout_create_order_line_item', 'woocommerce_postnlfulfillment_card_message_order_meta_handler', 1, 4 );