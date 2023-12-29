<div class="panel panel-default space">
	<div class="panel-heading">
		<h4 class="panel-title">
			<a data-toggle="collapse" data-parent="#accordion" href="#collapse4">
				Order export</a>
		</h4>
	</div>
	<div id="collapse4" class="panel-collapse collapse">
		<div class="panel-body">
		</div>
		<form class="form-horizontal" action="" method="post">
			<fieldset>
				<!-- Form Name -->
				<legend></legend>
				<?php
				require_once(__DIR__ . "/EcsOrderSettings.php");
				require_once(dirname(__DIR__) . "/ecsSftpProcess.php");
		 ecsOrderSettings::init();
		// find list of states in DB
		$EcsOrderSettings = ecsOrderSettings::init();
    if (!isset($_POST['OrderExport'])) {
        global $wpdb;
        $Cron           = '';
        $Path           = '';
        $informcustomer = '';
        $giftMessage    = '';
        $giftMessageValue  = '';
        $cron           = '';
        $Shipping       = '';
        $Status         = '';
        $no             = '';
		$table_name_ecs = $wpdb->prefix . 'ecs';


		$settingID = $EcsOrderSettings->getSettingId();


        if(!empty($settingID)){
			$statesmeta = $EcsOrderSettings->loadOrderSettings($settingID);
			foreach ($statesmeta as $k) {
				if ($k->keytext == "Cron") {
					$Cron = $k->value;
				}
				if ($k->keytext == "Path") {
					$Path = $k->value;
				}
				if ($k->keytext == "Shipping") {
					$Shipping = $k->value;
				}
				if ($k->keytext == "Status") {
					$Status = $k->value;

				}
				if ($k->keytext == "no") {
					$no = $k->value;
				}
                if ($k->keytext == "giftcard_attribute") {
                    $giftMessage = $k->value;
                }
                if ($k->keytext == "giftcard_attribute_value") {
                    $giftMessageValue = $k->value;
                }
			}
		}
		$EcsOrderSettings->displayOrderExpSettings($Cron,$Path, $Shipping, $Status, $no, $giftMessage, $giftMessageValue);


    }
?>
				<?php
    if (isset($_POST['OrderExport'])) {
        // handle post data
        $localFile  = 'test.xml';
        $remoteFile = 'public_html/ecs/test.xml';
        $port       = 22;

        $Cron       = $_POST["Cron"];
        $Status     = "";
        $Shipping   = "";
               $ShippingArray = $_POST["Shipping"];

        $StatusArray = $_POST["Status"];

        $Path = $_POST["Path"];
        $no   = $_POST["no"];
        $giftMessage    =  $_POST["giftcard_attribute"];
        $giftMessageValue  =  $_POST["giftcard_attribute_value"];




        foreach ($StatusArray as $selectedOption) {
            $Status .= $selectedOption . ":";
        }


        foreach ($ShippingArray as $selectedOption1) {

            $Shipping .= $selectedOption1 . ":";
        }

        global $wpdb;
        // find list of states in DB
       $EcsSftpProcess = ecsSftpProcess::init();

		$ftpCheck = $EcsSftpProcess->checkSftpSettings($Path);
		$settingID = $EcsOrderSettings->getSettingId();
		if($ftpCheck[0] == 'SUCCESS') {
				$order = new WC_Order();

				if ($settingID == '') {
				$id = $EcsOrderSettings->saveSettings();
				$EcsOrderSettings->saveSettingsValues($id,'Cron',$Cron);
				$EcsOrderSettings->saveSettingsValues($id,'Shipping',$Shipping);
				$EcsOrderSettings->saveSettingsValues($id,'Path',$Path);
				$EcsOrderSettings->saveSettingsValues($id,'Status',$Status);
				$EcsOrderSettings->saveSettingsValues($id,'giftcard_attribute',$giftMessage);
                $EcsOrderSettings->saveSettingsValues($id,'giftcard_attribute_value',$giftMessageValue);

				}
				else {
				$statesmeta = $EcsOrderSettings->getSettingValues($settingID);
                $setGiftSetting = false;
                $setGiftSettingValue = false;
				foreach ($statesmeta as $k) {
							if ($k->keytext == "Cron") $EcsOrderSettings->updateSettingsValues($k->id,$Cron);
							if ($k->keytext == "Path") $EcsOrderSettings->updateSettingsValues($k->id,$Path);
							if ($k->keytext == "Shipping") $EcsOrderSettings->updateSettingsValues($k->id,$Shipping);
							if ($k->keytext == "Status") $EcsOrderSettings->updateSettingsValues($k->id,$Status);
							if ($k->keytext == "no") $EcsOrderSettings->updateSettingsValues($k->id,$no);

							if ($k->keytext == "giftcard_attribute") {
                                $EcsOrderSettings->updateSettingsValues($k->id,$giftMessage);
                                $setGiftSetting = true;
                            }
                            if ($k->keytext == "giftcard_attribute_value") {
                                $EcsOrderSettings->updateSettingsValues($k->id,$giftMessageValue);
                                $setGiftSettingValue = true;
                            }


						}

				        if(!$setGiftSetting)
                            $EcsOrderSettings->saveSettingsValues($settingID,'giftcard_attribute',$giftMessage);
                        if(!$setGiftSettingValue)
                            $EcsOrderSettings->saveSettingsValues($settingID,'giftcard_attribute_value',$giftMessageValue);

				}

					$postnlOrder = new PostNLOrder();
					$postnlOrder->processOrders();
					//cron_order_export
					if ($Cron == '0') {
						postnlecs_stop_cron_order();
					} else {




						wp_clear_scheduled_hook('task_order_export');
						if (!wp_next_scheduled('task_order_export')) {
							wp_schedule_event(time(), $Cron, 'task_order_export');
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
		$EcsOrderSettings->displayOrderExpSettings($Cron,$Path, $Shipping, $Status, $no, $giftMessage, $giftMessageValue);


        echo "<script>
$(document).ready(function(){
$('#collapse4').collapse('show');
});
</script>";
    }
?>
				<!-- Button -->
				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button id="singlebutton" name="OrderExport" class="btn btn-primary" type="submit" >Save</button>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>