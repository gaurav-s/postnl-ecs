<?php
class PostNLProcess
{

    public function _getBadCharacters()
    {
        return [
            ";",
            "\\",
            "`",
            '\'',
            '"',
            "&",
            "*",
            "{",
            "}",
            "[",
            "]",
            "!",
            "<",
            ">",
        ];
    }

    public function getFtpSettings()
    {
        return ecsSftpProcess::init();
    }

    public function checkFtpSettings($path)
    {
        $EcsSftpSettings = $this->getFtpSettings();

        $ftpCheck = $EcsSftpSettings->checkSftpSettings($path);

        if ($ftpCheck[0] == "SUCCESS") {
            return $ftpCheck[1];
        }

        error_log("POSTNLECS: ERROR " . $ftpCheck[1]);
        $this->log( "POSTNLECS: ERROR " . $ftpCheck[1], 'error' ) ;
        return false;
    }

    public function sendErrorEmail($mailBody, $type)
    {
        global $wpdb;
        $name = "";
        $email = "";

        // find list of states in DB
        $table_name_ecs = $wpdb->prefix . "ecs";
        $qry = "SELECT * FROM " .$table_name_ecs ." WHERE keytext ='general' ORDER BY id DESC  LIMIT 1";
        $states = $wpdb->get_results($qry);
        $settingID = "";

        foreach ($states as $k) {
            $settingID = $k->id;
        }

        $table_name = $wpdb->prefix . "ecsmeta";

        // find list of states in DB
        $qrymeta = "SELECT * FROM " .$table_name ." WHERE settingid = '" .$settingID ."'";
        $statesmeta = $wpdb->get_results($qrymeta);

        foreach ($statesmeta as $k) {
            if ($k->keytext == "Name") {
                $name = $k->value;
            }

            if ($k->keytext == "Email") {
                $email = $k->value;
            }
        }

        $to = $email;
        $subject = "PostNL Fulfilment plugin: " .$type ." processing error for webshop " .get_bloginfo();

        $body = $mailBody;
        $headers = ["Content-Type: text/html; charset=UTF-8"];

        wp_mail($to, $subject, $body, $headers);
    }

    public function woocommerce_version_check($version = "2.1")
    {
        if (
            function_exists("is_woocommerce_active") &&
            is_woocommerce_active()
        ) {
            global $woocommerce;
            if (version_compare($woocommerce->version, $version, ">=")) {
                return true;
            }
        }
        return false;
    }

    public function formatnumber($number)
    {
        $numConverted = round((float) $number, 2);

        return number_format($numConverted, 2, ".", "");
    }

    /**
	 * Log a helper event.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional, defaults to info, valid levels: emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public function log( $message, $level = 'error' ) {

        if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$log = wc_get_logger();

		$log->log( $level, $message, array( 'source' => 'postnl_fullfillment' ) );
	}
}