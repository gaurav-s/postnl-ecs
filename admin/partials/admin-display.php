<?php

//Silence
function postnlecs_admin_scripts($hook) {
    if (strpos($hook, 'woocommerce_page_fulfillment_ECS') !== false) {

							$dir = plugin_dir_path( __DIR__ );

        wp_enqueue_style("bootstrap", $dir."/bootstrap/css/bootstrap.min.css");
        wp_enqueue_script("jquery");
        wp_enqueue_script("bootstrap", $dir."/bootstrap/js/bootstrap.min.js", array("jquery"), false, true);
    }
}

add_action("admin_enqueue_scripts", "postnlecs_admin_scripts");

function postnlecs_errorPage()
{
    require_once ECS_PATH . "/admin/error.php";
}

function postnlecs_Addpage()
{
    global $woocommerce;
    if ($woocommerce->version == "") {
        postnlecs_errorPage();
    } else {
        postnlecs_display_adminUI();
    }

    global $wpdb;
    $table_name_ecs = $wpdb->prefix . "ecs";
    $qrymeta = "SELECT * FROM $table_name_ecs ";
    $statesmeta = $wpdb->get_results($qrymeta);
    $is_created = false;

    foreach ($statesmeta as $k) {
        if ($k->keytext == "LastOrderID") {
            $is_created = true;
        }
    }

    if ($is_created == false) {
        $wpdb->insert($table_name_ecs, [
            "type" => "0",
            "enable" => "true",
            "keytext" => "LastOrderID", // ... and so on
        ]);
        $wpdb->insert($table_name_ecs, [
            "type" => "0",
            "enable" => "true",
            "keytext" => "LastproductID", // ... and so on
        ]);
    }
}

add_action("admin_menu", "postnlecs_Addaction");
function postnlecs_Addaction()
{
    global $pw_settings_page;

    //$pw_settings_page = 	add_options_page("ECS", "PostNL Fulfillment", "manage_options", "fulfillment_ECS", "postnlecs_Addpage");
    $pw_settings_page = add_submenu_page(
        "woocommerce",
        "PostNL Fulfilment",
        "PostNL Fulfilment",
        "manage_options",
        "fulfillment_ECS",
        "postnlecs_Addpage"
    );
}

function postnlecs_display_adminUI()
{
    echo "<style> .space { margin-right:20px; } </style>";

    require_once ECS_PATH . "/admin/general-config.php";
    require_once ECS_PATH . "/admin/sftp-config.php";
    require_once ECS_PATH . "/admin/export/product-export.php";
    require_once ECS_PATH . "/admin/export/order-export.php";
    require_once ECS_PATH . "/admin/import/shipment-import.php";
    require_once ECS_PATH . "/admin/import/inventory-import.php";
}
// ADDING COLUMN TITLES (Here 2 columns)
add_filter("manage_edit-shop_order_columns", "postnlecs_shop_order_column", 11);

function postnlecs_shop_order_column($columns)
{
    $columns["postnlecs-export"] = __("Export Status", "theme_slug");
    return $columns;
}

// adding the data for each orders by column (example)
add_action(
    "manage_shop_order_posts_custom_column",
    "postnlecs_add_exportedColumn",
    10,
    2
);

function postnlecs_add_exportedColumn($column)
{
    global $the_order;
    if (!$the_order) {
        return;
    }

    $order_id = $the_order->get_id();
    switch ($column) {
        case "postnlecs-export":
            $isExported = get_post_meta($order_id, "ecsExport", true);
            if (strlen($isExported) !== 0) {
                echo " EXPORTED ";
            } else {
                echo " NOT EXPORTED";
            }
            break;
    }
}

//Custom Fields

add_action(
    "woocommerce_admin_order_data_after_shipping_address",
    "postnlecs_checkout_field_display_admin_order_meta",
    10,
    1
);

function postnlecs_checkout_field_display_admin_order_meta($order)
{
    $trackcode = get_post_meta($order->get_id(), "trackAndTraceCode", true);
    $isExported = get_post_meta($order->get_id(), "ecsExport", true);

    if (strlen($isExported) !== 0) {
        echo "<h3><strong>" .
            __("Export  Status") .
            ":</strong> </h3> <p> Exported </p>";
    } else {
        echo "<h3><strong>" .
            __("Export  Status") .
            ":</strong> </h3>  <p> Not Exported </p>";
    }

    if (!empty($trackcode)) {
        $getTracking = get_postnl_ecs_tracking_url($order);

        if ($getTracking["trackingCode"]) {
            echo "<h3><strong>" . __("Track & Trace code") . ":</strong> </h3>";

            if (is_array($getTracking["trackingCode"])) {
                foreach ($getTracking["trackingCode"] as $trackingUrl) {
                    echo $trackingUrl;
                }
            }
        }
    }
}

///MODIFY WHEN UPDATED
function postnlecs_reset_export($post_id)
{
    $posttype = get_post_type($post_id);
    if ($posttype == "product" || $posttype == "product_variation") {
        $product = wc_get_product($post_id);
        if ($product->is_type("variable")) {
            $productVaries = $product->get_children();
            foreach ($productVaries as $variation_id) {
                delete_post_meta($variation_id, "ecsExport", "yes");
            }
        } else {
            delete_post_meta($post_id, "ecsExport", "yes");
        }
    }
}

add_action("save_post", "postnlecs_reset_export");