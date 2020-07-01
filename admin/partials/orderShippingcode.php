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

        $shippingOptions = $order->get_meta('_postnl_delivery_options');
        $saoArray = [
            'Morning10',
            'Morning',
            'Morning12',
            'Evening',
            'Standard'
        ];
        if($shippingOptions) {
            
            $homeAddressOnly = '';
            $sinatureOption = '';
            if(isset($shippingOptions['only_recipient']) && ($shippingOptions['only_recipient'] != 0))
                $homeAddressOnly = '_SAO';
            
            if(isset($shippingOptions['signature']) && ($shippingOptions['signature'] != 0))
                $sinatureOption = '_SIG';
            
            if (class_exists('WooCommerce_PostNL')) 
                $wooCommPostNlpackages =  WooCommerce_PostNL()->export->get_package_type_for_order($order);
            else
                $wooCommPostNlpackages = false;
            
            if($wooCommPostNlpackages) {
                if($wooCommPostNlpackages === 3)
                    return 'NA';
                if($wooCommPostNlpackages === 2) {
                    if(strtolower($shippingCountry) === 'nl')
                        return '02928';
                    else 
                        return get_outside_nl_shipping($shippingCountry);
                }

            }
                
            $shipmentTypeCode = 0;
            if(isset($shippingOptions['time'])) {

                foreach($shippingOptions['time'] as $optionDetails) {
                    if(isset($optionDetails['type'])) {
                        
                        $start_time = isset($optionDetails['start']) ? $optionDetails['start'] : false;
                        $end_time = isset($optionDetails['end']) ? $optionDetails['end'] : false;
                        $shipmentTypeCode = $optionDetails['type'];
                        if($optionDetails['type'] == 5)
                            break;    
                        
                    }
                    
                }
                if($shipmentTypeCode) {
                    $postNlCode = getpostnlMappingCodes($optionDetails['type'],$start_time,$end_time, $shippingCountry);
                    if(in_array($postNlCode,$saoArray)) 
                        $postNlCode = $postNlCode.$sinatureOption.$homeAddressOnly;
                    return $postNlCode;
                }
    
            }
        }
        
        return false;
        
        
       

    }

    function getpostnlMappingCodes($optionType, $start_time, $end_time, $countryCode) {
        $postnlshippingCode = 'PNLP'; 
        switch ($optionType) {
            case 1: 
                //Time check for future use
                /*if($end_time) {
                    //Time check for future use
                    $endTime = strtotime($end_time);
                    $timevar = (int) date('H',$endTime) ;
                    if($timevar <= 10)
                        $postnlshippingCode = 'Morning10';
                    else
                        $postnlshippingCode = 'Morning12';
                } else*/
                    $postnlshippingCode = 'Morning';
                break;
            case 2:
                if(strtolower($countryCode) === 'nl')
                    $postnlshippingCode = 'Standard'; 
                else 
                    $postnlshippingCode = get_outside_nl_shipping($countryCode);
                break;
            case 3:
                if(strtolower($countryCode) === 'nl')
                    $postnlshippingCode = 'Evening';
                else 
                    $postnlshippingCode = get_outside_nl_shipping($countryCode);
                break;
            case 4:
                if(strtolower($countryCode) === 'nl') {
                    //For Future
                    /*if($start_time) {
                        $startTime = strtotime($start_time);
                        $timevar = (int) date('H',$startTime) ;
                        if($timevar <= 12)
                            $postnlshippingCode = 'PGE';
                        else
                            $postnlshippingCode = '03533';
                    }
                    else*/ 
                        $postnlshippingCode = '03533';
                   
                   
                }
                else 
                    //$postnlshippingCode = get_outside_nl_shipping($countryCode);
                    $postnlshippingCode = 'NA';
                break;
            case 5:
                if(strtolower($countryCode) === 'nl') {
                    $postnlshippingCode = 'PGE';
                }
                else 
                    //$postnlshippingCode = get_outside_nl_shipping($countryCode);
                    $postnlshippingCode = 'NA';
                break;
            default:
                $postnlshippingCode = 'PNLP';
                break;


         }

         return $postnlshippingCode;


    }

     function ecs_eu_country_check($country_code) {
        $euro_countries = array('AT','BE','BG','CZ','DK','EE','FI','FR','DE','GB','GR','HU','IE','IT','LV','LT','LU','PL','PT','RO','SK','SI','ES','SE','MC','AL','AD','BA','IC','FO','GI','GL','GG','JE','HR','LI','MK','MD','ME','UA','SM','RS','VA','BY','CY','MT');

        return in_array( $country_code, $euro_countries);
    }

    function get_outside_nl_shipping($countryCode) {
        if(ecs_eu_country_check(strtoupper($countryCode)))
            return '04944'; 
        else 
            return '04945';

    }