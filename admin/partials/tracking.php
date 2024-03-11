<?php
//Silence

function get_postnl_ecs_tracking_url($order)
{
    global $wpdb;

    $table_name_ecs = $wpdb->prefix . "ecs";
    // find list of states in DB

    $qry = "SELECT * FROM   $table_name_ecs " ."WHERE keytext ='shipmentImport' ORDER BY id DESC LIMIT 1 ";
    $states = $wpdb->get_results($qry);
    $settingID = "";

    foreach ($states as $k) {
        $settingID = $k->id;
    }

    $table_name = $wpdb->prefix . "ecsmeta";
    // find list of states in DB
    $qrymeta = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
    $statesmeta = $wpdb->get_results($qrymeta);
    $tracking = "";
    $Inform = "";

    foreach ($statesmeta as $k) {
        if ($k->keytext == "tracking") {
            $tracking = $k->value;
        }
        if ($k->keytext == "Inform") {
            $Inform = $k->value;
        }
    }

    $trackcode = get_post_meta($order->get_id(), "trackAndTraceCode", true);
    $trackcode  = !empty($trackcode) ? $trackcode : $order->get_meta('trackAndTraceCode');


    if (empty($trackcode)) {
        return [
            "trackingUrl" => $tracking,
            "inform" => $Inform,
            "trackingCode" => false,
        ];
    }

    //Create tracking URLs
    $tntUrls = [];
    if ($tracking === "") {
        $tracking = "https://jouw.postnl.nl/#!/track-en-trace/";
    }

    $tracking = rtrim($tracking, "/") . "/";

    $trackcode = str_replace(",", ";", $trackcode);

    $codes = explode(";", $trackcode);

    //echo '<h3><strong>' . __('Track & Trace code') . ':</strong> </h3>';
    $postCode = str_replace(" ", "", $order->get_shipping_postcode());
    $orderShipPostcountry = $order->get_shipping_country(); //Set from WC
    $pgCodeArray = ["03533", "PGE"];

    $shippingCodePostNL = getPostNLEcsShippingCode(
        $order->get_shipping_country(),
        $order
    );

    if ($shippingCodePostNL) {
        if (in_array($shippingCodePostNL, $pgCodeArray)) {
            $postCode = str_replace(" ", "", $order->get_billing_postcode()); //Set for PGE
        }
    }

    foreach ($codes as $code) {
        //Remove extra spaces
        $code = trim($code);

        $codeUrl =
            $tracking . $code . "/" . $orderShipPostcountry . "/" . $postCode;

        $tntUrls[] =
            '<a target="_blank" href="' . $codeUrl . '" >' . $code . "</a><br>";
    }

    return [
        "trackingUrl" => $tracking,
        "inform" => $Inform,
        "trackingCode" => $tntUrls,
    ];
}

//Add Tracking URL in order
add_action(
    "woocommerce_email_order_meta",
    "postnlecs_woo_add_order_notes_to_email",
    10,
    3
);

function postnlecs_woo_add_order_notes_to_email(
    $order,
    $sent_to_admin,
    $plain_text
) {
    global $woocommerce, $post;

    $getTracking = get_postnl_ecs_tracking_url($order);

    if ($getTracking["trackingCode"]) {
        if ($getTracking["inform"]) {
            if (is_array($getTracking["trackingCode"])) {
                echo "<h3><strong>" .
                    __("Track & Trace code") .
                    ":</strong> </h3>";

                foreach ($getTracking["trackingCode"] as $trackingUrl) {
                    echo $trackingUrl;
                }
            }
        }
    }
}