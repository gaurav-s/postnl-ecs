<div class="panel panel-default space">
	<div class="panel-heading">
		<h4 class="panel-title">
			<a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
				Product export</a>
		</h4>
	</div>
	<div id="collapse3" class="panel-collapse collapse">
		<div class="panel-body">
		</div>
		<form class="form-horizontal" action="" method="post">
			<fieldset>
				<!-- Form Name -->
				<legend></legend>
				<!-- Text input-->
				<?php
				require_once(__DIR__ . "/EcsProductSettings.php");
				require_once(dirname(__DIR__) . "/ecsSftpProcess.php");
		 
				$EcsProductSettings = ecsProductSettings::init();
    if (!isset($_POST['ProductExport'])) {
        global $wpdb;
        $Cron           = '';
        $Path           = '';
        $informcustomer = '';
        $cron           = '';
        $enable         = '';
        $lastfile       = '';
        $no             = '';
        // find list of states in DB
        
		$settingID = $EcsProductSettings->getSettingId();
		
		if(!empty($settingID)){
			$statesmeta = $EcsProductSettings->loadProductSettings($settingID);
			
			foreach ($statesmeta as $k) {
				if ($k->keytext == "Cron") {
					$Cron = $k->value;
				}
				if ($k->keytext == "Path") {
					$Path = $k->value;
				}
				if ($k->keytext == "no") {
					$no = $k->value;
				}
			}
		}
		
		$EcsProductSettings->displayProductExpSettings($Cron, $Path, $no);
		
	
    }
?>
				<?php
    if (isset($_POST['ProductExport'])) {
        // handle post data
        $localFile  = 'test.xml';
        $remoteFile = 'public_html/ecs/test.xml';
        $port       = 22;
        //$Enable     = $_POST["Enable"];
        //$Last       = $_POST["Last"];
        $Cron       = $_POST["Cron"];
        //$Shipping   = $_POST["Shipping"];
        //$Status     = $_POST["Status"];
        $Path       = $_POST["Path"];
        $no         = $_POST["no"];
        $validate   = true;
		
		$EcsSftpProcess = ecsSftpProcess::init();
		$ftpCheck = $EcsSftpProcess->checkSftpSettings($Path);
		
		if($ftpCheck[0] == 'SUCCESS') {
			$sftp = $ftpCheck[1];
			
			$settingID = $EcsProductSettings->getSettingId();
			if ($settingID == '') {
				$id = $EcsProductSettings->saveSettings();
				//$EcsProductSettings->saveSettingsValues($id,'Last',$Last);
				$EcsProductSettings->saveSettingsValues($id,'Cron',$Cron);
				//$EcsProductSettings->saveSettingsValues($id,'Shipping',$Shipping);
				$EcsProductSettings->saveSettingsValues($id,'Path',$Path);
				$EcsProductSettings->saveSettingsValues($id,'no',$no);
				
				
			} else {
			$statesmeta = $EcsProductSettings->getSettingValues($settingID);
			foreach ($statesmeta as $k) {
									
					if ($k->keytext == "Cron") $EcsProductSettings->updateSettingsValues($k->id,$Cron);
					if ($k->keytext == "Path") $EcsProductSettings->updateSettingsValues($k->id,$Path);
					if ($k->keytext == "no") $EcsProductSettings->updateSettingsValues($k->id,$no);
				
				}
			
			}
			if ($Cron == '0') {
					stop_cron_product();
			} else {
					 	 //orderfunction12();
						 //$obj =  new ni_order_list();
					 		//$obj->productExport();
					wp_clear_scheduled_hook('task_product_export');
					if (!wp_next_scheduled('task_product_export')) {
						wp_schedule_event(time(), $Cron, 'task_product_export');
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
		$EcsProductSettings->displayProductExpSettings($Cron, $Path, $no);
		 echo "<script>
				$(document).ready(function(){
				$('#collapse3').collapse('show');
				});
				</script>";
	}
     
    
?>
				<!-- Button -->
				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button id="singlebutton" name="ProductExport" class="btn btn-primary" type="submit" >Save</button>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>