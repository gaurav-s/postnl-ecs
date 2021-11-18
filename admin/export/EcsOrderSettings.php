<?php 
class ecsOrderSettings {
   
    public static $instance;
	
	
    
	public static function init()
    {
        if ( is_null( self::$instance ) )
            self::$instance = new EcsOrderSettings();
        return self::$instance;
    }
    
    private function __construct()
    {
     
		
    }
    
    public function loadOrderSettings($settingID)
    {
    
			// find list of states in DB
			global $wpdb;
			$table_name = $wpdb->prefix . 'ecsmeta';	
			$qrymeta    = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
			$statesmeta = $wpdb->get_results($qrymeta);
			
		return 	 $statesmeta; 
	  
    }
    
	  public function getSettingId()
    {
      
		global $wpdb;
		$table_name_ecs = $wpdb->prefix . 'ecs';
		$qry            = "SELECT * FROM  	$table_name_ecs " . "WHERE keytext ='OrderExport' ORDER BY id DESC  LIMIT 1 ";
        $states         = $wpdb->get_results($qry);
        $settingID      = '';
        foreach ($states as $k) {
            $settingID = $k->id;
        }
		return 	  $settingID;
    }
	
	 public function saveSettings()
    {
			global $wpdb;
			$table_name_ecs = $wpdb->prefix . 'ecs';
			$wpdb->insert($table_name_ecs, array(
							'type' => '4',
							'enable' => 'true',
							'keytext' => 'OrderExport' // ... and so on
				));
			$id         = $wpdb->insert_id;
			return $id;
    }
	
	 public function saveSettingsValues($id,$keytext,$value)
    {
			global $wpdb;
			$table_name = $wpdb->prefix . 'ecsmeta';	
			$wpdb->insert($table_name, array(
							'settingid' => $id,
							'keytext' => $keytext,
							'value' => $value 
						));
    }
	
