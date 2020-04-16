<div class="panel panel-default space">
	<div class="panel-heading">
		<h4 class="panel-title">
			<a data-toggle="collapse" data-parent="#accordion" href="#collapse5">
				Shipment import</a>
		</h4>
	</div>
	<div id="collapse5" class="panel-collapse collapse">
		<div class="panel-body">
		</div>
		<form class="form-horizontal" action="" method="post"  >
			<fieldset>
				<!-- Form Name -->
				<legend></legend>
				<?php
					require_once(__DIR__ . "/ecsShipmentSettings.php");
				require_once(dirname(__DIR__) . "/ecsSftpProcess.php");
		 
		// find list of states in DB
		$EcsShipmentSettings = ecsShipmentSettings::init();
    if (!isset($_POST['shipmentImport'])) {
        global $wpdb;
        $Cron           = '';
        $Path           = '';
        $Inform         = '';
        $tracking       = '';
        $enable         = '';
        $lastfile       = '';
        // find list of states in DB
			$settingID = $EcsShipmentSettings->getSettingId();
		
       	if(!empty($settingID)){
			$statesmeta = $EcsShipmentSettings->loadShipmentSettings($settingID);
			foreach ($statesmeta as $k) {
				if ($k->keytext == "Cron") {
					$Cron = $k->value;
				}
				if ($k->keytext == "Path") {
					$Path = $k->value;
				}
				if ($k->keytext == "tracking") {
					$tracking = $k->value;
				}
				if ($k->keytext == "Inform") {
					$Inform = $k->value;
				}
			}
		}
        
			$EcsShipmentSettings->displayShipmentSettings($Cron,$Path, $tracking, $Inform);
		
		
        
       
    }
?>
				<?php
    
    if (isset($_POST['shipmentImport'])) {
        // handle post data
       // $localFile  = ECS_DATA_PATH."\shipment.xml;
        $remoteFile = 'public_html/ecs/shipment.xml';
        $port       = 22;
        //$Enable     = $_POST["Enable"];
        //$Last       = $_POST["Last"];
        $Cron       = $_POST["Cron"];
        $Inform     = $_POST["Inform"];
        //$Status     = $_POST["Status"];
        $Path       = $_POST["Path"];
        $tracking   = $_POST["tracking"];
        
		$EcsSftpProcess = ecsSftpProcess::init();
        
		$ftpCheck = $EcsSftpProcess->checkSftpSettings($Path);
		
		if($ftpCheck[0] == 'SUCCESS') {
		
		$settingID = $EcsShipmentSettings->getSettingId();
		
		if ($settingID == '') {
				$id = $EcsShipmentSettings->saveSettings();
				$EcsShipmentSettings->saveSettingsValues($id,'Cron',$Cron);
				$EcsShipmentSettings->saveSettingsValues($id,'tracking',$tracking);
				$EcsShipmentSettings->saveSettingsValues($id,'Path',$Path);
				$EcsShipmentSettings->saveSettingsValues($id,'Inform',$Inform);
			
				
				} else {
				$statesmeta = $EcsShipmentSettings->getSettingValues($settingID);
				foreach ($statesmeta as $k) {
							if ($k->keytext == "Cron") $EcsShipmentSettings->updateSettingsValues($k->id,$Cron);
							if ($k->keytext == "Path") $EcsShipmentSettings->updateSettingsValues($k->id,$Path);
							if ($k->keytext == "tracking") $EcsShipmentSettings->updateSettingsValues($k->id,$tracking);
							if ($k->keytext == "Inform") $EcsShipmentSettings->updateSettingsValues($k->id,$Inform);
						}
				}
		if ($Cron == '0') {
					stop_cron_shipment();
				} else {
						wp_clear_scheduled_hook('task_shipement_import');
						if (!wp_next_scheduled('task_shipement_import')) {
							wp_schedule_event(time(), $Cron, 'task_shipement_import');
						} else {
								}
				}
		echo '<div class="alert alert-success">
			<strong>Updated successfully</strong> 
			</div>';
		
		} else {
			?>
					<div class="alert alert-danger">
						<strong> <?php echo $ftpCheck[1]; ?> </strong>
					</div>
					<?php
		
		}
		$EcsShipmentSettings->displayShipmentSettings($Cron,$Path, $tracking, $Inform);
		 echo "<script>
$(document).ready(function(){
$('#collapse5').collapse('show');
});
</script>";
		
        
       
    }
?>  
				<!-- Button -->
				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button id="singlebutton" name="shipmentImport" class="btn btn-primary" type="submit" >Save</button>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>