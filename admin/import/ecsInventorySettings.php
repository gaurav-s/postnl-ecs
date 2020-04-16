<?php 
class ecsInventorySettings {
   
    public static $instance;
	
	
    
	public static function init()
    {
        if ( is_null( self::$instance ) )
            self::$instance = new ecsInventorySettings();
        return self::$instance;
    }
    
    private function __construct()
    {
     
		
    }
    
    public function loadInventorySettings($settingID)
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
		$qry            = "SELECT * FROM  	$table_name_ecs " . "WHERE keytext ='inventoryImport' ORDER BY id DESC  LIMIT 1 ";
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
							'type' => '6',
							'enable' => 'true',
							'keytext' => 'inventoryImport' // ... and so on
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
	
	
    
	public function displayInventorySettings($Cron,$Path) {
		echo '<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Path</label>  
<div class="col-md-4">
<input id="textinput" name="Path" type="text" placeholder="Path" required="true" class="form-control input-md" value=' . $Path . '>
<span class="help-block">For example /orders</span>  
</div>
</div>
';
        if ($Cron == '') {
            echo '
<!-- Select Basic -->
<div class="form-group">
<label class="col-md-4 control-label" for="selectbasic">Cron schedule</label>
<div class="col-md-4">
<select id="selectbasic" name="Cron" class="form-control">
<option value="15min">15 min</option>
<option value="30min">30 min</option>
<option value="1hour">1 hour</option>
<option value="2hour">2 hour</option>
<option value="4hour">4 hour</option>
<option value="1day">1 daily</option>
<option value="0" selected="selected" >Stop</option>  
</select>
<span class="help-block">Pick a schedule</span>  
</div>
</div>';
        } else {
            echo '
<!-- Select Basic -->
<div class="form-group">
<label class="col-md-4 control-label" for="selectbasic">Cron schedule</label>
<div class="col-md-4">
<select id="selectbasic" name="Cron" class="form-control">';
            if ($Cron == "15min") {
                echo '     <option value="15min" selected="selected" >15 min</option>';
            } else {
                echo '     <option value="15min">15 min</option>';
            }
            if ($Cron == "30min") {
                echo '  <option value="30min" selected="selected">30 min</option>';
            } else {
                echo '  <option value="30min">30 min</option>';
            }
            if ($Cron == "1hour") {
                echo '    <option value="1hour" selected="selected"  >1 hour</option>';
            } else {
                echo ' <option value="1hour">1 hour</option>';
            }
            if ($Cron == "2hour") {
                echo '     <option value="2hour" selected="selected" >2 hour</option>';
            } else {
                echo '  <option value="2hour">2 hour</option>';
            }
            if ($Cron == "4hour") {
                echo '  <option value="4hour" selected="selected"  >4 hour</option>';
            } else {
                echo '<option value="4hour">4 hour</option>';
            }
            if ($Cron == "1day") {
                echo '    <option value="1day" selected="selected"  >1 daily</option>';
            } else {
                echo '  <option value="1day">1 daily</option>';
            }
            if ($Cron == "0") {
                echo '    <option value="0" selected="selected"  >Stop</option>';
            } else {
                echo ' <option value="0">Stop</option>';
            }
            echo '
</select>
<span class="help-block">Pick a schedule</span>  
</div>
</div>';
        }
	
	
	}
	
   
    
    




}

?>