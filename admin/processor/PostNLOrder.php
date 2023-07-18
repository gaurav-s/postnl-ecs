<?php
class PostNLOrder extends PostNLProcess
{


    public function __construct() {

    }

    public function processOrders(){

        global $wpdb;
        $EcsSftpSettings = $this->getFtpSettings();

        $EcsOrderSettings = ecsOrderSettings::init();
        $Path = '';
        $orderStatus = '';
        $shipment = '';
        $no = '';

        $settingID = $EcsOrderSettings->getSettingId();
        if($settingID) {
            $statesmeta = $EcsOrderSettings->loadOrderSettings($settingID);
        }
        else {
            error_log('Order Settings not found');
            return;
        }

        foreach ($statesmeta as $k) {

            if ($k->keytext == "Path") {
                $Path = $k->value;
            }

            if ($k->keytext == "no") {
                $no = $k->value;
            }

            if($k->keytext == "Cron") {
                $Cron = $k->value;
            }


            if($k->keytext == "Status") {
                $orderStatus = $k->value;
            }

            if($k->keytext == "Shipping") {
                $shipment = $k->value;
            }



        }

        $sftp = $this->checkFtpSettings($Path);

        if(!$sftp)
            return false;



        // find list of states in DB




        $ordersW = '';
        $orderStatusArray = explode(":", $orderStatus);


        $ordersW = get_posts(array(
            'post_type' => 'shop_order',
            'post_status' => $orderStatusArray,
            'posts_per_page' => 100,
            'meta_query' => array(
                array(
                    'key' => 'ecsExport',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));

        if(empty($ordersW) && count($ordersW) < 1)
            return;

        $table_name_ecs = $wpdb->prefix . 'ecs';
        $qrymeta = "SELECT * FROM ".$table_name_ecs." WHERE keytext = 'LastOrderID'";
        $statesmeta = $wpdb->get_results($qrymeta);
        $orderNo = '';

        foreach($statesmeta as $k) {
            $orderNo = $k->type;
            $NextorderNo = $orderNo + 1;
            $OrderMessageKey = $k->id;
            $wpdb->query($wpdb->prepare("UPDATE ".$table_name_ecs." SET type = '".$NextorderNo."' WHERE   id= %d", $k->id));
        }

        $NextorderNo = $orderNo + 1;

        $Orderchunck  = array_chunk($ordersW, $no);
        $FailedOrders = array();
        $shipementsArray = explode(":", $shipment);
        $shipements = array();

        foreach($shipementsArray as $ship) {
            array_push($shipements, $ship);
        }

        foreach($Orderchunck as $order_split) {
            $isEmpty = 0;
            $xml = new DOMDocument();

            $message = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new', 'message');
            $xml->appendChild($message);
            $message->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','type', 'deliveryOrder'));
            $message->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','messageNo', $orderNo));
            $t = time();
            $message->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','date', date("Y-m-d", $t)));
            $message->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','time', date("H:i:s", $t)));

            $orders = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrders');
            $message->appendChild($orders);
            $processedOrders = [];

            foreach ($order_split as $order) {
                $order= new WC_Order($order->ID);
                $order_shipping_method_id = 'l ';

                $shipping_items = $order->get_items('shipping');

                foreach($shipping_items as $el) {
                    $order_shipping_method_id = $el['method_id'];
                }

                $split = explode(":", $order_shipping_method_id);
                $order_shipping_method_id = $split[0];


                if($order_shipping_method_id == 'l ') $order_shipping_method_id = "disabled";

                if(in_array($order_shipping_method_id, $shipements)) {
                    $isvalidate = true;
                    $order_id = $order->get_id();
                    $failed = new Failederrors();
                    $failed->set_orderID($order_id);
                    $order = new WC_Order($order_id);


                    $date = date_create ($order->get_date_created());
                    $node = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrder');

                    if(strlen($order->get_id()) == 0) {
                        $failed->addError(" orderNo length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($order->get_id()) > 10) {
                            $failed->addError(" orderNo length is greater than 10 characters");
                            $isvalidate = false;
                        }
                    }

//Customization for Rextro function getretailername added to get retailername configured and to add in ordernumber as prefix
					$retailerDetails = $this->getRetailerName();
					$nameRetailer =  strtoupper($retailerDetails['retailer']);

//                  $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderNo', $order->get_id()));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderNo', ($nameRetailer) . ($order->get_id())));                        
                        
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','webOrderNo', $order->get_id()));


                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderDate', $order->get_date_created()->format("Y-m-d")));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderTime', $order->get_date_created()->format("H:i:s")));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','customerNo', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','onlyHomeAddress', 'false'));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','vendorNo', ''));


                    //  shipping
                    //SHIPPING TITLE
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToTitle', ''));

                    //SHIPPING  LASTNAME
                    if(strlen($order->get_shipping_last_name()) == 0) {
                        $failed->addError(" shipToLastName length is null");

                        $isvalidate = false;
                    } else {
                        if(strlen($order->get_shipping_last_name()) > 35) {
                            $failed->addError(" shipping_last_name length is greater than 35 characters");

                            $isvalidate = false;
                        }
                    }



                    //SHIPPING LAST NAME
                    if(strlen($order->get_shipping_first_name()) > 30) {
                        $failed->addError(" shipToFirstName length is greater than 30");

                        $isvalidate = false;
                    }

                    $shippingCodePostNL = getPostNLEcsShippingCode($order->get_shipping_country(), $order);

                    $orderShipPostCode = $order->get_shipping_postcode(); //Set from WC
                    $orderShipPostcity = $order->get_shipping_city(); //Set from WC
                    $orderShipPostcountry = $order->get_shipping_country(); //Set from WC
                    $orderShipPoststreet =  trim($order->get_shipping_address_1()); //Set from WC
                    $orderShipPoststreetNum = trim($order->get_shipping_address_2()); //Set from WC
                    $orderShipPostcompany = $order->get_shipping_company(); // Set from WC
                    $orderShipPostDeliveryDate = '';
                    $orderShipPostDeliveryTime = '';

                    //For PGE address
                    $shippingCodeArrayskip = ['04952','04945', '04946','NA'];

                    if($shippingCodePostNL && !in_array($shippingCodePostNL, $shippingCodeArrayskip)) {
                        $shippingOptionsJson = $order->get_meta('_postnl_delivery_options');

                        $shippingOptions = is_array($shippingOptionsJson) ? '' : json_decode($shippingOptionsJson,true) ;

                        if($shippingCodePostNL === 'PGE' || $shippingCodePostNL === '03533' || $shippingCodePostNL === '04936') {

                            if(isset($shippingOptions['pickupLocation'])) {

                                $pickupOptions = $shippingOptions['pickupLocation'];
                                if(

                                    isset($pickupOptions['postal_code'])
                                    && isset($pickupOptions['street'])
                                    && 	isset($pickupOptions['number'])
                                    && isset($pickupOptions['city'])
                                ) {

                                    $orderShipPostCode = $pickupOptions['postal_code']; //Set for PGE
                                    $orderShipPostcity = $pickupOptions['city']; //Set Set for PGE
                                    $orderShipPostcountry = isset($pickupOptions['cc']) ? $pickupOptions['cc'] : $orderShipPostcountry ; //Set for PGE
                                    $orderShipPoststreet =  $pickupOptions['street']; //Set for PGE
                                    $orderShipPoststreetNum = $pickupOptions['number']; //Set for PGE
                                    $orderShipPostcompany = isset($pickupOptions['location_name']) ? $pickupOptions['location_name'] : $orderShipPostcompany;
                                }
                            }



                        } else {


                            if(isset($shippingOptions['date'])){

                                $postNLdeliveryDate = strtotime($shippingOptions['date']);

                                if($postNLdeliveryDate > strtotime('tomorrow')) {
                                    $orderShipPostDeliveryDate = date('Y-m-d',$postNLdeliveryDate);
                                    $orderShipPostDeliveryTime  = date('H:i', $postNLdeliveryDate);
                                }




                            }


                            /*if($orderShipPostDeliveryDate && isset($shippingOptions['time'])) {

                                foreach($shippingOptions['time'] as $timeOption) {
                                    if(isset($timeOption['start'])) {
                                        $orderShipPostDeliveryTime  = date('H:i', strtotime($timeOption['start']));

                                    }


                                }


                            }*/

                        }


                    }

                    if(strlen($orderShipPostcompany) > 35) {

                        $orderShipPostcompany = substr($orderShipPostcompany,0,35);
                    }


                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToFirstName', $order->get_shipping_first_name()));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToLastName', $order->get_shipping_last_name()));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToCompanyName', $orderShipPostcompany));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToBuildingName', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToDepartment', ''));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToFloor', ''));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToDoorcode', ''));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToStreet', ''));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToHouseNo', ''));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToAnnex', ''));



                    //SHIP TO POSTAL CODE
                    if(strlen($orderShipPostCode) == 0) {
                        $failed->addError(" shipToPostalCode length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($orderShipPostCode) > 10) {
                            $failed->addError(" shipping_postcode length is greater than 10 characters");
                            $isvalidate = false;
                        }
                    }

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToPostalCode', $orderShipPostCode));

                    if(strlen($orderShipPostcity) == 0) {
                        $failed->addError(" shipToCity length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($orderShipPostcity) > 30) {
                            $failed->addError(" shipping_city length is greater than 30 characters");
                            $isvalidate = false;
                        }
                    }

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToCity', $orderShipPostcity));


                    if(strlen($orderShipPostcountry) == 0) {
                        $failed->addError(" shipToCountryCode length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($orderShipPostcountry) > 2) {
                            $failed->addError(" shipToCountryCode length is greater than 2 characters");
                            $isvalidate = false;
                        }
                    }

                    //SHIPPING COUNTRY CODE
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToCountryCode', $orderShipPostcountry));

                    if(strlen($order->get_shipping_country()) == 0) {
                        $failed->addError(" shipToCountry length is null");
                        $isvalidate = false;
                    }

                    //SHIPPING COUNTRY
                    if($order->get_shipping_country())
                        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToCountry', WC()->countries->countries[$order->get_shipping_country()]));
                    else
                        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToCountry', ''));


                    //SHIPPING PHONE
                    $billingPhoneOr = $order->get_billing_phone() ? $order->get_billing_phone() : '1';
                    $billingPhone = preg_replace("/\s+/", "", $billingPhoneOr);




                    if(strlen($billingPhone) > 15) {
                        $failed->addError(" shipping_phone length is greater than 15 characters");
                        $isvalidate = false;
                    }




                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToPhone', $billingPhone));

                    //Shipping Address

                    if(strlen($orderShipPoststreet) == 0) {
                        $failed->addError(" shipping_address_1 length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($orderShipPoststreet .$orderShipPoststreetNum) > 100) {
                            $failed->addError(" shipToStreetHouseNrExt length is greater than 100 characters");
                            $isvalidate = false;
                        }
                    }

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToStreetHouseNrExt',
                        (strlen($orderShipPoststreetNum) > 0) ? $orderShipPoststreet . " " . $orderShipPoststreetNum : $orderShipPoststreet
                    ));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToArea', ''));

                    $woo_countries = new WC_Countries();
                    $states = $woo_countries->get_states($order->get_shipping_country());
                    $region = $order->get_shipping_city();
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToRegion', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToRemark', ''));

                    if(strlen($order->get_billing_email()) == 0) {
                        //$failed->addError(" shipToEmail length is null");
                        //$isvalidate = false;
                    } else {
                        if(strlen($order->get_billing_email()) > 50) {
                            $failed->addError(" billing_email length is greater than 50 characters");
                            $isvalidate = false;
                        }
                    }

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToEmail', $order->get_billing_email()));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToTitle', ''));

                    if(strlen($order->get_billing_last_name()) == 0) {
                        $failed->addError(" invoiceToFirstName length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($order->get_billing_last_name()) > 35) {
                            $failed->addError(" billing_last_name length is greater than 35 characters");
                            $isvalidate = false;
                        }
                    }

                    if(strlen($order->get_billing_first_name()) > 35) {
                        $failed->addError(" billing_first_name length is greater than 35 characters");
                        $isvalidate = false;
                    }

                    if(strlen($order->get_billing_company()) > 35) {
                        $failed->addError(" billing_company length is greater than 35 characters");
                        $isvalidate = false;
                    }


                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToFirstName', $order->get_billing_first_name()));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToLastName', $order->get_billing_last_name()));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToCompanyName', $order->get_billing_company()));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToDepartment', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToFloor', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToDoorcode', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToStreet', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToHouseNo', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToAnnex', ''));

                    if(strlen($order->get_billing_postcode()) == 0) {
                        $failed->addError(" invoiceToPostalCode length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($order->get_billing_postcode()) > 10) {
                            $failed->addError(" billing_postcode length is greater than 10 characters");
                            $isvalidate = false;
                        }
                    }

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToPostalCode', $order->get_billing_postcode()));
                    if(strlen($order->get_billing_city()) == 0) {
                        $failed->addError(" invoiceToCity length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($order->get_billing_city()) > 30) {
                            $failed->addError(" shipping_city length is greater than 30 characters");
                            $isvalidate = false;
                        }
                    }

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToCity', $order->get_billing_city()));
                    if(strlen($order->get_billing_country()) == 0) {
                        $failed->addError(" invoiceToCountryCode length is null");
                        $isvalidate = false;
                    } else {
                        if(strlen($order->get_billing_country()) > 2) {
                            $failed->addError(" billing_country Code length is greater than 2 characters");
                            $isvalidate = false;
                        }
                    }

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToCountryCode', $order->get_billing_country()));

                    if(!empty($order->get_billing_country()))
                        $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToCountry', WC()->countries->countries[$order->get_billing_country()]));
                    else {
                        $failed->addError(" billing country is empty");
                        $isvalidate = false;
                    }



                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToPhone', $billingPhone));

                    if(strlen($order->get_billing_address_1()) == 0) {
                        $failed->addError(" billing_address_1 length is null");
                        $isvalidate = false;
                    } else {
                        if (strlen($order->get_billing_address_1() . $order->get_billing_address_2()) > 100) {
                            $failed->addError(" BillingToStreetHouseNrExt length is greater than 100 characters");
                            $isvalidate = false;
                        }
                    }


                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToStreetHouseNrExt',
                        (strlen(trim($order->get_billing_address_2())) > 0 ) ?  trim($order->get_billing_address_1()) . " " . trim($order->get_billing_address_2()) : trim($order->get_billing_address_1())
                    ));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToArea', ''));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToRegion', ''));


                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToRemark', ''));

                    if(strlen($order->get_billing_email()) == 0) {
                        $failed->addError(" invoiceToEmail length is null");
                        $isvalidate = false;
                    }

                    if(strlen($order->get_billing_email()) > 50) {
                        $failed->addError(" invoiceToEmail length is greater than 50");
                        $isvalidate = false;
                    }

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToEmail', $order->get_billing_email()));
//Customization for Rextro function getretailername added to get retailername configured and to add as language/channel ID
					$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','language', $nameRetailer));