	public function updateSettingsValues($id,$value)
    {
			global $wpdb;
			$table_name = $wpdb->prefix . 'ecsmeta';	
			$wpdb->query($wpdb->prepare("UPDATE $table_name  SET value = '$value'
	WHERE id= %d", $id));
    }
    
	public function getSettingValues($settingID)
    {
			global $wpdb;
			$table_name = $wpdb->prefix . 'ecsmeta';	
			$qrymeta    = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
						$statesmeta = $wpdb->get_results($qrymeta);
						return $statesmeta;
    }
	
	public function getShippingTypesList()
    {
        $shippingMethods = WC()->shipping->get_shipping_methods();
        
        $ShippingTypes = [];
        foreach ( $shippingMethods as $id => $shipping_method ) {
            
        
            //if ( isset( $shipping_method->enabled ) && 'yes' === $shipping_method->enabled ) {
                //$method_title = $shipping_method->get_method_title();
                $method_title = $id;
				
				array_push( $ShippingTypes, $method_title );
			//}
		}
        
        $ShippingTypes[] = 'disabled';
      
	  return $ShippingTypes;
    }
	
	 public function getOrderStatusList()
    {
        
        $wooOrderStatus = wc_get_order_statuses();
        return $wooOrderStatus;
        
    }
    
	public function displayOrderExpSettings($Cron,$Path, $Shipping, $Status, $no  , $giftMessage = '', $giftMessageValue = '' ) {
		
		 echo '<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Path</label>  
<div class="col-md-4">
<input id="textinput" name="Path" type="text" placeholder="Path" required="true" class="form-control input-md" value=' . $Path . '>
<span class="help-block">For example /orders</span>  
</div>
</div>
';
        echo '<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Number of Orders per file</label>  
<div class="col-md-4">
<input id="textinput" name="no" type="number" placeholder="number" required="true" class="form-control input-md" value=' . $no . '>
<span class="help-block">For example 3,5,10</span>  
</div>
</div>
';


        echo    '<div class="form-group">
                <label class="col-md-4 control-label" for="giftcard_attribute">Attribute code of Gift Card Message</label>  
                    <div class="col-md-4">
                    <input id="textinput" name="giftcard_attribute" type="text" placeholder="gift_message"  class="form-control input-md" value=' . $giftMessage . '>
                    <span class="help-block">Attribute code should be used to create variation product</span>  
                </div>
            </div>
            ';

        echo    '<div class="form-group">
                <label class="col-md-4 control-label" for="giftcard_attribute_value">Attribute code value to show git card input</label>  
                    <div class="col-md-4">
                    <input id="textinput" name="giftcard_attribute_value" type="text" placeholder="Yes"  class="form-control input-md" value=' . $giftMessageValue . '>
                    <span class="help-block">If any custom value selected based on which selection would be displayed. Default is Yes</span>  
                </div>
            </div>
            ';
        postnlecs_cron_selection_display($Cron);
       
        $OrderStatus = $this->getOrderStatusList();
		
		if ($Status == '') {
            echo '<!-- Select Basic -->
				<div class="form-group">
				<label class="col-md-4 control-label" for="selectbasic">Order Status</label>
				<div class="col-md-4">
				<select id="selectbasic" name="Status[]" class="form-control"  required="true"  multiple="multiple" >
				';
				foreach($OrderStatus as $orderStatusKey => $OrderStatusValue) {
								
									echo '            <option value="'.$orderStatusKey.'">'.$OrderStatusValue.'</option>';
							
							
							}
				
				echo '
				</select>
				<span class="help-block">Only orders with selected status will be exported</span>  
				 
				</div>
				</div>
				';
        } else {
            echo '<!-- Select Basic -->
<div class="form-group">
<label class="col-md-4 control-label" for="selectbasic">Order Status</label>
<div class="col-md-4">
<select id="selectbasic" name="Status[]" class="form-control"  required="true"   multiple="multiple">';
            echo '';
			
			$selectedOrderStatus = explode(":",$Status);
			
			foreach($OrderStatus as $orderStatusKey => $OrderStatusValue) {
				if(in_array($orderStatusKey,$selectedOrderStatus)) {
					echo '            <option value="'.$orderStatusKey.'" selected="selected" >'.$OrderStatusValue.'</option>
';
					}
				else {
					echo '            <option value="'.$orderStatusKey.'">'.$OrderStatusValue.'</option>';
				}
					
			
			}
			
            echo ' </select>
<span class="help-block">Only orders with selected status will be exported </span>  
 
</div>
</div>';
        }
        
        
        $ShippingTypes = $this->getShippingTypesList();
        if ($Shipping == '') {
            echo '<!-- Select Basic -->
			<div class="form-group">
			<label class="col-md-4 control-label" for="selectbasic"> Shipping Method </label>
			<div class="col-md-4">
			<select id="selectbasic" name="Shipping[]" class="form-control" required="true" multiple="multiple">';
			
			foreach($ShippingTypes as $ShippingTypesValue) {
											
												echo '            <option value="'.$ShippingTypesValue.'">'.$ShippingTypesValue.'</option>';
										
										
										}
				echo '
					</select>
					<span class="help-block">Only orders with selected status will be exported</span>  
					</div>
					</div>
				';
						
            
        } else {
            
          
            echo '<!-- Select Basic -->
			<div class="form-group">
			<label class="col-md-4 control-label" for="selectbasic"> Shipping Method </label>
			<div class="col-md-4">
			<select id="selectbasic" name="Shipping[]" class="form-control" required="true" multiple="multiple" > ';
			$selectedShippingType = explode(":",$Shipping);
				foreach($ShippingTypes as $ShippingTypesValue) {
				if(in_array($ShippingTypesValue,$selectedShippingType)) {
					echo '            <option value="'.$ShippingTypesValue.'" selected="selected" >'.$ShippingTypesValue.'</option>
';
					}
				else {
					echo '            <option value="'.$ShippingTypesValue.'">'.$ShippingTypesValue.'</option>';
				}
					
			
			}
            echo '
</select>
<span class="help-block"> Only orders with selected status will be exported</span>  


</div>
</div>';
        }
        global $wpdb;
        $table_name_ecs = $wpdb->prefix . 'ecs';
        $querylast      = "SELECT * FROM $table_name_ecs " . "WHERE keytext = 'lastOrdername'  ";
        $statesmeta     = $wpdb->get_results($querylast);
        $lastname       = '';
        if (count($statesmeta) > 0) {
            foreach ($statesmeta as $k) {
                echo '<!-- Text input-->
<div class="form-group">
<label class="col-md-4 control-label  " for="textinput"  style="cursor:default"> Last Processed File</label>  
<div class="col-md-4">
<label class="col-md-4 control-label  " for="textinput" style="cursor:default ; padding-left:0px ; margin-left:0px ;">' . $k->type . ' </label>  
<span class="help-block"></span>  
</div>
</div>';
            }
        } else {
            echo '<!-- Text input-->
<div class="form-group">
<label class="col-md-4 control-label " for="textinput" style="cursor:default"> Last Processed File</label>  
<div class="col-md-4">
<label class="col-md-4 control-label " for="textinput"  style="cursor:default"> </label>  
<span class="help-block"></span>  
</div>
</div>';
        }
	
	
	}
	
   
    
    




}

?>