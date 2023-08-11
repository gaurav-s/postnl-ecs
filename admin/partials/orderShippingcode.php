<?php
/*
Plugin Name: PostNL-ECS
Plugin URI: http://www.postnl.nl/
Description: PostNL ECS Fulfilment Plugin
Author: PostNL
Author URI: http://www.postnl.nl/
*/

/**
 * Order export shipping ID settings
 */

function getPostNLEcsShippingCode($shippingCountry, $order) {


    $shippingOptions = $order->get_meta('_postnl_order_metadata');
    $found_shipping_classes = find_order_shipping_classes($order);
    $postNlDeliveryType = '';
    if (class_exists('PostNLWooCommerce\Order\Single')) {
         $postNlDeliveryType = new PostNLWooCommerce\Order\Single;
         $shippingOptions['deliveryType'] = $postNlDeliveryType->get_delivery_type( $order );
    }


    if(!empty($found_shipping_classes)){
        $shippingOptions['packageType'] = $found_shipping_classes;
    }


    if(isset($shippingOptions['frontend']['dropoff_points_type']) && $shippingOptions['frontend']['dropoff_points_type'] == 'Pickup') {
        $shippingOptions['isPickup'] = true;
    }

    /*$shippingOptionsJson = $order->get_meta('_postnl_delivery_options');

    if(is_array($shippingOptionsJson))
        return false;

    $shippingOptions = json_decode($shippingOptionsJson,true);
    */
    $saoArray = [
        'Morning10',
        'Morning',
        'Morning12',
        'Evening',
        'Standard'
    ];

    if($shippingOptions) {

        $shipmentOptions = '';
        if(isset($shippingOptions['shipmentOptions']))
            $shipmentOptions = $shippingOptions['shipmentOptions'];



        $homeAddressOnly = '';
        $sinatureOption = '';


        if(isset($shipmentOptions['only_recipient']) && ($shipmentOptions['only_recipient'] != 0))
            $homeAddressOnly = '_SAO';

        if(isset($shipmentOptions['signature']) && ($shipmentOptions['signature'] != 0))
            $sinatureOption = '_SIG';




        if(isset($shippingOptions['packageType'])) {

            if($shippingOptions['packageType'] == 'mailbox') {
                if( strtolower($shippingCountry) === 'nl')
                    return '02928';
                else
                    return get_outside_nl_shipping($shippingCountry);


            }

            if($shippingOptions['packageType'] == 'letter' || $shippingOptions['packageType'] == 'digital_stamp')
                return 'NA';


            if($shippingOptions['packageType'] == 'package') {
                $postNlCode = getpostnlMappingCodes($shippingOptions, $shippingCountry);
                if(in_array($postNlCode,$saoArray))
                    $postNlCode = $postNlCode.$sinatureOption.$homeAddressOnly;
                return $postNlCode;

            }



        }
        else {
            $postNlCode = getpostnlMappingCodes($shippingOptions, $shippingCountry);
            if(in_array($postNlCode,$saoArray))
                $postNlCode = $postNlCode.$sinatureOption.$homeAddressOnly;
            return $postNlCode;
        }

    }

    return false;

}

function getpostnlMappingCodes($options, $countryCode) {
	$match =  esc_html__( 'Pickup at PostNL Point', 'postnl-for-woocommerce' );
    if(isset($options['deliveryType'])) {

        if(isset($options['isPickup']) && $options['deliveryType'] == $match && $options['isPickup']){
            if(strtolower($countryCode) === 'nl')
                return  '03533';
            if(strtolower($countryCode) === 'be')
                return '04936';
            else
                return 'NA';
        }

        if($options['deliveryType'] == esc_html__( 'Morning Delivery', 'postnl-for-woocommerce' ) )
            return 'Morning';

        if($options['deliveryType'] == esc_html__( 'Evening Delivery' , 'postnl-for-woocommerce' ))
            return 'Evening';

        if($options['deliveryType'] == esc_html__( 'Standard Shipment', 'postnl-for-woocommerce' ))
            return 'Standard';
    }
    else {
        if(strtolower($countryCode) !== 'nl')
            return get_outside_nl_shipping($countryCode);
    }

    return 'PNLP';
}

function ecs_eu_country_check($country_code) {
    $euro_countries = array(
                            'AT',
                            'BG',
                            'CY',
                            'CZ',
                            'DE',
                            'DK',
                            'EE',
                            'ES',
                            'FI',
                            'FR',
                            'GR',
                            'HR',
                            'HU',
                            'IE',
                            'IT',
                            'LT',
                            'LU',
                            'LV',
                            'MC',
                            'MT',
                            'PL',
                            'PT',
                            'RO',
                            'SE',
                            'SI',
                            'SK',
                            'VA'
                    );

    return in_array( $country_code, $euro_countries);
}

function get_outside_nl_shipping($countryCode) {
    if(strtoupper($countryCode) == 'BE')
        return '04946';
    if(ecs_eu_country_check(strtoupper($countryCode)))
        return '04952';
    else
        return '04945';

}

function postnl_fulfilment_shipping_age_check ($shippingCountry, $order) {
    $shippingOptionsJson = $order->get_meta('_postnl_delivery_options');
    if(is_array($shippingOptionsJson))
        return '';

    $shippingOptions = json_decode($shippingOptionsJson,true);
    $ageCheck = '';
    $ageCheckCode = 'LC1';

    if(isset($shippingOptions['shipmentOptions']) && $shippingOptions['shipmentOptions']) {

        $shipmentOptions = $shippingOptions['shipmentOptions'];
        if(isset($shipmentOptions['age_check']) && ($shipmentOptions['age_check'] != 0))
            $ageCheck =  $ageCheckCode;

    }

    return  $ageCheck;
}

function find_order_shipping_classes($order) {
    $found_shipping_classes = array();
    $order_items = $order->get_items();
    foreach ( $order_items as $item_id => $item ) {
        $product = wc_get_product($item['product_id']);
        if ($product && $product->needs_shipping()) {
            return $found_class = $product->get_shipping_class();
        }
    }

    return $found_shipping_classes;
}