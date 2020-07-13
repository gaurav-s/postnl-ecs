<?php 
class ecsShipmentSettings {
   
    public static $instance;
	
	
    
	public static function init()
    {
        if ( is_null( self::$instance ) )
            self::$instance = new ecsShipmentSettings();
        return self::$instance;
    }
    
    private function __construct()
    {
     
		
    }
    
    public function loadShipmentSettings($settingID)
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
		$qry            = "SELECT * FROM  	$table_name_ecs " . "WHERE keytext ='shipmentImport' ORDER BY id DESC  LIMIT 1 ";
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
							'type' => '5',
							'enable' => 'true',
							'keytext' => 'shipmentImport' // ... and so on
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
	
	
    
	public function displayShipmentSettings($Cron,$Path, $tracking, $Inform   ) {
		 echo '<div class="form-group">
            <label class="col-md-4 control-label" for="textinput">Path</label>  
            <div class="col-md-4">
            <input id="textinput" name="Path" type="text" placeholder="Path" required="true" class="form-control input-md" value=' . $Path . '>
            <span class="help-block">For example /orders</span>  
            </div>
            </div>
            ';
  
        postnlecs_cron_selection_display($Cron);

        echo ' <!-- Text input-->
            <div class="form-group">
            <label class="col-md-4 control-label" for="textinput">Tracking Url</label>  
            <div class="col-md-4">
            <input id="textinput" name="tracking" type="text" placeholder="Tracking" required="true" class="form-control input-md" value=' . $tracking . '>
            <span class="help-block">ex:https://jouw.postnl.nl/#!/track-en-trace/</span>  
            </div>
            </div>';
        if ($Inform == "1") {
            echo '
                <!-- Select Basic -->
                <div class="form-group">
                <label class="col-md-4 control-label" for="selectbasic"> Inform Customer </label>
                <div class="col-md-4">
                <select id="selectbasic" name="Inform" class="form-control">
                <option value="1" selected="selected" >YES</option>
                <option value="2">No</option>
                </select>
                <span class="help-block">Standart order shipment email will be sent to customer</span>  
                </div>
                </div>
                ';
        } else {
            echo ' 
                <!-- Select Basic -->
                <div class="form-group">
                <label class="col-md-4 control-label" for="selectbasic"> Inform Customer </label>
                <div class="col-md-4">
                <select id="selectbasic" name="Inform" class="form-control">
                <option value="1">YES</option>
                <option value="2" selected="selected" >No</option>
                </select>
                <span class="help-block">Standart order shipment email will be sent to customer</span>  
                </div>
                </div>
                ';
        }    
		
	
	
	}
	
   
    
    




}

?>