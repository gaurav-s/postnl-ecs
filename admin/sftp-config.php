<?php
	use phpseclib3\Crypt\RSA;
	use phpseclib3\Net\SSH2;
	use phpseclib3\Net\SFTP;
?>
<div class="panel panel-default space"  >
	<div class="panel-heading">
		<h4 class="panel-title">
			<a data-toggle="collapse" data-parent="#accordion" href="#collapse2">
				SFTP configuration</a>
		</h4>
	</div>
	<div id="collapse2" class="panel-collapse collapse">
		<div class="panel-body">
		</div>
		<form class="form-horizontal" action="" method="post">
			<fieldset>
				<!-- Form Name -->
				<legend></legend>
				<!-- Text input-->
				<?php
    require_once __DIR__ . "/ecsSftpProcess.php";

    // find list of states in DB
    $EcsSftpSettings = ecsSftpProcess::init();
    if (!isset($_POST["singlebutton"])) {
        global $wpdb;
        $hostname = "";
        $Username = "";
        $PrivateKey = "";
        //$lastfile       = '';
        $host = "";
        $port = 22;

        $settingID = $EcsSftpSettings->getSettingId();

        if (!empty($settingID)) {
            $statesmeta = $EcsSftpSettings->loadSftpSettings($settingID);
            foreach ($statesmeta as $k) {
                if ($k->keytext == "Hostname") {
                    $hostname = $k->value;
                }
                if ($k->keytext == "Username") {
                    $Username = $k->value;
                }
                if ($k->keytext == "PrivateKey") {
                    $PrivateKey = $k->value;
                }
                if ($k->keytext == "Port") {
                    $port = $k->value;
                }
            }
        }
        $EcsSftpSettings->displaySftpSettings(
            $hostname,
            $Username,
            $PrivateKey,
            $port
        );
    }
    ?>
				<?php
    if (isset($_POST["singlebutton"])) {
        $ftp_source_file_name = "testb.xml";
        $ftp_dest_file_name = $ftp_source_file_name;
        $localFile = "test.xml";
        $remoteFile = "public_html/ecs/test.xml";
        $host = $_POST["Hostname"];
        //$port                 = 22;
        $port = $_POST["Port"];
        $user = $_POST["Username"];
        $pass = $_POST["PrivateKey"];

		$key = RSA::loadPrivateKey($pass);
        $ssh = new SSH2($host);
        $local_directory = "test2.xml";
        $remote_directory = "/woocommerce_test/Order/";
        $sftp = new SFTP($host);


        if (!$sftp->login($user, $key) || !$ssh->login($user, $key)) { ?>
				<div class="alert alert-danger">
					<strong>  There was an error. Please check again your credentials</strong>
				</div>
				<?php $EcsSftpSettings->displaySftpSettings(
        $host,
        $user,
        $pass,
        $port
    );} else { ?>
				<div class="alert alert-success">
					<strong>Updated successfully</strong>
				</div>
				<?php
    $settingID1 = $EcsSftpSettings->getSettingId();

    global $wpdb;

    if ($settingID1 == "") {
        $id = $EcsSftpSettings->saveSettings();

        $EcsSftpSettings->saveSettingsValues($id, "Hostname", $host);
        $EcsSftpSettings->saveSettingsValues($id, "Username", $user);
        $EcsSftpSettings->saveSettingsValues($id, "PrivateKey", $pass);
        $EcsSftpSettings->saveSettingsValues($id, "Port", $port);
    } else {
        $statesmeta = $EcsSftpSettings->getSettingValues($settingID1);
        foreach ($statesmeta as $k) {
            global $wpdb;
            if ($k->keytext == "Hostname") {
                $EcsSftpSettings->updateSettingsValues($k->id, $host);
            }
            if ($k->keytext == "Username") {
                $EcsSftpSettings->updateSettingsValues($k->id, $user);
            }
            if ($k->keytext == "PrivateKey") {
                $EcsSftpSettings->updateSettingsValues($k->id, $pass);
            }
            if ($k->keytext == "Port") {
                $EcsSftpSettings->updateSettingsValues($k->id, $port);
            }
        }
    }
    $EcsSftpSettings->displaySftpSettings($host, $user, $pass, $port);
    }
    }
    echo "<script>
$(document).ready(function(){
$('#collapse2').collapse('show');
});
</script>";

// Silence is golden.
?>
				<!-- Button -->
				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button id="singlebutton" name="singlebutton" class="btn btn-primary" type="submit" >Save</button>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>