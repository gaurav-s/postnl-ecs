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

    $deliveryType = '';
    // Check if the class exists
    if (class_exists('PostNLWooCommerce\Order\Single')) {
        // Create an instance of the class
        $postNlDeliveryType = new PostNLWooCommerce\Order\Single;

        // Check if the method exists in the class
        if (method_exists($postNlDeliveryType, 'get_delivery_type')) {
            // Call the method with the appropriate arguments (assuming $order is defined)
            $deliveryType = $postNlDeliveryType->get_delivery_type($order);


        }
    }
    $shippingOptions = $order->get_meta('_postnl_order_metadata');

//	$found_shipping_classes = find_order_shipping_classes($order);
//    if(!empty($found_shipping_classes)){
//        $shippingOptions['packageType'] = $found_shipping_classes;
//    }

//above code replaced/optimized by below code for version 2.2.1
	$found_shipping_classes = find_order_shipping_classes($order);

	// Check if $found_shipping_classes is an array
	if (is_array($found_shipping_classes) && !empty($found_shipping_classes)) {
		$shippingOptions['packageType'] = $found_shipping_classes;
	} elseif (is_string($found_shipping_classes) && !empty($found_shipping_classes)) {
		// Handle case when a string is returned, you can either convert it or log it
		$shippingOptions['packageType'] = [$found_shipping_classes];  // Convert string to array
	} else {
		// Handle empty or invalid return value
		//$shippingOptions['packageType'] = 'default_package';
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

//added code for version 2.2.1
		if($order->get_meta('_postnl_letterbox') === '1' ) {
                if( strtolower($shippingCountry) === 'nl')
                    return '02928';
                else
                    return get_outside_nl_shipping($shippingCountry);
	}


        if(isset($shippingOptions['packageType'])) {

            if($shippingOptions['packageType'] == '_postnl_letterbox') {
                if( strtolower($shippingCountry) === 'nl')
                    return '02928';
                else
                    return get_outside_nl_shipping($shippingCountry);


            }

            if($shippingOptions['packageType'] == 'letter' || $shippingOptions['packageType'] == 'digital_stamp')
                return 'NA';


            if($shippingOptions['packageType'] == 'package') {
                $shippingOptions['deliveryType'] = $deliveryType;
                $postNlCode = getpostnlMappingCodes($shippingOptions, $shippingCountry);
                if(in_array($postNlCode,$saoArray))
                    $postNlCode = $postNlCode.$sinatureOption.$homeAddressOnly;
                return $postNlCode;

            }



        }
        else {
             $shippingOptions['deliveryType'] = $deliveryType;
            $postNlCode = getpostnlMappingCodes($shippingOptions, $shippingCountry);
            if(in_array($postNlCode,$saoArray))
                $postNlCode = $postNlCode.$sinatureOption.$homeAddressOnly;
            return $postNlCode;
        }




    }

    return false;




}

function getpostnlMappingCodes($options, $countryCode) {
    if(isset($options['deliveryType'])) {
// Updated code below for version 2.2.1
//		if(isset($options['isPickup']) && $options['deliveryType'] == 'Pickup at PostNL Point' && $options['isPickup']){
		if(isset($options['isPickup']) && (($options['deliveryType'] == 'Pickup at PostNL Point') or ($options['deliveryType'] == 'Pickup at PostNL Point Belgium')) && $options['isPickup']){
            if(strtolower($countryCode) === 'nl')
                return  '03533';
            if(strtolower($countryCode) === 'be')
                return '04936';
            else
                return 'NA';
        }

        if(strtolower($countryCode) !== 'nl')
            return get_outside_nl_shipping($countryCode);

        if($options['deliveryType'] == 'Morning Delivery')
            return 'Morning';

        if($options['deliveryType'] == 'Evening Delivery')
            return 'Evening';

        if($options['deliveryType'] == 'Standard Shipment')
            return 'Standard';
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