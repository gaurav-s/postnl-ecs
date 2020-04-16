<?php
	class FailedOrder {
		var $OrderiD;
		var $arrayObject = array();
		// Add new element
		public function addError($errror) {
			array_push($this->arrayObject, $errror);
		}
		public function get_errors() {
			return $this->arrayObject;
		}
		public function set_orderID($OrderiD) {
			$this->OrderiD = $OrderiD;
		}
		public function get_orderID() {
			return $this->OrderiD;
		}

	}
	
	
	class ni_order_list {
		public function __construct() {
			
		}
		
		public function page_init() {
			
		}
		
		public function _getBadCharacters()
		{
			return array(
					';',
					'\\',
					'`',
					'\'',
					'"',
					'&',
					'*',
					'{',
					'}',
					'[',
					']',
					'!',
					'<',
					'>',
			);
		}
		
		function productExport() {
			
				require_once(dirname(__DIR__) . "/ecsSftpProcess.php");
				require_once(dirname(__DIR__) . "/export/EcsProductSettings.php");
				$EcsSftpSettings = ecsSftpProcess::init();
		
				$EcsProductSettings = ecsProductSettings::init();
				$Path = '';
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
				
			}
				$ftpCheck = $EcsSftpSettings->checkSftpSettings($Path);
				
				if($ftpCheck[0] == 'SUCCESS') {
				$sftp = $ftpCheck[1];
				
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
				$Products = get_posts(array(
					'post_type' => array('product','product_variation'),
					//'post_status' => wc_get_order_statuses(), //get all available order statuses in an array
					'posts_per_page' => 100,
					'meta_query' => array(
						array(
							'key' => 'ecsExport',
							'compare' => 'NOT EXISTS'
						)
					)
				));
				
				$Productchunck = array_chunk($Products, $no);
				$FailedOrders = array();

				foreach($Productchunck as $Product_split) {
					$isEmpty = 0;
					foreach($Product_split as $productPostItem) {
						$product_id = $productPostItem->ID;
						
						$isvalidate = true;
						$failed = new FailedOrder();
						$failed->set_orderID($product_id);
						$productpost = $productPostItem;
						//$product = new WC_Product($product_id);
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
						if(strlen($productpost->post_name) == 0) {
							$failed->addError(" description length is null");
							$isvalidate = false;
						} else {
							$description2 = '';
							if(strlen($productpost->post_name) > 30) {
								$description2 = substr($productpost->post_name, 30,30);
								//split in two
								$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description', substr($productpost->post_name, 0, 30)));
								
								$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description2', $description2));
							} else {
								//split in two
								$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description', $productpost->post_name));
								$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'description2', ''));
							}
						}
						
						if($product->is_type('variation')) {
							$attributes = $product->get_attributes();
							$parentData = $product->get_parent_data();
						
							
						}
						else $attributes = $product->get_attributes();
						
						
						$unitOfMeasure = $product->get_attribute('unitOfMeasure') ? $product->get_attribute('title') : '';
						
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
						$eanNo = '';
						$ean_array = array('ean','ean-no','eanNo','ean-13','eanno');
						
						foreach($ean_array as $ean_search){
							if (array_key_exists($ean_search, $attributes)) 
								$eanNo = $attributes[$ean_search];
			
						}
						
						if(strlen($unitOfMeasure) > 10) {
							$failed->addError(" unitOfMeasure length is greater than 10 characters");
							$isvalidate = false;
						}
						if(strlen($unitOfMeasure) == 0) {
							$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'unitOfMeasure', 'ST'));
						} else {
							$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'unitOfMeasure', $unitOfMeasure));
						}
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
						if(strlen($vendorItemNo) > 30) {
							$failed->addError(" vendorItemNo length is greater than 30 characters");
							$isvalidate = false;
						}
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'vendorItemNo', $vendorItemNo));
						
						if(strlen($eanNo) == 0) {
							$eanNo = $product->get_sku();
							
						} else {
							if(strlen($eanNo) > 15) {
								$failed->addError(" eanNo length is greater than 15 characters");
								$isvalidate = false;
							}
						}
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'eanNo', $eanNo));
						if(strlen($bac) > 255) {
							$failed->addError(" bac length is greater than 255 characters");
							$isvalidate = false;
						}
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'bac', $bac));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'validFrom', $validFrom));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'validTo', $validTo));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'expiry', 'false'));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'adr', $adr));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'active', 'true'));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'lot', $lot));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'sortOrder', $sortOrder));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'minStock', $minStock));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'maxStock', $maxStock));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'retailPrice', $product->get_regular_price()));
						if(strlen($product->get_sale_price()) == 0) {
							$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'purchasePrice', $product->get_regular_price()));
						} else {
							$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'purchasePrice', $product->get_sale_price()));
						}
						if(strlen($productType) > 255) {
							$failed->addError(" Product Type length is greater than 255 characters");
							$isvalidate = false;
							
						}
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'productType', $productType));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'defaultMasterProduct', 'false'));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'hangingStorage', 'false'));
						$back = $product->get_backorders();
						if(strcmp($back, "no") != 0) {
							$back = 'true';
						} else {
							$back = 'false';
						}
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'backOrder', $back));
						$node->appendChild($xml->createElementNS("http://www.toppak.nl/item",'enriched', 'true'));
						if($isvalidate == true) {
							$products->appendChild($node);
							add_post_meta($product_id, 'ecsExport', 'yes');
							$isEmpty = $isEmpty + 1;
						} else {
							array_push($FailedOrders, $failed);
						}
					}
					
					$result = count($Products);
					if($isEmpty > 0) { //Export products
						$t = time();
						$filename = 'PRD' . date("YmdHis", $t) . '.xml';
						
						$message->appendChild($products);
						$xml->appendChild($message);

						//Remove Empty fields:
						$xpath = new DOMXPath($xml);
						
						foreach( $xpath->query('//*[not(node())]') as $node ) {
							$node->parentNode->removeChild($node);
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
								$failed = new FailedOrder();
								$failed->set_orderID('');
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
								$wpdb->insert($table_name_ecs, array(
									'type' => $filename,
									'enable' => 'true',
									'keytext' => 'lastproductname'
								));
							}

						}

						
					} 
				}
	
				if(count($FailedOrders) > 0) {
					$t = time();
					$filename = 'PRD' . date("YmdHis", $t) . '.xml';
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
							'</p></body>
						</html>';
						
					global $wpdb;
					$name = '';
					$email = '';
					
						
					// find list of states in DB
					$table_name_ecs = $wpdb->prefix . 'ecs';
					$qry = "SELECT * FROM ".$table_name_ecs." WHERE keytext ='general' ORDER BY id DESC  LIMIT 1";
					$states = $wpdb->get_results($qry);
					$settingID = '';
					foreach($states as $k) {
						$settingID = $k->id;
					}
					$table_name = $wpdb->prefix . 'ecsmeta';
					// find list of states in DB
					$qrymeta = "SELECT * FROM ".$table_name." WHERE settingid = '".$settingID."'";
					$statesmeta = $wpdb->get_results($qrymeta);
					
					foreach($statesmeta as $k) {
						if($k->keytext == "Name") {
							$name = $k->value;
						}
						if($k->keytext == "Email") {
							$email = $k->value;

						}
					}
					
						
					$to = $email;
					$subject = 'PostNL ECS plugin processing error';
					$body = $Errors;
					$headers = array(
						'Content-Type: text/html; charset=UTF-8'
					);
					
					
					wp_mail($to, $subject, $body, $headers);

					
				}
			
				
				
				} else {
					//Failed FTP Check
					error_log('ERROR: POSTNL ECS Product Export: '. $ftpCheck[1]); 
				}
			
				

				
		}  
						

		function woocommerce_version_check($version = '2.1') {
		
			if(function_exists('is_woocommerce_active') && is_woocommerce_active()) {
				global $woocommerce;
				if(version_compare($woocommerce->version, $version, '>=' )) {
				return true;
				}
			
			}
			return false;


		}
  		
		function orderExport() {
		
			
			require_once(dirname(__DIR__) . "/ecsSftpProcess.php");
			require_once(dirname(__DIR__) . "/export/EcsOrderSettings.php");
			$EcsSftpSettings = ecsSftpProcess::init();
			$EcsOrderSettings = ecsOrderSettings::init(); 
			$Path = '';
			$settingID = $EcsOrderSettings->getSettingId();
			if($settingID) {
				$statesmeta = $EcsOrderSettings->loadOrderSettings($settingID); }
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
				
			} 
			$ftpCheck = $EcsSftpSettings->checkSftpSettings($Path);
			
			if($ftpCheck[0] != 'SUCCESS') {
			
				error_log('ERROR: POSTNL ECS Product Export: '. $ftpCheck[1]); 
			
			}
			else {
			
				$sftp = $ftpCheck[1];
				$order = new WC_Order();
				global $wpdb;
				
				
				foreach($statesmeta as $k) {
					if($k->keytext == "Cron") {
						$Cron = $k->value;
					}
					if($k->keytext == "Path") {
						$Path = $k->value;
					}
					if($k->keytext == "Shipping") {
						$Shipping = $k->value;
					}
					if($k->keytext == "Status") {
						$Status = $k->value;
					}
				}
				
				$StartPath = $sftp->pwd();
				$sftp->chdir($Path);
				$endPath = $sftp->pwd();
				$date = date_create($order->get_date_created());
				
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
				
				global $wpdb;
				$orderStatus = '';
				$shipment = '';
				$no = '';
				
			
				// find list of states in DB
				$table_name_ecs = $wpdb->prefix . 'ecs';
				$qry = "SELECT * FROM ".$table_name_ecs." WHERE keytext ='OrderExport' ORDER BY id DESC LIMIT 1";
				$states = $wpdb->get_results($qry);
				$settingID = '';
				
				foreach($states as $k) {
					$settingID = $k->id;
				}
				
				$table_name = $wpdb->prefix . 'ecsmeta';
				// find list of states in DB
				$qrymeta = "SELECT * FROM ".$table_name." WHERE settingid = '".$settingID."'";
				$statesmeta = $wpdb->get_results($qrymeta);
				
				foreach($statesmeta as $k) {
					if($k->keytext == "Status") {
						$orderStatus = $k->value;
					}
					if($k->keytext == "Shipping") {
						$shipment = $k->value;
					}
					if($k->keytext == "no") {
						$no = $k->value;
					}
				}
				
				$ordersW = '';
				$orderStatusArray = explode(":", $orderStatus);
				$orderStatusArray2 = array();
				
				foreach($orderStatusArray as $orderss) {
					array_push($orderStatusArray2, 'wc-' . $orderss);
				}
				
				$ordersW = get_posts(array(
					'post_type' => 'shop_order',
					'post_status' => $orderStatusArray2,
					'posts_per_page' => 100,
					'meta_query' => array(
						array(
							'key' => 'ecsExport',
							'compare' => 'NOT EXISTS'
						)
					)
				));
				
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
							$failed = new FailedOrder();
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
							
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderNo', $order->get_id()));
							
							
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','webOrderNo', $order->get_id()));
							$t = time();
							
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderDate', $order->get_date_created()->format("Y-m-d")));
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','orderTime', $order->get_date_created()->format("H:i:s")));
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','customerNo', ''));
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','onlyHomeAddress', 'false'));
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','vendorNo', ''));
	
							
						//  shipping
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToTitle', ''));
							
							if(strlen($order->get_shipping_last_name()) == 0) {
								$failed->addError(" shipToLastName length is null");
								
								$isvalidate = false;
							} else {
								if(strlen($order->get_shipping_last_name()) > 35) {
									$failed->addError(" shipping_last_name length is greater than 35 characters");
									
									$isvalidate = false;
								}
							}


						

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
							$shippingCodeArrayskip = ['04944','04945', 'NA'];
							
							if($shippingCodePostNL && !in_array($shippingCodePostNL, $shippingCodeArrayskip)) {
								$shippingOptions = $order->get_meta('_postnl_delivery_options');
								if($shippingCodePostNL === 'PGE' || $shippingCodePostNL === '03533') {
									
									if(isset($shippingOptions['postal_code']) && isset($shippingOptions['street'])  && 	isset($shippingOptions['number'])  && isset($shippingOptions['city']) ) {
										$orderShipPostCode = $shippingOptions['postal_code']; //Set for PGE
										$orderShipPostcity = $shippingOptions['city']; //Set Set for PGE
										$orderShipPostcountry = isset($shippingOptions['cc']) ? $shippingOptions['cc'] : $orderShipPostcountry ; //Set for PGE
										$orderShipPoststreet =  $shippingOptions['street']; //Set for PGE
										$orderShipPoststreetNum = $shippingOptions['number']; //Set for PGE
										$orderShipPostcompany = isset($shippingOptions['location']) ? $shippingOptions['location'] : $orderShipPostcompany;
									}
									

								} else {
									

									if(isset($shippingOptions['date'])){
										$postNLdeliveryDate = strtotime($shippingOptions['date']);
										if($postNLdeliveryDate > strtotime('tomorrow'))
											$orderShipPostDeliveryDate = date('Y-m-d',$postNLdeliveryDate);
										
									}

									if($orderShipPostDeliveryDate && isset($shippingOptions['time'])) {
										foreach($shippingOptions['time'] as $timeOption) {
											if(isset($timeOption['start'])) {
												$orderShipPostDeliveryTime  = date('H:i', strtotime($timeOption['start']));
	
											}
	
	
										}
	
	
									}

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
							
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToCountryCode', $orderShipPostcountry));
							if(strlen($order->get_shipping_country()) == 0) {
								$failed->addError(" shipToCountry length is null");
								$isvalidate = false;
							}
			

							if($order->get_shipping_country()) 
								$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToCountry', WC()->countries->countries[$order->get_shipping_country()]));
							else 
								$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToCountry', ''));

							if(strlen($order->get_billing_phone()) == 0) {
								$failed->addError(" shipToPhone length is null");
								$isvalidate = false;
							} else {
								if(strlen($order->get_billing_phone()) > 15) {
									$failed->addError(" shipping_phone length is greater than 15 characters");
									$isvalidate = false;
								}
							}
							
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','shipToPhone', $order->get_billing_phone()));

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

							if(strlen($order->get_billing_phone()) > 15 ) {
								$failed->addError(" billing_phone length is greater than 15 characters");
								$isvalidate = false;
							}
							if(strlen($order->get_billing_phone()) == 0 ) {
								$failed->addError(" billing_phone length not found");
								$isvalidate = false;
							}
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','invoiceToPhone', $order->get_billing_phone()));
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
							$woo_countries = new WC_Countries();
							$states = $woo_countries->get_states($order->get_shipping_country());
							$region =  $order->get_shipping_city();
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
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','language', $newstring = substr(get_locale(), -2,2)));
							$node->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','remboursAmount', ''));
							$order_shipping_method_id = '';
							$shipping_items = $order->get_items('shipping');
							
							foreach($shipping_items as $el) {
								$order_shipping_method_id = $el['method_id'];
							}
							/*if(strlen($order_shipping_method_id) == 0) {
								$failed->addError(" order_shipping_method_id length is null");
								error_log("shipping");
								$isvalidate = false;
							}*/
							
							


							if(!$shippingCodePostNL) {

								if(strtolower($order->get_shipping_country()) === 'nl')
									$order_shipping_method_id = "PNLP";
								else
									$order_shipping_method_id = get_outside_nl_shipping($order->get_shipping_country());
								
								
							}
							else { //From PostNL-WooCommerce Plugin

								$order_shipping_method_id = $shippingCodePostNL;
							}
							
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
								
								if($orderedProduct->is_virtual()) continue;
								if ($orderedProduct->is_downloadable('yes')) continue;
							
								
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
									//$failed->addError(" Product name length is greater than 255 characters");
									//$isvalidate = false;
									$orderItemName = substr($item['name'],0,255);
								} else 
									$orderItemName = $item['name'];
								
								$orderItemNameClean = str_replace($this->_getBadCharacters(), '', $orderItemName);
									
								/*$product = new WC_Product((int) $item['product_id']);
								if(strlen($product->get_sku()) == 0) {
									$failed->addError(" Product SKU  is null");
									$isvalidate = false;
								}*/
								$line = $xml->createElementNS('http://www.toppak.nl/deliveryorder_new','deliveryOrderLine');
								$line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','itemNo', $productSKU));
								$line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','itemDescription', $orderItemNameClean));
								$line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','quantity', $item['qty']));
								$line->appendChild($xml->createElementNS('http://www.toppak.nl/deliveryorder_new','singlePriceInclTax', $item['line_subtotal']));
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
						//$xml->save(ECS_DATA_PATH."/order.xml");
						//$t = time();
						//Remove Empty fields:
						$xpath = new DOMXPath($xml);
						
						foreach( $xpath->query('//*[not(node())]') as $node ) {
							$node->parentNode->removeChild($node);
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
								$failed = new FailedOrder();
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
						//$local_directory =ECS_DATA_PATH.'/order.xml';
						//$remote_directory = 'woocommerce_test/Order/';
						$remote_directory = $Path . '/';
						// $remote_directory = '/';
						$success = $sftp->put($remote_directory . $filename, $xml->saveXml());
						global $wpdb;
						$table_name_ecs = $wpdb->prefix . 'ecs';
						$querylast = "SELECT * FROM ".$table_name_ecs." WHERE keytext = 'lastOrdername'";
						$statesmeta = $wpdb->get_results($querylast);
						$lastname = '';
						if(count($statesmeta) > 0) {
							foreach($statesmeta as $k) {
								$wpdb->query($wpdb->prepare("UPDATE ".$table_name_ecs." SET type = '".$filename."' WHERE id= %d", $k->id));
							}
						} else {
							global $wpdb;
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
							'</p></body>
						</html>';
						
					global $wpdb;
					$name = '';
					$email = '';
					// find list of states in DB
					$table_name_ecs = $wpdb->prefix . 'ecs';
					$qry = "SELECT * FROM ".$table_name_ecs." WHERE keytext ='general' ORDER BY id DESC  LIMIT 1";
					$states = $wpdb->get_results($qry);
					$settingID = '';
					foreach($states as $k) {
						$settingID = $k->id;
					}
					$table_name = $wpdb->prefix . 'ecsmeta';
					// find list of states in DB
					$qrymeta = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
					$statesmeta = $wpdb->get_results($qrymeta);
					
					foreach($statesmeta as $k) {
						if($k->keytext == "Name") {
							$name = $k->value;
						}
						if($k->keytext == "Email") {
							$email = $k->value;
						}
					}
					
					$to = $email;
					$subject = 'PostNL ECS plugin processing error';
					$body = $Errors;
					$headers = array(
						'Content-Type: text/html; charset=UTF-8'
					);
					if(count($FailedOrders) > 0) {
						wp_mail($to, $subject, $body, $headers);
					}
				} 	
			}
				
		}
		
		function shipmentImport() {
				require_once(dirname(__DIR__) . "/ecsSftpProcess.php");
				require_once(dirname(__DIR__) . "/import/ecsShipmentSettings.php");
				$EcsSftpSettings = ecsSftpProcess::init();
				$EcsShipmentSettings = ecsShipmentSettings::init();
				$Path = '';
				$settingID = $EcsShipmentSettings->getSettingId();
				if($settingID) {
					$statesmeta = $EcsShipmentSettings->loadShipmentSettings($settingID); }
				else { 
				error_log('Shipment Settings not found'); 
				return;
				}
				
				foreach ($statesmeta as $k) {
				
				if ($k->keytext == "Path") {
					$Path = $k->value;
				}
				
				
			}
			$ftpCheck = $EcsSftpSettings->checkSftpSettings($Path);
			
			if($ftpCheck[0] != 'SUCCESS') {
			
			//error_log('ERROR: POSTNL ECS Product Export: '. $ftpCheck[1]); 
			
			}  else {
				
				$sftp = $ftpCheck[1];
				global $wpdb;
				$Cron = '';
				$Path = '';
				$Inform = '';
				$tracking = '';
				$enable = '';
				$lastfile = '';
				$table_name_ecs = $wpdb->prefix . 'ecs';
							
				foreach($statesmeta as $k) {
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
				
				$remote_directory = $Path . '/';
				$StartPath = $sftp->pwd();
				$sftp->chdir($Path); // open directory 'test'
				$endPath = $sftp->pwd();
				
				foreach($sftp->nlist() as $filename) {
					$codesNames = explode(".xml", $filename);
		
					if(count($codesNames) > 0) {
						if($filename == '.' || $filename == '..') {
							
						} else {
							
							$sftp->get($sftp->pwd() . '/' . $filename,ECS_DATA_PATH."/".$filename);							
							if(file_exists(ECS_DATA_PATH."/".$filename) && filesize(ECS_DATA_PATH."/".$filename) > 0) {
								$xml = simplexml_load_file(ECS_DATA_PATH."/".$filename, 'SimpleXMLElement', LIBXML_NOWARNING);
								 
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
									$intOrder = (int) $orderid;
									if(false === get_post_status((int) $stock->orderNo)) {
										$validate = false; 
										array_push($inventory_errors, 'Order  ID :' . $stock->orderNo . '  is not found for the shipment');
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
											$order = new WC_Order((int) $orderid);
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
										$intOrder = (int) $orderid;
										$stringTrack = (string) $traclCode;
							
										///check if everything is shipped
										$order = new WC_Order((int) $orderid);
										$items = $order->get_items('line_item');
										$ordExportedItems = 0;
										$countElement = 0;
										
										
											
										foreach ($items as $orderlineItem) {
											$lineItemProduct = $orderlineItem->get_product();
											
											if($lineItemProduct->is_virtual()) continue;
											if ($lineItemProduct->is_downloadable('yes')) continue;
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
											
												$order = wc_get_order((int) $orderid);
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
													error_log($fails);
													$Errors .= $fails;
													$Errors .= ' <br>';
												}
											'</p></body>
										</html>';
										
									global $wpdb;
									$name = '';
									$email = '';
									// find list of states in DB
									$table_name_ecs = $wpdb->prefix . 'ecs';
									$qry = "SELECT * FROM ".$table_name_ecs." WHERE keytext ='general' ORDER BY id DESC  LIMIT 1";
									$states = $wpdb->get_results($qry);
									$settingID = '';
									
									foreach($states as $k) {
										$settingID = $k->id;
									}
									
									$table_name = $wpdb->prefix . 'ecsmeta';
									// find list of states in DB
									$qrymeta    = "SELECT * FROM ".$table_name." WHERE settingid = '".$settingID."'";
									$statesmeta = $wpdb->get_results($qrymeta);
									
									foreach($statesmeta as $k) {
										if($k->keytext == "Name") {
											$name = $k->value;
										}
										if($k->keytext == "Email") {
											$email = $k->value;
										}
									}
									
									$to = $email;
									$subject = 'PostNL ECS plugin processing error';
									$body = $Errors;
									$headers = array(
										'Content-Type: text/html; charset=UTF-8'
									);
									wp_mail($to, $subject, $body, $headers);
								}
							if(file_exists(ECS_DATA_PATH."/".$filename))
								unlink(ECS_DATA_PATH."/".$filename);
							}
						}
					}
				}
			} 
		}
		
		function inventoryImport() {
				require_once(dirname(__DIR__) . "/ecsSftpProcess.php");
				require_once(dirname(__DIR__) . "/import/ecsInventorySettings.php");
				$EcsSftpSettings = ecsSftpProcess::init();
				$EcsInventorySettings = ecsInventorySettings::init();
				$Path = '';
				$settingID = $EcsInventorySettings->getSettingId();
				if($settingID) { 	
					$statesmeta = $EcsInventorySettings->loadInventorySettings($settingID);
		
					}

			else { 
				error_log('Stock Settings not found'); 
				return;
				}
				
				foreach ($statesmeta as $k) {
				
				if ($k->keytext == "Path") {
					$Path = $k->value;
				}
				
				
			}
			$ftpCheck = $EcsSftpSettings->checkSftpSettings($Path);
			
			if($ftpCheck[0] != 'SUCCESS') {
			
			error_log('ERROR: POSTNL ECS Product Export: '. $ftpCheck[1]); 
			
			} else {
				global $wpdb;
				$sftp =  $ftpCheck[1];
				$Cron = '';
				$Path = '';
				$informcustomer = '';
				$cron = '';
				$enable = '';
				$lastfile = '';
				// find list of states in DB
				$table_name_ecs = $wpdb->prefix . 'ecs';
				
				foreach($statesmeta as $k) {
					if($k->keytext == "Cron") {
						$Cron = $k->value;
					}
					if($k->keytext == "Path") {
						$Path = $k->value;
						
					}
				}
				
				global $wpdb;
				$nameRetailer = '';
				$email = '';
				$table_name_ecs = $wpdb->prefix . 'ecs';
				// find list of states in DB
				$qry = "SELECT * FROM ".$table_name_ecs." WHERE keytext ='inventoryImport' ORDER BY id DESC  LIMIT 1";
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
				
				
				
				
				
				$remote_directory = $Path . '/';
				$StartPath = $sftp->pwd();
				$sftp->chdir($Path); // open directory 'test'
				$endPath = $sftp->pwd();
				// $sftp->chdir('woocommerce_test/Stockcount');
				
		
				
				foreach($sftp->nlist() as $filename) {
					
					$codesNames = explode(".xml", $filename);
					if(count($codesNames) >0) {
						if($filename == '.' || $filename == '..') {
							
						} 
						
						else { 
							
							$sftp->get($sftp->pwd() . '/' . $filename, ECS_DATA_PATH."/".$filename);
							if (file_exists(ECS_DATA_PATH."/".$filename) && filesize(ECS_DATA_PATH."/".$filename) > 0) {
								$xml = simplexml_load_file(ECS_DATA_PATH."/".$filename, 'SimpleXMLElement', LIBXML_NOWARNING);
								$valid = true;
								$inventory_errors = array();
								
						
								foreach($xml->Stockupdate as $stock) {
									
									$prodduct_id = (string) $stock->stockdtl_itemnum;
									
									$Products = get_posts(array(
										'post_type' => array('product','product_variation'),
										'posts_per_page' => 100,
										'meta_query' => array(
											array(
												'key' => '_sku',
												'value' => (string) $stock->stockdtl_itemnum,
												'compare' => '='
											)
										)
									)); 
									if (count($Products) == 0) {
										
										$valid = false; 
										array_push($inventory_errors, "Product  SKU :" . $stock->stockdtl_itemnum . " is not found");
									} else {
										if($valid == true) {
											foreach($Products as $product) {
												$product_id = $product->ID;
											
												update_post_meta((int) $product_id, '_stock', (int) $stock->stockdtl_fysstock);
											}
										}
										
										
									}
								} 
								
	
								if(count($inventory_errors) > 0) {
										$Errors = '
											<!DOCTYPE html>
											<html>
												<body><p>';
													$Errors .= 'An error occurred processing file:' . $filename;
													$Errors .= '<br><b>Message:</b><br>';
													foreach($inventory_errors as $fails) {
														//error_log($fails);
														$Errors .= $fails;
														$Errors .= '<br>';
													}
												'</p></body>
											</html>';
											
										global $wpdb;
										$name = '';
										$email = '';
										// find list of states in DB
										$table_name_ecs = $wpdb->prefix . 'ecs';
										$qry = "SELECT * FROM ".$table_name_ecs." WHERE keytext ='inventoryImport' ORDER BY id DESC LIMIT 1";
										$states = $wpdb->get_results($qry);
										$settingID = '';
										foreach($states as $k) {
											$settingID = $k->id;
										}
										$table_name = $wpdb->prefix . 'ecsmeta';
										// find list of states in DB
										$qrymeta = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
										$statesmeta = $wpdb->get_results($qrymeta);
										foreach($statesmeta as $k) {
											if($k->keytext == "Name") {
												$name = $k->value;
											}
											if($k->keytext == "Email") {
												$email = $k->value;
											}
										}
										$to = $email;
										$subject = 'PostNL ECS plugin processing error';
										$body = $Errors;
										$headers = array(
											'Content-Type: text/html; charset=UTF-8'
										);
										wp_mail($to, $subject, $body, $headers);
									}
									else {
											$sftp->delete($sftp->pwd() . '/' . $filename);
											
									}
									
								if(file_exists(ECS_DATA_PATH."/".$filename))
										unlink(ECS_DATA_PATH."/".$filename);
							}
						}
					}
				}
			} 
		}
	}

?>