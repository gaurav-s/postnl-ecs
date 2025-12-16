<!-- Form Name -->
<legend></legend>
<div class="panel-group" id="accordion">
 <div class="panel panel-default space">
  <div class="panel-heading">
   <h4 class="panel-title">
    <a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
     General configuration</a>
   </h4>
  </div>
  <form class="form-horizontal" action="" method="post">
   <fieldset>
    <!-- Form Name -->
    <legend></legend>
    <?php wp_nonce_field('postnl_general_settings', 'postnl_general_nonce'); ?>
    <?php if (!isset($_POST["general"])) {
        global $wpdb;
        $name = "";
        $email = "";

        $table_name_ecs = $wpdb->prefix . "ecs";
        // find list of states in DB
        $qry = $wpdb->prepare("SELECT * FROM $table_name_ecs WHERE keytext = %s ORDER BY id DESC LIMIT 1", 'general');
        $states = $wpdb->get_results($qry);
        $settingID = "";
        foreach ($states as $k) {
            $settingID = $k->id;
        }
        // find list of states in DB
        $table_name = $wpdb->prefix . "ecsmeta";
        if (!empty($settingID)) {
            $qrymeta = $wpdb->prepare("SELECT * FROM $table_name WHERE settingid = %d", $settingID);
            $statesmeta = $wpdb->get_results($qrymeta);
            foreach ($statesmeta as $k) {
                if ($k->keytext == "Name") {
                    $name = $k->value;
                }
                if ($k->keytext == "Email") {
                    $email = $k->value;
                }
            }
        }
        echo '
<!-- Text input-->
<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Retailer Name</label>
<div class="col-md-4">
';
        echo '  <input id="textinput" name="Name" type="text" placeholder="Name" required="true" class="form-control input-md"  value=' .
            $name .
            ">  </input> " .
            '
<span class="help-block"></span>
</div>
</div>
<!-- Text input-->
<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Admin Email</label>
<div class="col-md-4">
<input type="email"  class="form-control input-md" id="email" placeholder="Enter email"  name="Email" value=' .
            $email .
            ' > </input>
<span class="help-block">You will receive any errors on the email configured here</span>
</div>
</div>';
        echo '<!-- Button -->
<div class="form-group">
<label class="col-md-4 control-label" for="singlebutton"></label>
<div class="col-md-4">
<button id="general" name="general" class="btn btn-primary" type="submit" >Save</button>
</div>
</div>';
    } ?>
    <?php if (isset($_POST["general"])) {
        $Name = $_POST["Name"];
        $Email = $_POST["Email"];
        ///Reload start
        global $wpdb;
        $informcustomer = "";
        $cron = "";
        $enable = "";
        $lastfile = "";
        $table_name_ecs = $wpdb->prefix . "ecs";
        // find list of states in DB
        $qry = "SELECT * FROM $table_name_ecs " ."WHERE keytext ='general' ORDER BY id DESC  LIMIT 1 ";
        $states = $wpdb->get_results($qry);
        $settingID1 = "";
        foreach ($states as $k) {
            $settingID1 = $k->id;
        }
        if ($settingID1 == "") {
            global $wpdb;
            $table_name_ecs = $wpdb->prefix . "ecs";
            $table_name = $wpdb->prefix . "ecsmeta";
            $wpdb->insert($table_name_ecs, [
                "type" => "1",
                "enable" => "true",
                "keytext" => "general", // ... and so on
            ]);
            $id = $wpdb->insert_id;
            $wpdb->insert($table_name, [
                "settingid" => $id,
                "keytext" => "Name",
                "value" => $Name, // ... and so on
            ]);
            $wpdb->insert($table_name, [
                "settingid" => $id,
                "keytext" => "Email",
                "value" => $Email, // ... and so on
            ]);
        } else {
            $settingID = "";
            foreach ($states as $k) {
                $settingID = $k->id;
            }
            // find list of states in DB
            $table_name = $wpdb->prefix . "ecsmeta";
            $qrymeta = $wpdb->prepare("SELECT * FROM $table_name WHERE settingid = %d", $settingID);
            $statesmeta = $wpdb->get_results($qrymeta);
            foreach ($statesmeta as $k) {
                global $wpdb;
                if ($k->keytext == "Name") {
                    global $wpdb;
                    $table_name = $wpdb->prefix . "ecsmeta";
                    $update1 = $wpdb->query(
                        $wpdb->prepare("UPDATE $table_name SET value = %s WHERE id = %d", $Name, $k->id)
                    );
                }

                if ($k->keytext == "Email") {
                    $wpdb->query(
                        $wpdb->prepare("UPDATE $table_name SET value = %s WHERE id = %d", $Email, $k->id)
                    );
                }
            }
        }
        echo '<div class="alert alert-success">
<strong>Updated successfully</strong>
</div>';
        echo '
<!-- Text input-->
<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Retailer Name</label>
<div class="col-md-4">
';
        echo '  <input id="textinput" name="Name" type="text" placeholder="Name" required="true" class="form-control input-md"  value=' .
            $Name .
            ">  </input> " .
            '
<span class="help-block"></span>
</div>
</div>
<!-- Text input-->
<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Admin Email</label>
<div class="col-md-4">
<input type="email"  class="form-control input-md" id="email" placeholder="Enter email"  name="Email" value=' .
            $Email .
            ' > </input>
<span class="help-block">You will receive  any errors on the email configured here</span>
</div>
</div>';
        echo '<!-- Button -->
<div class="form-group">
<label class="col-md-4 control-label" for="singlebutton"></label>
<div class="col-md-4">
<button id="general" name="general" class="btn btn-primary" type="submit" >Save</button>
</div>
</div>';
        echo "<script>
$(document).ready(function(){
$('#collapse1').collapse('show');
});
</script>";
    } ?>
   </fieldset>
  </form>
 </div>
</div>