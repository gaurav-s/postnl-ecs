<?php
class ecsInventorySettings
{
    public static $instance;

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new ecsInventorySettings();
        }
        return self::$instance;
    }

    public function loadInventorySettings($settingID)
    {
        // find list of states in DB
        global $wpdb;
        $table_name = $wpdb->prefix . "ecsmeta";
        $qrymeta = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
        $statesmeta = $wpdb->get_results($qrymeta);

        return $statesmeta;
    }

    public function getSettingId()
    {
        global $wpdb;
        $table_name_ecs = $wpdb->prefix . "ecs";
        $qry = "SELECT * FROM  	$table_name_ecs " ."WHERE keytext ='inventoryImport' ORDER BY id DESC  LIMIT 1 ";
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
            "type" => "6",
            "enable" => "true",
            "keytext" => "inventoryImport", // ... and so on
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
                "UPDATE $table_name  SET value = '$value' WHERE id= %d", $id
            )
        );
    }

    public function getSettingValues($settingID)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "ecsmeta";
        $qrymeta = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
        $statesmeta = $wpdb->get_results($qrymeta);
        return $statesmeta;
    }

    public function displayInventorySettings($Cron, $Path)
    {
        echo '<div class="form-group">
            <label class="col-md-4 control-label" for="textinput">Path</label>
            <div class="col-md-4">
            <input id="textinput" name="Path" type="text" placeholder="Path" required="true" class="form-control input-md" value=' .
            $Path .
            '>
            <span class="help-block">For example /orders</span>
            </div>
            </div>
        ';

        postnlecs_cron_selection_display($Cron);
    }
}

?>