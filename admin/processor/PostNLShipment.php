<?php
class PostNLShipment extends PostNLProcess
{
    public function processShipment()
    {
        try
        {
            $EcsShipmentSettings = ecsShipmentSettings::init();
            $Path = "";
            $no = 100;

            $settingID = $EcsShipmentSettings->getSettingId();

            if ($settingID)
            {
                $statesmeta = $EcsShipmentSettings->loadShipmentSettings($settingID);
            }
            else
            {
                $this->log("Shipment Settings not found");
                return;
            }

            foreach ($statesmeta as $k)
            {
                if ($k->keytext == "Path")
                {
                    $Path = $k->value;
                }

                if ($k->keytext == "Cron")
                {
                    $Cron = $k->value;
                }
                if ($k->keytext == "tracking")
                {
                    $tracking = $k->value;
                }
                if ($k->keytext == "Inform")
                {
                    $Inform = $k->value;
                }
                if ($k->keytext == "no")
                {
                    $no = $k->value;
                }
            }
            $sftp = $this->checkFtpSettings($Path);

            if (!$sftp)
            {
                return false;
            }

            $retailerDetails = $this->getRetailerName();

            $nameRetailer = $retailerDetails["retailer"];
            $sftp->chdir($Path); // open directory 'test'
            $counter = 0;
            foreach ($sftp->nlist() as $filename)
            {
                $codesNames = explode(".xml", $filename);

                if (count($codesNames) > 0)
                {
                    if ($filename == "." || $filename == "..")
                    {
                        continue;
                    }

                    //$sftp->get($sftp->pwd() . '/' . $filename,ECS_DATA_PATH."/".$filename);
                    //if(file_exists(ECS_DATA_PATH."/".$filename) && filesize(ECS_DATA_PATH."/".$filename) > 0) {
                    //  $xml = simplexml_load_file(ECS_DATA_PATH."/".$filename, 'SimpleXMLElement', LIBXML_NOWARNING);
                    $counter++;
                    if ($counter > $no)
                    {
                        break; // Exit the loop if $no files have been processed

                    }

                    $shipmentFileData = $sftp->get($sftp->pwd() . "/" . $filename);
                    if ($shipmentFileData)
                    {
                        $xml = simplexml_load_string($shipmentFileData, "SimpleXMLElement", LIBXML_NOWARNING);

                        $deleteFile = false;
                        $validate = true;
                        $inventory_errors = [];
                        $xmlRetailname = (string)$xml->retailerName;

                        if (strcmp(trim($xmlRetailname) , trim($nameRetailer)) == 0)
                        {
                            $validate = true;
                        }
                        else
                        {
                            $validate = false;

                            array_push($inventory_errors, "The retailer name from the shipment message and the system configuration do not match");
                        }

                        $shippedOrders_ids = "";
                        $shipmentProcessOrders = [];

                        foreach ($xml->orderStatus as $stock)
                        {
                            $orderid = $stock->orderNo;
                            $intOrder = (int)$orderid;
                            if (false === get_post_status((int)$stock->orderNo))
                            {
                                $validate = false;
                                array_push($inventory_errors, "Order  ID :" . $stock->orderNo . "  is not found for the shipment");
                                continue; // Skip further check

                            }

                            $processedFiles = get_post_meta($intOrder, "shipmentFiles", true);
                            $order = new WC_Order((int)$intOrder);
                            $processedFiles = !empty($processedFiles) ? $processedFiles :  $order->get_meta('shipmentFiles');
                            if (!empty($processedFiles))
                            {
                                $processedFilesArray = json_decode($processedFiles);

                                if (is_array($processedFilesArray))
                                {
                                    if (in_array($filename, $processedFilesArray))
                                    {
                                        $deleteFile = true;

                                        array_push($inventory_errors, "Shipment File :" . $filename . "  was already processed");
                                        continue;
                                    }
                                }
                            }
                            else
                            {
                                $processedFilesArray = [];
                            }

                            $shipmentProcessOrders[] = $intOrder;
                            $countElement = 0;

                            foreach ($stock->orderStatusLines as $pruduct2)
                            {
                                foreach ($pruduct2 as $pruduct)
                                {
                                    $countElement = $countElement + 1;
                                }
                            }

                            foreach ($stock->orderStatusLines as $pruduct1)
                            {
                                foreach ($pruduct1 as $pruduct)
                                {
                                    $shippedOrders_ids .= $pruduct->itemNo . ":";
                                    $order = new WC_Order((int)$orderid);
                                    $items = $order->get_items("line_item");
                                    $productExist = "0";

                                    foreach ($items as $item)
                                    {
                                        $product = $item->get_product();
                                        if (strlen($item["product_id"]) > 0)
                                        {
                                        }

                                        if ($product->get_sku() == $pruduct->itemNo)
                                        {
                                            $productExist = "1";
                                        }
                                    }

                                    if ($productExist == "0")
                                    {
                                        $validate = false;
                                        array_push($inventory_errors, "Product  ID :" . $pruduct->itemNo . "   is not found for the shipment");
                                    }
                                }
                            }
                        }

                        if ($validate == true && count($shipmentProcessOrders) > 0)
                        {
                            $ship_Orders = [];

                            foreach ($xml->orderStatus as $stock)
                            {
                                $orderid = $stock->orderNo;
                                $traclCode = $stock->trackAndTraceCode;
                                $intOrder = (int)$orderid;
                                $stringTrack = (string)$traclCode;

                                ///check if everything is shipped
                                $order = new WC_Order((int)$orderid);
                                $items = $order->get_items("line_item");
                                $ordExportedItems = 0;
                                $countElement = 0;
                                $order = wc_get_order($intOrder);

                                foreach ($items as $orderlineItem)
                                {
                                    $lineItemProduct = $orderlineItem->get_product();

                                    if ($lineItemProduct->is_virtual())
                                    {
                                        continue;
                                    }

                                    if ($lineItemProduct->is_downloadable("yes"))
                                    {
                                        continue;
                                    }

                                    $ordExportedItems = $ordExportedItems + 1;
                                }

                                foreach ($stock->orderStatusLines as $pruduct2)
                                {
                                    foreach ($pruduct2 as $pruduct)
                                    {
                                        $countElement = $countElement + 1;
                                    }
                                }

                                $existingtrackCode = get_post_meta($intOrder, "trackAndTraceCode", true);
                                $existingtrackCode  = !empty($existingtrackCode) ? $existingtrackCode : $order->get_meta('trackAndTraceCode');

                                if(!empty( $existingtrackCode)){
                                    $stringTrack = $stringTrack . ", " . $existingtrackCode;
                                    $order->update_meta_data( "trackAndTraceCode", $stringTrack);
                                }
                                else{
                                    $order->add_meta_data( "trackAndTraceCode", $stringTrack);
                                }


                                //Mark Oorder as Completed
                                if ($countElement == $ordExportedItems)
                                {
                                    $order->update_status("completed");
                                }
                                else
                                {
                                    $exportedItems = get_post_meta($intOrder, "exportedItems", true);
                                    $exportedItems  = !empty($exportedItems) ? $exportedItems : $order->get_meta('exportedItems');
                                    if (strlen($exportedItems) !== 0)
                                    {
                                        $itemsExported = explode(":", $exportedItems);
                                        $itemsExportedNewly = explode(":", $shippedOrders_ids);
                                        $totalItems = count($itemsExported) + count($itemsExportedNewly) - 2;

                                        if ($totalItems == $ordExportedItems)
                                        {
                                            $order->update_status("completed");
                                        }
                                        else
                                        {
                                            $newExported = $exportedItems . " " . $shippedOrders_ids;
                                              $order->update_meta_data("exportedItems", $newExported);
                                        }
                                    }
                                    else
                                    {
                                        $order->add_meta_data( "exportedItems", $shippedOrders_ids);
                                    }
                                }

                                //End Completed Order marking
                                //Mark File as processed
                                $processedFilesArray[] = $filename;
                                $processedFilesJson = json_encode($processedFilesArray);

                                if (count($processedFilesArray) > 1)
                                {
                                      $order->update_meta_data("shipmentFiles", $processedFilesJson);
                                }
                                else
                                {
                                    $order->add_meta_data( "shipmentFiles", $processedFilesJson);
                                }
                                $order->save();
                                //
                                array_push($ship_Orders, "Order  ID :" . $stock->orderNo . "  was successfully imported ");
                            }

                            $sftp->delete($sftp->pwd() . "/" . $filename);

                            //Capture processed Filename

                        }
                        else
                        {
                            if ($deleteFile)
                            {
                                $sftp->delete($sftp->pwd() . "/" . $filename);
                            }

                            $Errors = '
                            <!DOCTYPE html>
                            <html>
                            <body><p>';

                            $Errors .= "An error occurred processing file:" . $filename . "<br>";
                            $Errors .= "<b>Message:</b><br>";

                            foreach ($inventory_errors as $fails)
                            {
                                $this->log("An error occurred shipment processing file:" . $fails);
                                //error_log($fails);
                                $Errors .= $fails;
                                $Errors .= " <br>";
                            }

                            $Errors .= '</p></body>
                                </html>';

                            $this->sendErrorEmail($Errors, "Shipment");
                        }

                        if (file_exists(ECS_DATA_PATH . "/" . $filename))
                        {
                            unlink(ECS_DATA_PATH . "/" . $filename);
                        }
                    }
                }
            }
        }
        catch(\Exception $e)
        {
            $error_message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            $this->log($error_message);
        }
    }

    public function getRetailerName()
    {
        try
        {
            global $wpdb;
            $nameRetailer = "";
            $email = "";
            $table_name_ecs = $wpdb->prefix . "ecs";
            // find list of states in DB
            $qry = $wpdb->prepare("SELECT * FROM $table_name_ecs WHERE keytext = %s ORDER BY id DESC LIMIT 1", 'general');
            $states = $wpdb->get_results($qry);
            $settingID = "";
            foreach ($states as $k)
            {
                $settingID = $k->id;
            }
            // find list of states in DB
            $table_name = $wpdb->prefix . "ecsmeta";
            $qrymeta = $wpdb->prepare("SELECT * FROM $table_name WHERE settingid = %d", $settingID);
            $statesmeta = $wpdb->get_results($qrymeta);

            foreach ($statesmeta as $k)
            {
                if ($k->keytext == "Name")
                {
                    $nameRetailer = $k->value;
                }

                if ($k->keytext == "Email")
                {
                    $email = $k->value;
                }
            }

            return ["retailer" => $nameRetailer, "email" => $email, ];
        }
        catch(\Exception $e)
        {
            $error_message = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            $this->log($error_message);
        }
    }
}