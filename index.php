<?php
	/* 
	Plugin Name: PostNL-ECS
	Plugin URI: http://www.postnl.nl/
	Description: PostNL ECS Fulfilment Plugin
	Version: 1.9.2.4
	Author: PostNL 
	Author URI: http://www.postnl.nl/
	*/

	/**
	 * Display field value on the order edit page
	*/
	 
	set_include_path(__DIR__ . '/phpseclib');
	require_once(__DIR__ . '/phpseclib/Net/SSH2.php');
	require_once(__DIR__ . '/phpseclib/Crypt/RSA.php');
	require_once(__DIR__ . '/phpseclib/Net/SFTP.php');
	define('ECS_PATH', dirname(__FILE__));
	define('ECS_DATA_PATH', ECS_PATH.'/data');
	// ADDING COLUMN TITLES (Here 2 columns)
	add_filter('manage_edit-shop_order_columns', 'custom_shop_order_column', 11);
	
	function custom_shop_order_column($columns) {
		$columns['my-column1'] = __('Export Status', 'theme_slug');
		return $columns;
	}
	
	// adding the data for each orders by column (example)
	add_action('manage_shop_order_posts_custom_column', 'add_exportedColumn', 10, 2);
	
	function add_exportedColumn($column) {
		
		global $post, $woocommerce, $the_order;
		$order_id = $the_order->get_id();
		switch ($column) {
			case 'my-column1':
				$isExported = get_post_meta($order_id, 'ecsExport', true);
				if (strlen($isExported) !== 0) {
					echo ' EXPORTED ';
				} else {
					echo ' NOT EXPORTED';
				}
				break;
		}
	}
	
	///MODIFY WHEN UPDATED 
	function reset_export($post_id) {
		$posttype = get_post_type($post_id);
		if ($posttype == "product" || $posttype == "product_variation") {
			$product = wc_get_product($post_id);
			if ($product->is_type('variable')) {
				$productVaries = $product->get_children();
				foreach ($productVaries as $variation_id) {
					delete_post_meta($variation_id, 'ecsExport', 'yes');
						
				}
			}
			else
				delete_post_meta($post_id, 'ecsExport', 'yes');
		}
	}
	
	add_action('save_post', 'reset_export');
	add_action('woocommerce_admin_order_data_after_shipping_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1);
	
	function my_custom_checkout_field_display_admin_order_meta($order) {
		$trackcode  = get_post_meta($order->get_id(), 'trackAndTraceCode', true);
		$isExported = get_post_meta($order->get_id(), 'ecsExport', true);
		
		if (strlen($isExported) !== 0) {
			echo '<h3><strong>' . __('Export  Status') . ':</strong> </h3> <p> Exported </p>';
		} else {
			echo '<h3><strong>' . __('Export  Status') . ':</strong> </h3>  <p> Not Exported </p>';
		}
		
		if(!empty($trackcode)) {

			$getTracking = get_postnl_ecs_tracking_url($order);

			if($getTracking['trackingCode']) {
				echo '<h3><strong>' . __('Track & Trace code') . ':</strong> </h3>';
				
				if(is_array($getTracking['trackingCode'])) {
					foreach($getTracking['trackingCode'] as $trackingUrl) {
						echo $trackingUrl;
					}
				}
				
				
			}

		}
	
	}

	add_action('woocommerce_email_order_meta', 'woo_add_order_notes_to_email',10,3);
	
	function woo_add_order_notes_to_email($order, $sent_to_admin, $plain_text) {
		global $woocommerce, $post;
		
		$getTracking = get_postnl_ecs_tracking_url($order);

		if($getTracking['trackingCode']) {

			if($getTracking['inform']){

				if(is_array($getTracking['trackingCode'])) {
					echo '<h3><strong>' . __('Track & Trace code') . ':</strong> </h3>';
					
					foreach($getTracking['trackingCode'] as $trackingUrl) {
						echo $trackingUrl;
					}

				}

			}


		}
		
		
		
		
		
	}

	
	
	function my_cron_schedules($schedules) {
		if (!isset($schedules["15min"])) {
			$schedules["15min"] = array(
				'interval' => 900,
				'display' => __('Once every 15 minutes')
			);
		}
    
		if (!isset($schedules["5min"])) {
			$schedules["5min"] = array(
				'interval' => 300,
				'display' => __('Once every 5 minutes')
			);
		}
		
		if (!isset($schedules["30min"])) {
			$schedules["30min"] = array(
				'interval' => 1800,
				'display' => __('Once every 30 minutes')
			);
		}
		
		if (!isset($schedules["1hour"])) {
			$schedules["1hour"] = array(
				'interval' => 3600,
				'display' => __('Once every 1hour')
			);
		}
		
		if (!isset($schedules["2hour"])) {
			$schedules["2hour"] = array(
				'interval' => 7200,
				'display' => __('Once every 2hour')
			);
		}
		
		if (!isset($schedules["4hour"])) {
			$schedules["4hour"] = array(
				'interval' => 14400,
				'display' => __('Once every 4hour')
			);
		}
		
		if (!isset($schedules["1day"])) {
			$schedules["1day"] = array(
				'interval' => 86400,
				'display' => __('Once every 1day')
			);
		}
		return $schedules;
	}
	
	add_filter('cron_schedules', 'my_cron_schedules');
	
	global $jal_db_version;
	$jal_db_version = '1.9.2.4';
	function jal_install() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ecs';
		$charset_collate = $wpdb->get_charset_collate();
		if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name  (id mediumint(9) NOT NULL AUTO_INCREMENT, type text NOT NULL, enable BOOLEAN NOT NULL, keytext text NOT NULL, UNIQUE KEY id (id)) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}
	
	function jal_installMeta() {
		global $wpdb;
		global $jal_db_version;
		add_option('jal_db_version', $jal_db_version);
		$table_name = $wpdb->prefix . 'ecsmeta';
		$charset_collate = $wpdb->get_charset_collate();
		if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE  $table_name (id mediumint(9) NOT NULL AUTO_INCREMENT, settingid mediumint(9) NOT NULL, keytext text NOT NULL, value text NOT NULL, UNIQUE KEY  (id)) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		}
		
	}
	
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	
	function Addpage() {
		global $woocommerce;
		if ($woocommerce->version == '') {
			errorPage();
		} else {
			adminUI();
		}
		
		global $wpdb;
		$table_name_ecs = $wpdb->prefix . 'ecs';
		$qrymeta = "SELECT * FROM $table_name_ecs ";
		$statesmeta = $wpdb->get_results($qrymeta);
		$is_created = false;
		
		foreach ($statesmeta as $k) {
			if ($k->keytext == "LastOrderID") {
				$is_created = true;
			}
		}
		
		if ($is_created == false) {
			$wpdb->insert($table_name_ecs, array(
				'type' => '0',
				'enable' => 'true',
				'keytext' => 'LastOrderID' // ... and so on
			));
			$wpdb->insert($table_name_ecs, array(
				'type' => '0',
				'enable' => 'true',
				'keytext' => 'LastproductID' // ... and so on
			));
		}
	}
	
	function Addaction() {
		global $pw_settings_page;
		$pw_settings_page = 	add_options_page("ECS", "PostNL", "manage_options", "ECS", "Addpage");
	}
	
	add_action('admin_menu', 'Addaction');
	register_activation_hook(__FILE__, 'jal_installMeta');
	register_activation_hook(__FILE__, 'jal_install');
	add_action('task_order_export', 'cron_order_export');
	add_action('task_product_export', 'cron_product_export');
	add_action('task_shipement_import', 'cron_shipment_import');
	add_action('task_inventory_import', 'cron_inventory_import');
	
	function cron_order_export() {
		try{ 
		orderfunction12();
		$obj = new ni_order_list();
		$obj->orderExport();
		}
		catch(Exception $e){
		
		}
		
	}

	function cron_product_export() {
		orderfunction12();
		$obj = new ni_order_list();
		$obj->productExport();
	}
	
	function cron_shipment_import() {
		orderfunction12();
		$obj = new ni_order_list();
		$obj->shipmentImport();
	}
	
	function cron_inventory_import() {
		orderfunction12();
		$obj = new ni_order_list();
		$obj->inventoryImport();
	}
	
	function stop_cron_order() {
		wp_clear_scheduled_hook('task_order_export');
	}
	
	function stop_cron_product() {
		wp_clear_scheduled_hook('task_product_export');
	}
	
	function stop_cron_inventory() {
		wp_clear_scheduled_hook('task_inventory_import');
	}
	
	function stop_cron_shipment() {
		wp_clear_scheduled_hook('task_shipment_import');
	}

	function errorPage() {
		require_once("admin/error.php");
	}
	function my_admin_scripts($hook) {
		global $pw_settings_page;
		//wp_die($hook);
		if ($hook == 'settings_page_ECS') {
		echo '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>';
		echo '<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>';
			
			}
	}
	
	add_action( 'admin_enqueue_scripts', 'my_admin_scripts' );
	function adminUI() {
		wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
		
		echo "<style> .space { margin-right:20px; } </style>";
		require_once("admin/general-config.php");
		require_once("admin/sftp-config.php");
		require_once("admin/export/product-export.php");
		require_once("admin/export/order-export.php");
		require_once("admin/import/shipment-import.php");
		require_once("admin/import/inventory-import.php");
	
	}
	
	function orderfunction12() {
		require_once("admin/controllers/functions.php");
		require_once("admin/controllers/orderShippingcode.php");
		/* $obj = new ni_order_list();
		$obj->orderExport();
		$obj->productExport();*/
	}
	
	
 
	function ece_update($post_id, $post, $update) {

				 $post_type = get_post_type($post_id);
				 $post_status = get_post_status($post_id);

					if ( $post_type !="product") return;
					
					
					if($post_status != "publish") return ;
				  
				    if ( isset( $_POST['ecsExport'] ) ) {
						update_post_meta( $post_id, 'ecsExport', 10,3 );
					}
				  
		}
				  
				  
	
	add_action( 'save_post', 'ece_update', 10, 3  );

	function get_postnl_ecs_tracking_url($order) {
		
		global $wpdb;
		require_once("admin/controllers/orderShippingcode.php");
		$table_name_ecs = $wpdb->prefix . 'ecs';
		// find list of states in DB
		
		$qry = "SELECT * FROM   $table_name_ecs " . "WHERE keytext ='shipmentImport' ORDER BY id DESC  LIMIT 1 ";
		$states = $wpdb->get_results($qry);
		$settingID = '';
			
		foreach ($states as $k) {
			$settingID = $k->id;
		}
			
		$table_name = $wpdb->prefix . 'ecsmeta';
		// find list of states in DB
		$qrymeta = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
		$statesmeta = $wpdb->get_results($qrymeta);
		$tracking = '';
		$Inform = '';
			
		foreach ($statesmeta as $k) {
			if ($k->keytext == "tracking") {
				$tracking = $k->value;
			}
			if ($k->keytext == "Inform") {
				$Inform = $k->value;
			}
		}

		$trackcode = get_post_meta($order->get_order_number(), 'trackAndTraceCode', true);

		if( empty( $trackcode ) ) {

			return [
				'trackingUrl' => $tracking,
				'inform' => $Inform,
				'trackingCode' => false
			];

		}

		//Create tracking URLs
		$tntUrls = [];
		if ($tracking === '')
			$tracking = 'https://jouw.postnl.nl/#!/track-en-trace/';

		$tracking = rtrim($tracking,"/").'/';
		
		$trackcode = str_replace(',',';',$trackcode);
		
		$codes = explode(";", $trackcode);
		

		echo '<h3><strong>' . __('Track & Trace code') . ':</strong> </h3>';
		$postCode = str_replace(' ','',$order->get_shipping_postcode());
		$orderShipPostcountry = $order->get_shipping_country(); //Set from WC
		$pgCodeArray = ['03533', 'PGE'];

		
		$shippingCodePostNL = getPostNLEcsShippingCode($order->get_shipping_country(), $order);
		
		if($shippingCodePostNL){
			
			if(in_array($shippingCodePostNL, $pgCodeArray)) {
			
					$postCode = str_replace(' ','',$order->get_billing_postcode()); //Set for PGE
					
				
			}

		}

		foreach ($codes as $code) {
			//Remove extra spaces
			$code = trim($code);
			
			

			$codeUrl = $tracking . $code . '/'.$orderShipPostcountry.'/' . $postCode;
			

			$tntUrls[] =  '<a target="_blank" href="' . $codeUrl . '" >' . $code . '</a><br>';
			
		}

	
		return [
			'trackingUrl' => $tracking,
			'inform' => $Inform,
			'trackingCode' => $tntUrls
		];

	}
	
?>