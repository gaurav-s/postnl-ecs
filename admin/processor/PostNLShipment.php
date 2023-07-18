<?php
class PostNLShipment extends PostNLProcess
{
    
    
    public function __construct() {
			
    }

    public function processShipment(){

        
        
        $EcsShipmentSettings = ecsShipmentSettings::init();
        $Path = '';
        $Cron = '';
		$Path = '';
		$Inform = '';
		$tracking = '';
		$enable = '';
        $lastfile = '';
        
        
        $settingID = $EcsShipmentSettings->getSettingId();
        
        if($settingID) {
            $statesmeta = $EcsShipmentSettings->loadShipmentSettings($settingID); 
        }
		else { 
		    error_log('Shipment Settings not found'); 
			return;
		}
				
		foreach ($statesmeta as $k) {
				
			if ($k->keytext == "Path") {
				$Path = $k->value;
			}
				
            if($k->keytext == "Cron") {
                $Cron = $k->value;
            }
            
            if($k->keytext == "Path") {
                $Path = $k->value;

            }
            if($k->keytext == "tracking") {
                $tracking = $k->value;
                
            }
            if($k->keytext == "Inform") {
                $Inform = $k->value;
            }
        }
        
        $EcsSftpSettings = $this->getFtpSettings();
        $sftp = $this->checkFtpSettings($Path);

        if(!$sftp) 
            return false;

        
        $retailerDetails = $this->getRetailerName();

        $nameRetailer = $retailerDetails['retailer'];

        $sftp->chdir($Path); // open directory 'test'
        $endPath = $sftp->pwd();
        
        foreach($sftp->nlist() as $filename) {
            $codesNames = explode(".xml", $filename);

            if(count($codesNames) > 0) {
                if($filename == '.' || $filename == '..') 
                   continue;
                
                    
                //$sftp->get($sftp->pwd() . '/' . $filename,ECS_DATA_PATH."/".$filename);							
                //if(file_exists(ECS_DATA_PATH."/".$filename) && filesize(ECS_DATA_PATH."/".$filename) > 0) {
                  //  $xml = simplexml_load_file(ECS_DATA_PATH."/".$filename, 'SimpleXMLElement', LIBXML_NOWARNING);
                  $shipmentFileData = $sftp->get($sftp->pwd() . '/' . $filename);							
                  if($shipmentFileData) {
                    $xml = simplexml_load_string($shipmentFileData, 'SimpleXMLElement', LIBXML_NOWARNING);
                         
                    $deleteFile = false;
                    $validate = true; 
                    $inventory_errors = array();
                    $xmlRetailname = (string) $xml->retailerName;
                        
                    if(strcmp(trim($xmlRetailname), trim($nameRetailer)) == 0) {
                            
                        $validate = true;

                    } else {
                                                          
                        $validate = false; 
                            
                        array_push($inventory_errors, 'The retailer name from the shipment message and the system configuration do not match');
                    }
                        
                        
                    $shippedOrders_ids = "";
                    $shipmentProcessOrders = [];
                    
                    foreach ($xml->orderStatus as $stock) {
                        $orderid  = $stock->orderNo;
//Customization for Rextro remove retailername from ordernumber as prefix
                        $intOrder = (string) $orderid;
						$intOrder = substr($intOrder,2,strlen($intOrder));						
                        if(false === get_post_status((int) $intOrder)) {
                            $validate = false; 
                            array_push($inventory_errors, 'Order  ID :' . $intOrder . '  is not found for the shipment');
                            continue; // Skip further check
                        }

                        $processedFiles = get_post_meta($intOrder, 'shipmentFiles', true);
                        if(!empty($processedFiles)) {
                                
                            $processedFilesArray = json_decode($processedFiles);

                            if( is_array($processedFilesArray) ) {

                                if(in_array($filename,$processedFilesArray)) {
                                    $deleteFile = true;
                                        
                                    array_push($inventory_errors, 'Shipment File :' . $filename . '  was already processed');
                                    continue;
                                }
                                            
                            }

                        } else {
                            $processedFilesArray = [];
                        }
                            
                        $shipmentProcessOrders[] = 	$intOrder;
                        $countElement = 0;
                            
                        foreach($stock->orderStatusLines as $pruduct2) {
                            foreach($pruduct2 as $pruduct) {
                                $countElement = $countElement + 1;
                            }
                        }
                            
                        foreach($stock->orderStatusLines as $pruduct1) {
                            foreach($pruduct1 as $pruduct) {
                                $shippedOrders_ids .= $pruduct->itemNo . ":";
//Customization for Rextro remove retailername from ordernumber as prefix
                                $order = new WC_Order((int) $intOrder);
                                $items = $order->get_items('line_item');
                                $productExist = "0";
                                
                                foreach($items as $item) {
                                    $product = $item->get_product();
                                    $productSKU = $product->get_sku();
                                    if(strlen($item['product_id']) > 0) {
                                            
                                    }
                                        
                                        
                                    if($product->get_sku() == $pruduct->itemNo) {
                                        $productExist = "1";
                        
                                    }
                                }
                                    
                                if($productExist == "0") {
                                    $validate = false; 
                                    array_push($inventory_errors, 'Product  ID :' . $pruduct->itemNo . '   is not found for the shipment');
                                    
                                }
                            }
                        }
                    }
                            
                    if($validate == true && (count($shipmentProcessOrders) > 0)) {
                            
                        $ship_Orders = array();
                            
                            
                        foreach($xml->orderStatus as $stock) {
                            $orderid = $stock->orderNo;
                            $traclCode = $stock->trackAndTraceCode;
//Customization for Rextro remove retailername from ordernumber as prefix
                            $intOrder = (string) $orderid;
							$intOrder = substr($intOrder,2,strlen($intOrder));
                            $stringTrack = (string) $traclCode;
                    
                            ///check if everything is shipped
                            $order = new WC_Order((int) $intOrder);
                            $items = $order->get_items('line_item');
                            $ordExportedItems = 0;
                            $countElement = 0;
                                
                                
                                    
                            foreach ($items as $orderlineItem) {
                                $lineItemProduct = $orderlineItem->get_product();
                            
                                if($lineItemProduct->is_virtual()) 
                                    continue;
                                    
                                if ($lineItemProduct->is_downloadable('yes')) 
                                    continue;
//Customization for Rextro to skip bundled product as orderline when using plugin yith-woocommerce-product-bundles
								if (metadata_exists( 'post', $lineItemProduct->get_id(), '_yith_bundle_product_version' ))
	                            continue;
								if (metadata_exists( 'post', $lineItemProduct->get_id(), '_yith_wcpb_bundle_data' ))
	                            continue;						
//Customization for Rextro to skip bundled product as orderline when using plugin woocommerce-product-bundles
							if (metadata_exists( 'post', $lineItemProduct->get_id(), '_wc_pb_group_mode' ))
        	                    continue;  
                                   
                                $ordExportedItems = $ordExportedItems +1;
                                    
                            }
                                
                            foreach($stock->orderStatusLines as $pruduct2) {
                                    
                                foreach($pruduct2 as $pruduct) {
                                    $countElement = $countElement + 1;
                                }
                            }
                            
                            //Add Track and Trace Codes
                            if(!add_post_meta($intOrder, 'trackAndTraceCode', $stringTrack, true)) {
                                $existingtrackCode = get_post_meta($intOrder, 'trackAndTraceCode', true);
                                $stringTrack = $stringTrack.', '.$existingtrackCode;
                                update_post_meta($intOrder, 'trackAndTraceCode', $stringTrack);
                                        
                            }
                                
                            //Mark Oorder as Completed
                            if($countElement == $ordExportedItems) {
                                    
                                $order = wc_get_order((int) $intOrder);
                                $order->update_status('completed');
                                    
                            } else {
                                $exportedItems =get_post_meta($intOrder, 'exportedItems', true);
                                if(strlen($exportedItems) !== 0) {
                                    $itemsExported = explode(":", $exportedItems);
                                    $itemsExportedNewly = explode(":", $shippedOrders_ids);
                                    $totalItems = count($itemsExported) + count($itemsExportedNewly) -2 ;
                                        
                                    if($totalItems == $ordExportedItems) {
                                            
                                        $order = wc_get_order($intOrder);
                                        $order->update_status('completed');
                                            
                                    } else {
                                        $newExported = $exportedItems . " " . $shippedOrders_ids;
                                        update_post_meta($intOrder, 'exportedItems', $newExported);
                                    }
                                } else {
                                        add_post_meta($intOrder, 'exportedItems', $shippedOrders_ids, yes);
                                }
                            }
                            
                            //End Completed Order marking
                                
                            
                            //Mark File as processed
                                
                            $processedFilesArray[] = $filename;
                            $processedFilesJson = json_encode($processedFilesArray);
                                
                            if(count($processedFilesArray) > 1)
                                update_post_meta($intOrder, 'shipmentFiles', $processedFilesJson);
                            else
                                add_post_meta($intOrder, 'shipmentFiles', $processedFilesJson, true);
                            
                            //

                            array_push($ship_Orders, 'Order  ID :' . $stock->orderNo . '  was successfully imported ');
                        }
                            
                        $sftp->delete($sftp->pwd() . '/' . $filename);
                            
                        //Capture processed Filename


                    } else {

                        if($deleteFile)
                            $sftp->delete($sftp->pwd() . '/' . $filename);
                            
                        $Errors = '
                            <!DOCTYPE html>
                            <html>
                            <body><p>';
                    
                        $Errors .= 'An error occurred processing file:' . $filename . '<br>';
                        $Errors .= '<b>Message:</b><br>';
                                        
                        foreach($inventory_errors as $fails) {
                            //error_log($fails);
                            $Errors .= $fails;
                            $Errors .= ' <br>';
                        }
                        
                        $Errors .= '</p></body>
                                </html>';
                                
                        $this->sendErrorEmail($Errors,'Shipment');

                    }
                    
                    if(file_exists(ECS_DATA_PATH."/".$filename))
                        unlink(ECS_DATA_PATH."/".$filename);
                }
                
            }
        }
        
        
    }

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