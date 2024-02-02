<?php
class PostNLProduct extends PostNLProcess
{
    const EAN_ARRAY = [
        'ean',
        'ean-no',
        'eanNo',
        'ean-13',
        'eanno'
    ];

    public function __construct() {

    }

    public function processProducts()
    {


        $EcsSftpSettings = $this->getFtpSettings();

        $EcsProductSettings = ecsProductSettings::init();

        $Path = '';

        $eanAttribute = '';
        $settingID = $EcsProductSettings->getSettingId();

        if ($settingID)
            $statesmeta = $EcsProductSettings->loadProductSettings($settingID);
        else {
            error_log('Product settings not found');
            return;
        }

        foreach ($statesmeta as $k) {

            if ($k->keytext == "Path") {
                $Path = $k->value;
            }

            if ($k->keytext == "no") {
                $no = $k->value;
            }

            if ($k->keytext == "ean_attribute") {
                $eanAttribute = trim($k->value);
            }
        }

        $eanAttribute = empty($eanAttribute) ? 'ean' : strtolower($eanAttribute) ;

        $sftp = $this->checkFtpSettings($Path);

        if(!$sftp)
            return false;


        $lastfile = '';

        global $wpdb;
        $table_name_ecs = $wpdb->prefix . 'ecs';
        $qrymeta = "SELECT * FROM $table_name_ecs " . "WHERE keytext = 'LastproductID'  ";
        $statesmeta = $wpdb->get_results($qrymeta);
        $orderNo = '0';

        foreach($statesmeta as $k) {
            $orderNo = $k->type;
            $NextorderNo = $orderNo + 1;
            $wpdb->query($wpdb->prepare("UPDATE $table_name_ecs SET type = '".$NextorderNo."' WHERE  id= %d", $k->id));
        }

        $xml = new DOMDocument('1.0');
        $message = $xml->createElementNS("http://www.toppak.nl/item", 'message');
        $xml->appendChild($message);
        $message->appendChild($xml->createElementNS("http://www.toppak.nl/item",'type', 'item'));
        $message->appendChild($xml->createElementNS("http://www.toppak.nl/item",'messageNo', $orderNo));

        $t = time();
        $message->appendChild($xml->createElementNS("http://www.toppak.nl/item",'date', date("Y-m-d", $t)));
        $message->appendChild($xml->createElementNS("http://www.toppak.nl/item",'time', date("H:i:s", $t)));

        $products = $xml->createElementNS("http://www.toppak.nl/item",'items');
        $message->appendChild($products);
        $Products = get_posts(
            array(
                'post_type' => array('product','product_variation'),
                //'post_status' => wc_get_order_statuses(), //get all available order statuses in an array
                'posts_per_page' => 100,
                'meta_query' => array(
                    array(
                        'key' => 'ecsExport',
                        'compare' => 'NOT EXISTS'
                    )
                )
            )
        );

        $result = count($Products);

        if(!$result)
            return false;

        $Productchunck = array_chunk($Products, $no);
        $FailedOrders = array();

        foreach($Productchunck as $Product_split) {
            $isEmpty = 0;

            foreach($Product_split as $productPostItem) {
                $product_id = $productPostItem->ID;

                $isvalidate = true;
                $failed = new Failederrors();
                $failed->set_orderID($product_id);
                $productpost = $productPostItem;
                //$product = new WC_Product($product_id);
/*
					if($product_id == 273761)
						continue;				
					if($product_id == 21075)
						continue;
					if($product_id == 21076)
						continue;					
					if($product_id == 21073)
						continue;
					if($product_id == 21074)
						continue;					
					if($product_id == 21071)
						continue;
					if($product_id == 21072)
						continue;
					if($product_id == 21069)
						continue;
					if($product_id == 21070)
						continue;
					if($product_id == 21064)
						continue;
					if($product_id == 21065)
						continue;					
					if($product_id == 21062)
						continue;
					if($product_id == 21061)
						continue;
					if($product_id == 21059)
						continue;
					if($product_id == 21060)
						continue;
					if($product_id == 13297)
						continue;
					if($product_id == 13307)
						continue;					
					if($product_id == 13308)
						continue;
					if($product_id == 13309)
						continue;					
					if($product_id == 13296)
						continue;
					if($product_id == 13011)
						continue;
					if($product_id == 13013)
						continue;
					if($product_id == 13014)
						continue;
					if($product_id == 13016)
						continue;
					if($product_id == 13018)
						continue;					
					if($product_id == 13020)
						continue;
					if($product_id == 13022)
						continue;
					if($product_id == 13024)
						continue;
					if($product_id == 13026)
						continue;
					if($product_id == 13029)
						continue;
					if($product_id == 13031)
						continue;
					if($product_id == 13001)
						continue;
					if($product_id == 13002)
						continue;					
					if($product_id == 13003)
						continue;
					if($product_id == 12782)
						continue;
					if($product_id == 12781)
						continue;
					if($product_id == 12619)
						continue;
					if($product_id == 12621)
						continue;
					if($product_id == 12632)
						continue;					
					if($product_id == 12634)
						continue;
					if($product_id == 12645)
						continue;					
					if($product_id == 12649)
						continue;
					if($product_id == 12650)
						continue;
					if($product_id == 12517)
						continue;
					if($product_id == 12518)
						continue;
					if($product_id == 12279)
						continue;
					if($product_id == 12281)
						continue;					
					if($product_id == 12282)
						continue;
					if($product_id == 12185)
						continue;
					if($product_id == 12188)
						continue;
					if($product_id == 11708)
						continue;
					if($product_id == 11705)
						continue;
					if($product_id == 11418)
						continue;					
					if($product_id == 11422)
						continue;
					if($product_id == 11423)
						continue;
					if($product_id == 11427)
						continue;
					if($product_id == 11428)
						continue;
					if($product_id == 11432)
						continue;
					if($product_id == 11482)
						continue;					
					if($product_id == 11483)
						continue;
					if($product_id == 11333)
						continue;					
					if($product_id == 11334)
						continue;
					if($product_id == 11335)
						continue;
					if($product_id == 11343)
						continue;
					if($product_id == 11344)
						continue;
					if($product_id == 11345)
						continue;
					if($product_id == 11320)
						continue;					
					if($product_id == 11321)
						continue;
					if($product_id == 11322)
						continue;
					if($product_id == 11332)
						continue;
					if($product_id == 11258)
						continue;
					if($product_id == 11259)
						continue;
					if($product_id == 11260)
						continue;					
					if($product_id == 11278)
						continue;
					if($product_id == 11250)
						continue;
					if($product_id == 11251)
						continue;
					if($product_id == 11252)
						continue;
					if($product_id == 11233)
						continue;
					if($product_id == 11234)
						continue;					
					if($product_id == 11235)
						continue;
					if($product_id == 11241)
						continue;					
					if($product_id == 11242)
						continue;
					if($product_id == 11231)
						continue;
					if($product_id == 11232)
						continue;
					if($product_id == 11200)
						continue;
					if($product_id == 11201)
						continue;
					if($product_id == 11092)
						continue;					
					if($product_id == 11093)
						continue;
					if($product_id == 11094)
						continue;
					if($product_id == 11095)
						continue;
					if($product_id == 11105)
						continue;
					if($product_id == 11106)
						continue;
					if($product_id == 10985)
						continue;
					if($product_id == 10997)
						continue;
					if($product_id == 10998)
						continue;
					if($product_id == 11024)
						continue;
					if($product_id == 11025)
						continue;
					if($product_id == 11107)
						continue;
					if($product_id == 10944)
						continue;
					if($product_id == 10945)
						continue;
					if($product_id == 10958)
						continue;
					if($product_id == 10959)
						continue;
					if($product_id == 10971)
						continue;
*/
                if($productPostItem->post_type == 'product_variation') {
                    $product = new WC_Product_Variation($product_id);
                }
                else {
                    //$product = new WC_Product($product_id);
                    $product = wc_get_product($product_id);

                    if($product->is_type('variable'))
                        continue;

                    if($product->is_downloadable())
                        continue;

                    if($product->is_virtual())
                        continue;
                }

                $node = $xml->createElementNS("http://www.toppak.nl/item",'item');

                //SKU
                if(strlen($product->get_sku()) == 0) {
                    $failed->addError(" itemNo length is null");
                    $isvalidate = false;
                } else {
                    if(strlen($product->get_sku()) > 24) {
                        $failed->addError(" itemNo length is greater than 24 characters");
                        $isvalidate = false;

                    }
                }

                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'itemNo', $product->get_sku()));


                //DESCRIPTION

                if(strlen($product->get_title()) == 0) {
                    $failed->addError(" description length is null");
                    $isvalidate = false;
                } else {
                    $description2 = '';
                    if(strlen($product->get_title()) > 30) {
                        $description2 = htmlentities(substr($product->get_title(), 30,30));
                        //split in two
                        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description', htmlentities(substr($product->get_title(), 0, 30))));

                        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description2', $description2));
                    } else {
                        //split in two
                        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description', htmlentities($product->get_title())));
                        $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description2', ''));
                    }
                }


                //ATTRIBUTES - NOT REQUIRED
                /*if($product->is_type('variation')) {
                    $attributes = $product->get_attributes();
                    $parentData = $product->get_parent_data();


                }
                else
                    $attributes = $product->get_attributes();
                */



                //ATTRIBUTES ADD
                $unitOfMeasure = $product->get_attribute('unitOfMeasure') ? $product->get_attribute('unitOfMeasure') : '';

                $vendorItemNo = $product->get_attribute('vendorItemNo') ? $product->get_attribute('vendorItemNo') : '';
                $bac = $product->get_attribute('bac') ? $product->get_attribute('bac') : '';
                $validFrom = $product->get_attribute('validFrom') ? $product->get_attribute('validFrom') : '';
                $validTo = $product->get_attribute('validTo') ? $product->get_attribute('validTo') : '';
                $adr = $product->get_attribute('adr') ? $product->get_attribute('adr') : '';
                $lot = $product->get_attribute('lot') ? $product->get_attribute('lot') : '';
                $sortOrder = $product->get_attribute('sortorder') ? $product->get_attribute('sortorder') : '';
                $minStock = $product->get_attribute('minstock') ? $product->get_attribute('minstock') : '';
                $maxStock = $product->get_attribute('maxstock') ? $product->get_attribute('maxstock') : '';
                $productType = $product->get_attribute('product-type') ? $product->get_attribute('product-type') : '';

                //if plugin for EAN is installed use it https://wordpress.org/plugins/product-gtin-ean-upc-isbn-for-woocommerce/
                $eanNo = $product->get_meta('_wpm_gtin_code') ? $product->get_meta('_wpm_gtin_code') : '';
                //else use default functionality with EAN as custom attribute
                if(strlen($eanNo) == 0) {
                    $eanNo = $product->get_attribute($eanAttribute) ? $product->get_attribute($eanAttribute) : '';
                }


                /*foreach(self::EAN_ARRAY as $ean_search){
                    if (array_key_exists($ean_search, $attributes))
                        $eanNo = $attributes[$ean_search];
                    }*/

                //UOM
                if(strlen($unitOfMeasure) > 10) {
                    $failed->addError(" unitOfMeasure length is greater than 10 characters");
                    $isvalidate = false;
                }

                if(strlen($unitOfMeasure) == 0) {
                    $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'unitOfMeasure', 'ST'));
                } else {
                    $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'unitOfMeasure', $unitOfMeasure));
                }

                //HEIGHT
                $height = $product->get_height();
                if(strlen($product->get_height()) == 0) {
                    $height = 1;
                } else {
                    if(strlen($product->get_height()) > 255) {
                        $failed->addError(" height length is greater than 255 characters");
                        $isvalidate = false;
                    }
                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'height', $height));

                //WIDTH
                $width = $product->get_width();
                if(strlen($product->get_width()) == 0) {
                    $width = 1;
                } else {
                    if(strlen($product->get_width()) > 255) {
                        $failed->addError(" width length is greater than 255 characters");
                        $isvalidate = false;
                    }
                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'width', $width));

                //LENGTH
                $length = $product->get_length();
                if(strlen($product->get_length()) == 0) {
                    $length = 1;
                } else {
                    if (strlen($product->get_length()) > 255) {
                        $failed->addError(" Product length length is greater than 255 characters");
                        $isvalidate = false;
                    }
                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'depth', $length));

                //WEIGHT
                $weight = $product->get_weight();
                if(strlen($product->get_weight()) == 0) {
                    $weight = 1;
                } else {
                    if (strlen($product->get_weight()) > 255) {
                        $failed->addError(" Product weight length is greater than 255 characters");
                        $isvalidate = false;
                    }
                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'weight', $weight));

                //VENDOR ITEM
                if(strlen($vendorItemNo) > 30) {
                    $failed->addError(" vendorItemNo length is greater than 30 characters");
                    $isvalidate = false;
                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'vendorItemNo', $vendorItemNo));

                //EAN
                if(strlen($eanNo) == 0) {
                    $eanNo = $product->get_sku();

                } else {
                    if(strlen($eanNo) > 15) {
                        $failed->addError(" eanNo length is greater than 15 characters");
                        $isvalidate = false;
                    }
                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'eanNo', $eanNo));

                //BAC
                if(strlen($bac) > 255) {
                    $failed->addError(" bac length is greater than 255 characters");
                    $isvalidate = false;
                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'bac', $bac));

                //OTHER attributes
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'validFrom', $validFrom));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'validTo', $validTo));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'expiry', 'false'));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'adr', $adr));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'active', 'true'));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'lot', $lot));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'sortOrder', $sortOrder));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'minStock', $minStock));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'maxStock', $maxStock));
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'retailPrice', $this->formatnumber($product->get_regular_price())));

                //PURCHASE PRICE
                if(strlen($product->get_sale_price()) == 0) {
                    $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'purchasePrice', $this->formatnumber($product->get_regular_price())));
                } else {
                    $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'purchasePrice', $this->formatnumber($product->get_sale_price())));
                }

                //PRODUCT TYPE
                if(strlen($productType) > 255) {
                    $failed->addError(" Product Type length is greater than 255 characters");
                    $isvalidate = false;

                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'productType', $productType));

                //MASTER PRODUCT
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'defaultMasterProduct', 'false'));

                //HANGING STORAGE
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'hangingStorage', 'false'));

                //BACKORDERS
                $back = $product->get_backorders();
                if(strcmp($back, "no") != 0) {
                    $back = 'true';
                } else {
                    $back = 'false';
                }
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'backOrder', $back));

                //ENRICHED
                $node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'enriched', 'true'));


                if($isvalidate == true) {
                    $products->appendChild($node);
                    add_post_meta($product_id, 'ecsExport', 'yes');
                    $isEmpty = $isEmpty + 1;
                } else {
                    array_push($FailedOrders, $failed);
                }



                if($isEmpty > 0) { //Export products
                    $t = time();
                    $filename = 'PRD' . date("YmdHis", $t) . '.xml';

                    $message->appendChild($products);
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

                    if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'schema'.DIRECTORY_SEPARATOR."item.xsd")) {

                        $is_valid_xml = $xml->schemaValidate(__DIR__.DIRECTORY_SEPARATOR.'schema'.DIRECTORY_SEPARATOR."item.xsd");

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

                        $failed->addError(" Product XML is invalid: ".$validationError);

                        array_push($FailedOrders, $failed);

                    } else {

                        if(function_exists('libxml_use_internal_errors')) {
                            libxml_clear_errors();
                            libxml_use_internal_errors(false);
                        }


                        //End check XSD
                        //$xml->save(ECS_DATA_PATH."/product.xml");
                        $t = time();
                        $filename = 'PRD' . date("YmdHis", $t) . '.xml';
                        //$local_directory = ECS_DATA_PATH.'/product.xml';

                        //$remote_directory = 'woocommerce_test/Productdata/';
                        $remote_directory = $Path . '/';
                        $success = $sftp->put($remote_directory . $filename, $xml->saveXml());
                        global $wpdb;
                        $table_name_ecs = $wpdb->prefix . 'ecs';
                        $querylast = "SELECT * FROM $table_name_ecs " . "WHERE keytext = 'lastproductname'  ";
                        $statesmeta = $wpdb->get_results($querylast);
                        $lastname = '';
                        if(count($statesmeta) > 0) {
                            foreach($statesmeta as $k) {
                                $wpdb->query($wpdb->prepare("UPDATE ".$table_name_ecs." SET type = '".$filename."' WHERE id= %d", $k->id));
                            }
                        } else {
                            $wpdb->insert($table_name_ecs,
                                array(
                                    'type' => $filename,
                                    'enable' => 'true',
                                    'keytext' => 'lastproductname'
                                )
                            );
                        }

                    }


                }
            }

            if(count($FailedOrders) > 0) {

                $Errors = '
					<!DOCTYPE html>
					    <html>
				    	    <body><p>';

                $Errors .= 'An error occurred processing  Product export file';

                $Errors .= '<br><b>Message:</b><br>';

                foreach($FailedOrders as $fails) {
                    $Errors .= '<br>';
                    $Errors .= 'Product ID :' . $fails->get_orderID();
                    $Errors .= '<br>';
                    foreach($fails->get_errors() as $fail) {
                        $Errors .= $fail;
                        $Errors .= ' <br>';
                    }
                }

                $Errors .= '</p></body>
						</html>';

                $this->sendErrorEmail($Errors,'Product');




            }




        }
    }
}