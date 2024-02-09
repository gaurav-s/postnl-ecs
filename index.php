<?php
	/*
	Plugin Name: WooCommerce PostNL-Fulfilment
	Plugin URI: http://www.postnl.nl/
	Description: PostNL Fulfilment plugin for WooCommerce
	Version: 2.1.6
	Author: PostNL
	Author URI: http://www.postnl.nl/
	Text Domain: woocommercepostnlfulfillment
	*/

	/**
	 * Display field value on the order edit page
	*/

	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	require 'vendor/autoload.php'; // Adjust the path based on your actual setup

	define('ECS_PATH', dirname(__FILE__));
	define('ECS_DATA_PATH', ECS_PATH.'/data');

	require_once("admin/partials/admin-display.php");

	//SFTP and MASTER DATA
	require_once("admin/ecsSftpProcess.php");
	require_once("admin/export/EcsOrderSettings.php");
	require_once("admin/export/EcsProductSettings.php");
	require_once("admin/import/ecsInventorySettings.php");
	require_once("admin/import/ecsShipmentSettings.php");

	require_once("admin/partials/export-functions.php");
	require_once("admin/partials/orderShippingcode.php");
	require_once("admin/partials/tracking.php");
    require_once("admin/partials/gift-message.php");
	require_once("admin/partials/cron-tasks.php");

	//


	//Export Function

	require_once("admin/processor/Failederrors.php");
	require_once("admin/processor/PostNLProcess.php");
	require_once("admin/processor/PostNLOrder.php");
	require_once("admin/processor/PostNLProduct.php");
	require_once("admin/processor/PostNLShipment.php");
	require_once("admin/processor/PostNLStock.php");




	global $jal_db_version;
	$jal_db_version = '2.1.2';
	function postnlecs_plugin_install() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ecs';
		$charset_collate = $wpdb->get_charset_collate();
		if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name  (id mediumint(9) NOT NULL AUTO_INCREMENT, type text NOT NULL, enable BOOLEAN NOT NULL, keytext text NOT NULL, UNIQUE KEY id (id)) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}

	function postnlecs_plugin_installMeta() {

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

	register_activation_hook(__FILE__, 'postnlecs_plugin_installMeta');
	register_activation_hook(__FILE__, 'postnlecs_plugin_install');

	/*
    load_plugin_textdomain(
        'woocommercepostnlfulfillment',
        false,
        dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
    );
    */
    add_action( 'plugins_loaded', 'postnl_fulfillment_load_textdomain' );

    /**
     * Load plugin textdomain.
     */
    function postnl_fulfillment_load_textdomain() {
        load_plugin_textdomain( 'woocommercepostnlfulfillment', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }












?>