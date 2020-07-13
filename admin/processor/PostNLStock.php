<?php
class PostNLStock extends PostNLProcess
{
    
    
    public function __construct() {
			
    }

    public function processStock(){

       

       

        $EcsInventorySettings = ecsInventorySettings::init();
        $Path = '';
        $Cron = '';
		$settingID = $EcsInventorySettings->getSettingId();
		if($settingID) { 	
			$statesmeta = $EcsInventorySettings->loadInventorySettings($settingID);
		
		}

		else { 
			error_log('Stock Settings not found'); 
			return;
		}
				
		foreach ($statesmeta as $k) {
				
			if ($k->keytext == "Path") {
				$Path = $k->value;
            }
            
            if($k->keytext == "Cron") {
                $Cron = $k->value;
            }
				
				
        }
        $EcsSftpSettings = $this->getFtpSettings();
        $sftp = $this->checkFtpSettings($Path);
        
        if(empty($Path)) {
            error_log('POSTNL: Stock Import Path Settings not found'); 
            return false;
        }

        if(!$sftp) 
            return false;
        
        
            
        
        $sftp->chdir($Path); // open directory 'test'
		$endPath = $sftp->pwd();

        foreach($sftp->nlist() as $filename) {
					
            $codesNames = explode(".xml", $filename);
            if(count($codesNames) >0) {
                if($filename == '.' || $filename == '..') {
                    continue;
                } 
                
                
                    
                    //$sftp->get($sftp->pwd() . '/' . $filename, ECS_DATA_PATH."/".$filename);
                    //if (file_exists(ECS_DATA_PATH."/".$filename) && filesize(ECS_DATA_PATH."/".$filename) > 0) {
                        //$xml = simplexml_load_file(ECS_DATA_PATH."/".$filename, 'SimpleXMLElement', LIBXML_NOWARNING);
                        $stockFileData = $sftp->get($sftp->pwd() . '/' . $filename);							
                        if($stockFileData) {
                          $xml = simplexml_load_string($stockFileData, 'SimpleXMLElement', LIBXML_NOWARNING);
                        
                        $inventory_errors = array();
                        
                
                        foreach($xml->Stockupdate as $stock) {
                            
                            $postnlProductId = (string) $stock->stockdtl_itemnum;
                            $valid = true;
                            $Products = get_posts(array(
                                'post_type' => array('product','product_variation'),
                                'posts_per_page' => 100,
                                'meta_query' => array(
                                    array(
                                        'key' => '_sku',
                                        'value' => $postnlProductId,
                                        'compare' => '='
                                    )
                                )
                            )); 
                            if (count($Products) == 0) {
                                
                                
                                array_push($inventory_errors, "Product  SKU :" . $stock->stockdtl_itemnum . " is not found");
                            } else {
                                
                                    foreach($Products as $product) {
                                        $product_id = $product->ID;
                                    
                                        update_post_meta((int) $product_id, '_stock', (int) $stock->stockdtl_fysstock);
                                    }
                                
                                
                                
                            }
                        } 
                        

                        if(count($inventory_errors) > 0) {
                                $Errors = '
                                    <!DOCTYPE html>
                                    <html>
                                        <body><p>';
                                
                                $Errors .= 'An error occurred processing file:' . $filename;
                                $Errors .= '<br><b>Message:</b><br>';
                                
                                foreach($inventory_errors as $fails) {
                                                //error_log($fails);
                                    $Errors .= $fails;
                                    $Errors .= '<br>';
                                }
                                
                                $Errors .= '</p></body>
                                    </html>';
                                    
                                $this->sendErrorEmail($Errors,'Stock');
                                $sftp->chdir($Path.'/tmp');
                                if($sftp->pwd() !== $Path.'/tmp') {
                                    wp_die('check');
                                    $sftp->mkdir($Path.'/tmp');
                                    
                                }

                                $sftp->chdir($Path);
                                $sftp->rename($sftp->pwd() . '/' . $filename,$sftp->pwd() . '/tmp/' . $filename);
                        }
                        
                        else {
                                    $sftp->delete($sftp->pwd() . '/' . $filename);
                                    
                        }
                            
                        if(file_exists(ECS_DATA_PATH."/".$filename))
                                unlink(ECS_DATA_PATH."/".$filename);
                    
                }
            }
        }

    }
}