//                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','language', $newstring = substr(get_locale(), -2,2)));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','remboursAmount', ''));

                    $order_shipping_method_id = '';
                    $shipping_items = $order->get_items('shipping');

                    foreach($shipping_items as $el) {
                        $order_shipping_method_id = $el['method_id'];
                    }


                    if(!$shippingCodePostNL) {

                        if(strtolower($order->get_shipping_country()) === 'nl')
                            $order_shipping_method_id = "PNLP";
                        else
                            $order_shipping_method_id = get_outside_nl_shipping($order->get_shipping_country());


                    } else { //From PostNL-WooCommerce Plugin
                        $order_shipping_method_id = $shippingCodePostNL;
                    }

                    //Add Age Check option for NL
                    $ageCheckoption = postnl_fulfilment_shipping_age_check($order->get_shipping_country(), $order);

                    if(!empty($ageCheckoption) && strtolower($order->get_shipping_country()) === 'nl' )
                        $order_shipping_method_id = $ageCheckoption;

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shippingAgentCode', $order_shipping_method_id));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipmentType', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipmentProductOption', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipmentOption', ''));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','receiverDateOfBirth', ''));

                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','IDExpiration', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','IDNumber', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','IDType', ''));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','requestedDeliveryDate', $orderShipPostDeliveryDate));
                    $node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','requestedDeliveryTime', $orderShipPostDeliveryTime));

                    if(strlen($order->get_customer_note()) > 0) {
                        $comment = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','comment');
                        if (strlen($order->get_customer_note()) > 200)
                            $comment->appendChild($xml->createCDATASection(substr($order->get_customer_note(),0,200)));
                        else
                            $comment->appendChild($xml->createCDATASection($order->get_customer_note()));

                        $node->appendChild($comment);
                    }

                    $node2 = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrderLines');
                    $items = $order->get_items('line_item');

                    $exportedItems = 0;
                    foreach($items as $item) {
                        $orderedProduct = $item->get_product();
                        $productSKU = $orderedProduct->get_sku();

                        if($orderedProduct->is_virtual())
                            continue;
                        if ($orderedProduct->is_downloadable('yes'))
                            continue;
//Customization for Rextro to skip bundled product as orderline when using plugin yith-woocommerce-product-bundles
						if (metadata_exists( 'post', $orderedProduct->get_id(), '_yith_bundle_product_version' ))
                            continue;  						
						if (metadata_exists( 'post', $orderedProduct->get_id(), '_yith_wcpb_bundle_data' ))
                            continue;  													
//Customization for Rextro to skip bundled product as orderline when using plugin woocommerce-product-bundles
						if (metadata_exists( 'post', $orderedProduct->get_id(), '_wc_pb_group_mode' ))
                            continue;                       

                        if(strlen($productSKU) == 0) {
                            $failed->addError(" No SKU Found in the ordered Item");
                            $isvalidate = false;
                        } elseif (strlen($productSKU) > 24) {

                            $failed->addError(" Item SKU length is greater than 24 characters");
                            $isvalidate = false;
                        }

                        if(strlen($item['qty']) == 0) {
                            $failed->addError(" quantity length is null");
                            $isvalidate = false;
                        } else {
                            if(strlen($item['qty']) > 5) {
                                $failed->addError(" quantity length is greater than 5 characters");
                                $isvalidate = false;
                            }
                        }

                        $orderItemName = '';


                        if(strlen($item['name']) > 255) {
                            $orderItemName = substr($item['name'],0,255);
                        } else
                            $orderItemName = $item['name'];

                        $orderItemNameClean = str_replace($this->_getBadCharacters(), '', $orderItemName);


                        $line = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrderLine');
                        $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','itemNo', $productSKU));
                        $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','itemDescription', $orderItemNameClean));
                        $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','quantity', $item['qty']));
                        $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','singlePriceInclTax', $this->formatnumber($item['line_subtotal'])));

                        $giftMessage = '';
                        $itemmeta = $item->get_meta_data();

                        if(is_array($itemmeta)) {
                            foreach ($itemmeta as $metaobject) {
                                $itemmetadata = $metaobject->get_data();
                                if($itemmetadata['key'] === 'card_message_text') {
                                    $giftMessage = $itemmetadata['value'];

                                    $giftMessage = str_replace($this->_getBadCharacters(), '', $giftMessage);
                                    $giftMessage = substr($giftMessage,0,255);
                                }


                            }
                        }

                        if(!empty($giftMessage)){
                            $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','GiftWrap', '9'));
                            $line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','GiftCardInstruction', $giftMessage));
                        }

                        $node2->appendChild($line);

                        if ($isvalidate == true)
                            $exportedItems = $exportedItems + 1;

                    }

                    if ($exportedItems == 0 ) {
                        $isvalidate = false;
                        $failed->addError(" No valid items to export");

                    }

                    $node->appendChild($node2);

                    if($isvalidate == true) {
                        //add_post_meta($order_id, 'ecsExport', 'yes');
                        $processedOrders[] = $order_id;
                        $orders->appendChild($node);
                        $isEmpty = $isEmpty + 1;
                    } else {

                        foreach($FailedOrders as $fails) {
                            foreach($fails->get_errors() as $fail) {
                                //error_log($fail);
                            }
                        }

                        array_push($FailedOrders, $failed);
                    }
                }

            }

            $result = count($order_split);
            if($isEmpty > 0) {
                $t = time();

                $seq = sprintf("%02d",($orderNo % 100));


                $xml->appendChild($message);

                //Remove Empty fields:
                $xpath = new DOMXPath($xml);

                while (($node_list = $xpath->query('//*[not(*) and not(@*) and not(text()[normalize-space()])]')) && $node_list->length) {
                    foreach ($node_list as $node) {
                        $node->parentNode->removeChild($node);
                    }
                }


                $xml->formatOutput = true;

                //Check XSD:

                $is_valid_xml = true;

                if(function_exists('libxml_use_internal_errors'))
                    libxml_use_internal_errors(true);

                if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'schema'.DIRECTORY_SEPARATOR."deliveryOrder_new.xsd")) {

                    $is_valid_xml = $xml->schemaValidate(__DIR__.DIRECTORY_SEPARATOR.'schema'.DIRECTORY_SEPARATOR."deliveryOrder_new.xsd");

                }


                if( !$is_valid_xml) {

                    $validationError = '';

                    if(function_exists('libxml_use_internal_errors')) {
                        $errors = libxml_get_errors();
                        foreach ($errors as $error) {
                            $validationError = $validationError.sprintf('XML error "%s" [%d] (Code %d) in %s on line %d column %d' . "\n",
                                    $error->message, $error->level, $error->code, $error->file,
                                    $error->line, $error->column);
                        }

                        libxml_clear_errors();
                        libxml_use_internal_errors(false);

                    }

                    $failed = new Failederrors();
                    $failed->set_orderID('');
                    $failed->set_orderID('');
                    $failed->addError(" Order XML is invalid: ".$validationError);
                    array_push($FailedOrders, $failed);

                } else {
                    if(function_exists('libxml_use_internal_errors')) {
                        libxml_clear_errors();

                        libxml_use_internal_errors(false);
                    }

                    foreach($processedOrders as $processedOrderId) {
                        if($processedOrderId)
                            add_post_meta($processedOrderId, 'ecsExport', 'yes');

                    }

                    $filename = 'ORD' . date("YmdHis", $t) .'-'.$seq. '.xml';

                    $remote_directory = $Path . '/';
                    // $remote_directory = '/';
                    $success = $sftp->put($remote_directory . $filename, $xml->saveXml());

                    $table_name_ecs = $wpdb->prefix . 'ecs';
                    $querylast = "SELECT * FROM ".$table_name_ecs." WHERE keytext = 'lastOrdername'";
                    $statesmeta = $wpdb->get_results($querylast);
                    $lastname = '';

                    if(count($statesmeta) > 0) {
                        foreach($statesmeta as $k) {
                            $wpdb->query($wpdb->prepare("UPDATE ".$table_name_ecs." SET type = '".$filename."' WHERE id= %d", $k->id));
                        }
                    } else {

                        $table_name_ecs = $wpdb->prefix . 'ecs';
                        $wpdb->insert($table_name_ecs, array(
                            'type' => $filename,
                            'enable' => 'true',
                            'keytext' => 'lastOrdername'
                        ));
                    }

                    $orderNo = $orderNo +1;
                    $wpdb->query($wpdb->prepare("UPDATE ".$table_name_ecs." SET type = '".$orderNo."' WHERE   id= %d", $OrderMessageKey));


                }


            }
        }

        if(count($FailedOrders) > 0) {

            $Errors = '
                <!DOCTYPE html>
                 <html>
                 <body><p>';

            $Errors .= 'An error occurred processing  Order export file';
            $Errors .= '<br><b>Message:</b><br>';

            foreach($FailedOrders as $fails) {
                $Errors .= 'Order ID :' . $fails->get_orderID();
                $Errors .= '<br>';

                foreach($fails->get_errors() as $fail) {
                    //error_log('POSTNL ECS: '.$fails->get_orderID().' '.$fail);

                    $Errors .= $fail;

                    $Errors .= '<br>';
                }
            }


            $Errors .= '</p></body>
                </html>';

            $this->sendErrorEmail($Errors,'Order');
        }

    }

//Customization for Rextro function getretailername added to get retailername configured and to add in ordernumber as prefix
	    public function getRetailerName() {
        global $wpdb;
        $nameRetailer = '';
		$email = '';
        $table_name_ecs = $wpdb->prefix . 'ecs';
		// find list of states in DB
		$qry = "SELECT * FROM ".$table_name_ecs." WHERE keytext ='general' ORDER BY id DESC  LIMIT 1";
		$states = $wpdb->get_results($qry);
		$settingID = '';
		foreach($states as $k) {
			$settingID = $k->id;
		}
				// find list of states in DB
		$table_name = $wpdb->prefix . 'ecsmeta';
		$qrymeta = "SELECT * FROM ".$table_name." WHERE settingid = '".$settingID."'";
		$statesmeta = $wpdb->get_results($qrymeta);

		foreach($statesmeta as $k) {
			if ($k->keytext == "Name") {
				$nameRetailer = $k->value;
												
			}
            
            if ($k->keytext == "Email") {
				$email = $k->value;
						
			}
        }
        
        return [
            'retailer'  =>   $nameRetailer,
            'email'     =>   $email
        ];
    }



}