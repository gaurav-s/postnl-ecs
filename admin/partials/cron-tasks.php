<?php 
//Silence

function postnlecs_cron_schedules($schedules) {
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

add_filter('cron_schedules', 'postnlecs_cron_schedules');

add_action('task_order_export', 'postnlecs_cron_order_export');
add_action('task_product_export', 'postnlecs_cron_product_export');
add_action('task_shipement_import', 'postnlecs_cron_shipment_import');
add_action('task_inventory_import', 'postnlecs_cron_inventory_import');

function postnlecs_cron_order_export() {
    try{ 
    
        $postnlOrder = new PostNLOrder();
        $postnlOrder->processOrders();
    }
    catch(Exception $e){
        
    }
    
}

function postnlecs_cron_product_export() {
    try{
        $postnlproduct = new PostNLProduct();
        $postnlproduct->processProducts();
    }catch(Exception $e){
        
    }
    
}

function postnlecs_cron_shipment_import() {
    try{
        $postnlshipment = new PostNLShipment();
        $postnlshipment->processShipment();
    }catch(Exception $e){
        
    }
}

function postnlecs_cron_inventory_import() {
    try{
        $postnlStock = new PostNLStock();
        $postnlStock->processStock();
    }catch(Exception $e){
        
    }
}

function postnlecs_stop_cron_order() {
    wp_clear_scheduled_hook('task_order_export');
}

function postnlecs_stop_cron_product() {
    wp_clear_scheduled_hook('task_product_export');
}

function postnlecs_stop_cron_inventory() {
    wp_clear_scheduled_hook('task_inventory_import');
}

function postnlecs_stop_cron_shipment() {
    wp_clear_scheduled_hook('task_shipment_import');
}

function postnlecs_cron_selection_display($Cron) {
    
    
    
        
    echo '
<!-- Select Basic -->
<div class="form-group">
<label class="col-md-4 control-label" for="selectbasic">Cron schedule</label>
<div class="col-md-4">
<select id="selectbasic" name="Cron" class="form-control">';
echo '<option value="15min" '. ($Cron === "15min" ? 'selected' : '' ).' >15 min</option>';
echo '<option value="30min" '. ($Cron === "30min" ? 'selected' : '' ).' >30 min</option>';
echo '<option value="1hour" '. ($Cron === "1hour" ? 'selected' : '' ).' >1 hour</option>';
echo '<option value="2hour" '. ($Cron === "2hour" ? 'selected' : '' ).' >2 hour</option>';
echo '<option value="4hour" '. ($Cron === "4hour" ? 'selected' : '' ).' >4 hour</option>';
echo '<option value="1day" '. ($Cron === "1day" ? 'selected' : '' ).' >1 daily</option>';
echo '<option value="0" '. (!$Cron  ? 'selected' : '' ).' >Stop</option>';
echo '
</select>
<span class="help-block">Pick a schedule</span>  
</div>
</div>';
    
}

