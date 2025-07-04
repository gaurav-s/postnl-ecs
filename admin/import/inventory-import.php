<div class="panel panel-default space ">
 <div class="panel-heading">
  <h4 class="panel-title">
   <a data-toggle="collapse" data-parent="#accordion" href="#collapse6">
    Inventory import</a>
  </h4>
 </div>
 <div id="collapse6" class="panel-collapse collapse">
  <div class="panel-body">
  </div>
  <form class="form-horizontal" action="" method="post">
   <fieldset>
    <!-- Form Name -->
    <legend></legend>
    <?php
    require_once __DIR__ . "/ecsInventorySettings.php";
    require_once dirname(__DIR__) . "/ecsSftpProcess.php";

    // find list of states in DB
    $EcsInventorySettings = ecsInventorySettings::init();
    if (!isset($_POST["inventoryImport"])) {
        global $wpdb;
        $Cron = "";
        $Path = "";
        $cron = "";
        $no = "";
		
        // find list of states in DB

        $settingID = $EcsInventorySettings->getSettingId();
        if (!empty($settingID)) {
            $statesmeta = $EcsInventorySettings->loadInventorySettings(
                $settingID
            );
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
        $EcsInventorySettings->displayInventorySettings($Cron, $Path, $no);
    }
    if (isset($_POST["inventoryImport"])) {
        // handle post data
        $remoteFile = "public_html/ecs/test.xml";
        $port = 22;
        $Cron = $_POST["Cron"];
        $Path = $_POST["Path"];
        $no = $_POST["no"];
		
        $EcsSftpProcess = ecsSftpProcess::init();

        $ftpCheck = $EcsSftpProcess->checkSftpSettings($Path);

        if ($ftpCheck[0] == "SUCCESS") {
            $settingID = $EcsInventorySettings->getSettingId();

            if ($settingID == "") {
                $id = $EcsInventorySettings->saveSettings();
                $EcsInventorySettings->saveSettingsValues($id, "Cron", $Cron);
                $EcsInventorySettings->saveSettingsValues($id, "Path", $Path);
				$EcsInventorySettings->saveSettingsValues($id, "no", $no);				
            } else {
                $statesmeta = $EcsInventorySettings->getSettingValues(
                    $settingID
                );
				$setNo = false;
                foreach ($statesmeta as $k) {
                    if ($k->keytext == "Cron") {
                        $EcsInventorySettings->updateSettingsValues(
                            $k->id,
                            $Cron
                        );
                    }
                    if ($k->keytext == "Path") {
                        $EcsInventorySettings->updateSettingsValues(
                            $k->id,
                            $Path
                        );
                    }
                    if ($k->keytext == "no") {
                        $EcsInventorySettings->updateSettingsValues($k->id, $no);
                        $setNo = true;
                    }					
                }
                if (!$setNo) {
                    $EcsInventorySettings->saveSettingsValues(
                        $settingID,
                        "no",
                        $no
                    );
                }				
            }

            $postnlStock = new PostNLStock();
            $postnlStock->processStock();
            if ($Cron == "0") {
                postnlecs_stop_cron_inventory();
            } else {
                wp_clear_scheduled_hook("task_inventory_import");
                if (!wp_next_scheduled("task_inventory_import")) {
                    wp_schedule_event(time(), $Cron, "task_inventory_import");
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
        $EcsInventorySettings->displayInventorySettings($Cron, $Path, $no);
        echo "<script>
$(document).ready(function(){
$('#collapse6').collapse('show');
});
</script>";
    }
    ?>
    <div class="form-group">
     <label class="col-md-4 control-label" for="singlebutton"></label>
     <div class="col-md-4">
      <button id="singlebutton" name="inventoryImport" class="btn btn-primary" type="submit">Save</button>
     </div>
    </div>