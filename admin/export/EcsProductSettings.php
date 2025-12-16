<?php
class ecsProductSettings
{
    public static $instance;

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new ecsProductSettings();
        }
        return self::$instance;
    }

    public function loadProductSettings($settingID)
    {
        // find list of states in DB
        global $wpdb;
        $table_name = $wpdb->prefix . "ecsmeta";
        $qrymeta = $wpdb->prepare("SELECT * FROM $table_name WHERE settingid = %d", $settingID);
        $statesmeta = $wpdb->get_results($qrymeta);
        return $statesmeta;
    }

    public function getSettingId()
    {
        global $wpdb;
        $table_name_ecs = $wpdb->prefix . "ecs";
        $qry = $wpdb->prepare("SELECT * FROM $table_name_ecs WHERE keytext = %s ORDER BY id DESC LIMIT 1", 'prductExport');
        $states = $wpdb->get_results($qry);
        $settingID = "";
        foreach ($states as $k) {
            $settingID = $k->id;
        }
        return $settingID;
    }

    public function saveSettings()
    {
        global $wpdb;
        $table_name_ecs = $wpdb->prefix . "ecs";
        $wpdb->insert($table_name_ecs, [
            "type" => "3",
            "enable" => "true",
            "keytext" => "prductExport", // ... and so on
        ]);
        $id = $wpdb->insert_id;
        return $id;
    }

    public function saveSettingsValues($id, $keytext, $value)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "ecsmeta";
        $wpdb->insert($table_name, [
            "settingid" => $id,
            "keytext" => $keytext,
            "value" => $value,
        ]);
    }

    public function updateSettingsValues($id, $value)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "ecsmeta";
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name SET value = %s WHERE id = %d",
                $value,
                $id
            )
        );
    }

    public function getSettingValues($settingID)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "ecsmeta";
        $qrymeta = $wpdb->prepare("SELECT * FROM $table_name WHERE settingid = %d", $settingID);
        $statesmeta = $wpdb->get_results($qrymeta);
        return $statesmeta;
    }

    public function displayProductExpSettings($Cron, $Path, $no, $ean_field)
    {
        //Path Field
        echo '<div class="form-group">
                    <label class="col-md-4 control-label" for="textinput">Path</label>
                    <div class="col-md-4">
                        <input id="textinput" name="Path" type="text" placeholder="Path" required="true" class="form-control input-md" value=' .
            $Path .
            '>
                        <span class="help-block">For example /Products</span>
                    </div>
                </div>
                ';
        //No of Products
        echo '<div class="form-group">
                    <label class="col-md-4 control-label" for="textinput">Number of Products per file</label>
                        <div class="col-md-4">
                        <input id="textinput" name="no" type="number" placeholder="number" required="true" class="form-control input-md" value=' .
            $no .
            '>
                        <span class="help-block">For example 3,5,10</span>
                    </div>
                </div>
                ';
        echo '<div class="form-group">
                <label class="col-md-4 control-label" for="textinput">Attribute code of EAN</label>
                    <div class="col-md-4">
                    <input id="textinput" name="ean_attribute" type="text" placeholder="ean"  class="form-control input-md" value=' .
            $ean_field .
            '>
                    <span class="help-block">For example ean</span>
                </div>
            </div>
            ';

        postnlecs_cron_selection_display($Cron);

        global $wpdb;
        $table_name_ecs = $wpdb->prefix . "ecs";
        $querylast = $wpdb->prepare("SELECT * FROM $table_name_ecs WHERE keytext = %s", 'lastproductname');
        $statesmeta = $wpdb->get_results($querylast);

        if (count($statesmeta) > 0) {
            foreach ($statesmeta as $k) {
                echo '<!-- Text input-->
                        <div class="form-group">
                        <label class="col-md-4 control-label " for="textinput" style="cursor:default"> Last Processed File</label>
                        <div class="col-md-4">
                        <label class="col-md-4 control-label " for="textinput" style="cursor:default; padding-left:0px ; margin-left:0px ;" >' .
                    $k->type .
                    ' </label>
                        <span class="help-block"></span>
                        </div>
                        </div>';
            }
        } else {
            echo '<!-- Text input-->
                <div class="form-group">
                <label class="col-md-4 control-label" for="textinput" style="cursor:default"> Last Processed File</label>
                <div class="col-md-4">
                <label class="col-md-4 control-label" for="textinput" style="cursor:default"> </label>
                <span class="help-block"></span>
                </div>
                </div>';
        }
    }
}

?>