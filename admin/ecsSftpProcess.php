<?php 
class ecsSftpProcess {
   
    public static $instance;
	
    
	public static function init()
    {
        if ( is_null( self::$instance ) )
            self::$instance = new ecsSftpProcess();
        return self::$instance;
    }
    
    private function __construct()
    {
       
    }
    
    public function checkSftpSettings($checkpath)
    {
		global $wpdb;
	$table_name_ecs = $wpdb->prefix . 'ecs';
	$table_name = $wpdb->prefix . 'ecsmeta';
		
		$settingID = $this->getSettingId();
		$SUCCESS = 'SUCCESS';
		$FAIL = 'FAIL';
		$message = '';

		if(!empty($settingID)){
			$qrymeta    = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
			$statesmeta = $wpdb->get_results($qrymeta);
			foreach ($statesmeta as $k) {
				if ($k->keytext == "PrivateKey") {
					$pass = $k->value;
				}
				if ($k->keytext == "Username") {
					$user = $k->value;
				}
				if ($k->keytext == "Hostname") {
					$host = $k->value;
				}
				if ($k->keytext == "Upload") {
				}
			}
			
		
			$file    = $this->getkeyFile($pass);
			// Open the file to get existing content
			//$current = file_get_contents($file);
			// Append a new person to the file
			//$current .= $pass;
			// Write the contents back to the file
			//file_put_contents($file, $current);
			
			$key = new Crypt_RSA();
			//$key->loadKey(file_get_contents($file));
			$key->loadKey($pass);
			$ssh              = new Net_SSH2($host);
			$local_directory  = 'test2.xml';
			$remote_directory = '/woocommerce_test/Order/';
			$sftp             = new Net_SFTP($host);
			if (!$sftp->login($user, $key)) {
				
				return array($FAIL, 'There was an error. Please check again your credentials');
				
			} else {
				
				$StartPath = $sftp->pwd();
				$sftp->chdir($checkpath); // open directory 'test'
				$endPath = $sftp->pwd();
			}
			if (strcmp($StartPath, $endPath) == 0) {
			return array($FAIL, 'There was an error .The path entered could not be found on the SFTP server. Please check the path and correct it.');
			}
			else return array($SUCCESS,$sftp);
		}
		else return array($FAIL, 'SFTP Settings not found. Please Enter SFTP details to continue');
			
    }
    
	  public function checkFtpLogin()
    {
      
		
    }
	 
		public function getkeyFile($pass)
    {
		if (!file_exists(dirname(__DIR__).'/data')) {
				mkdir(dirname(__DIR__).'/data', 0777, true);
			}
			$keyFile = dirname(__DIR__).'/data/private3.ppk';
			//$myfile = fopen($keyFile,'w') or die("Unable to open file!");
			//$txt = $pass;
			//fwrite($myfile, $txt);
			//fclose($myfile);
			return $keyFile;
		
    }
	  public function getSettingId()
    {
      
		global $wpdb;
	$table_name_ecs = $wpdb->prefix . 'ecs';
	$table_name = $wpdb->prefix . 'ecsmeta';
		 $qry       = "SELECT * FROM $table_name_ecs " . "WHERE keytext ='sftp' ORDER BY id DESC LIMIT 1 ";
        $states    = $wpdb->get_results($qry);
        $settingID = '';
        foreach ($states as $k) {
            $settingID = $k->id;
        }
		
	  return $settingID;
	  
    }
	
    public function loadSftpSettings($settingID)
    {
			global $wpdb;
			$table_name = $wpdb->prefix . 'ecsmeta';	
			$qrymeta    = "SELECT * FROM $table_name " . "WHERE settingid = $settingID  ";
			$statesmeta = $wpdb->get_results($qrymeta);
			
		return 	 $statesmeta; 
	
	
    }
	
	 public function saveSettings()
    {
			global $wpdb;
			$table_name_ecs = $wpdb->prefix . 'ecs';
			$wpdb->insert($table_name_ecs, array(
							'type' => '2',
							'enable' => 'true',
							'keytext' => 'sftp' // ... and so on
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
	
	
    
	public function displaySftpSettings($hostname,$Username, $PrivateKey, $port   ) {
    echo '<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Hostname</label>  
<div class="col-md-4">
<input id="textinput" name="Hostname" type="text" placeholder="Hostname"  required="true" class="form-control input-md" value=' . $hostname . '>  </input>
<span class="help-block">For example,test example.com or 192.168.1.1</span>  
</div>
</div>
' . '<!-- Text input-->
<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Port</label>  
<div class="col-md-4">
<input id="textinput" name="Port" type="text" placeholder="Port number" required="true" class="form-control input-md" value=' . $port . ' >  </input>
<span class="help-block">Leave empty for default (22)</span>  
</div>
</div>
' . '<!-- Text input-->
<div class="form-group">
<label class="col-md-4 control-label" for="textinput">Username</label>  
<div class="col-md-4">
<input id="textinput" name="Username" type="text" placeholder="username" required="true" class="form-control input-md"  value=' . $Username . '> </input>
</div>
</div>' . '<!-- 
Textarea
-->
<div class="form-group">
<label class="col-md-4 control-label" for="textarea">Key</label>
<div class="col-md-4">                     
<textarea class="form-control" id="textarea" name="PrivateKey"  overflow ="auto" rows="20">' . $PrivateKey . '</textarea>
<span class="help-block">PPK and PKCS formats are supported </span> 
</div>
</div>
';
	

}
}
?>