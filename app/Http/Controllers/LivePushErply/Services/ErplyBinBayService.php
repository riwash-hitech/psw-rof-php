<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\InventoryRegistration;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveOnHandInventory;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;
use App\Traits\IsFinalTrait;

class ErplyBinBayService{
    //

    use IsFinalTrait;
    protected $api;
    // protected $customer;

    public function __construct(EAPIService $api){
        $this->api = $api;
       
    }

    public function syncBinBayLocations(){
        $isAcademy = $this->api->flag;
        //first updating bin id if already pushed
        // $warehouse = LiveWarehouseLocation::where("ENTITY", $this->api->client->ENTITY)->pluck("Location")

        $binbays = LiveOnHandInventory::join("newstystem_store_location_live","newstystem_store_location_live.LocationID", "newsystem_on_hand_inventory.Warehouse")
                    ->where("newsystem_on_hand_inventory.pendingProcess", 1)
                    ->where("newstystem_store_location_live.ENTITY", $this->api->client->ENTITY)
                    ->groupBy("newsystem_on_hand_inventory.Location","newsystem_on_hand_inventory.Warehouse")
                    ->select(["newsystem_on_hand_inventory.*", "newstystem_store_location_live.erplyID as warehouseErplyID"])
                    ->limit(100)
                    ->get();
        // dd($binbays);



        if($binbays->isEmpty()){
            $p2 = LiveOnHandInventory::where("pendingProcess", 2)->first();
            if($p2){
                LiveOnHandInventory::where("pendingProcess", 2)->update(["pendingProcess" => 1]);
            }
            // info("Synccare to Erply : All Binbay Locations Synced");
            return response("Synccare to Erply : All Binbay Locations Synced");
        }

        //first get bin bulk
        $getBulk = $this->getBulkBin($binbays);
        
        // dd($getBulk);
        $bulkReq = array();
        foreach($binbays as $key => $bin){

            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "saveBin",
                "warehouseID" => $bin->warehouseErplyID,
                "code" => $bin->Location,
            );

            // $binID = $this->getBin($bin->warehouse->erplyID, $bin->Location);
            // if($binID != ''){
            //     $param["binID"] = $binID;
            // }

            if($getBulk["requests"][$key]["status"]["errorCode"] == 0 && !empty($getBulk["requests"][$key]["records"])){
                $param["binID"] = $getBulk["requests"][$key]["records"][0]["binID"];
            }
            
            $bulkReq[] = $param;
        }

        // dd($bulkReq);

        if(count($bulkReq) < 1){
            //if not found than update binbaypending 0
            // LiveWarehouseLocation::where("id", $warehouses->id)->update(["binbayPending" => 0]);
            info("Synccare to Erply :  Binbay Location Synced");
            return response("No Bin Bay Location Found.");
        }

        $bulkP = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey
        );

        $bulkReq = json_encode($bulkReq, true);

        $res = $this->api->sendRequest($bulkReq, $bulkP, 1);
        // dd($res);
        if($res["status"]["errorCode"] == 0 && !empty($res["requests"])){

            foreach($binbays as $key => $bin){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    LiveOnHandInventory::where("Warehouse", $bin->Warehouse)->where("Location", $bin->Location)->update(["binbayID" => $res['requests'][$key]["records"][0]["binID"], "pendingProcess" => 0]);
                }else{
                    LiveOnHandInventory::where("Warehouse", $bin->Warehouse)->where("Location", $bin->Location)->update(["pendingProcess" => 2]);
                    info("Error while saving Bin Bay ".$res['requests'][$key]['status']['errorCode'] ." location ".$bin->Location." warehosue ".$bin->Warehouse);
                    info($res['requests'][$key]["records"]);
                }
            }
        }
        info("Synccare to Erply : Binbay Location Syncing");
        return response()->json($res);
         
    }


    public function saveBinRecords($req){
        // info("Save Bin Records cron die ***********");
        // $this->isFinal($req, "Save Bin Records");

        
        $creatorID = 0;
        if(env("isLive") == false){
            $creatorID = 2;
        }

        if(env("isLive") == true){
            $creatorID = 7;
            if($this->api->client->ENTITY == "PSW"){
                $creatorID = 6;
            }
        }

        
        // dd($this->api->getLocationID());
        //for non generic product
        $binbays = LiveOnHandInventory:: 
                    join("newsystem_product_variation_live", "newsystem_product_variation_live.ERPLYSKU", "newsystem_on_hand_inventory.ERPLYSKU") 
                    ->whereNotIn("newsystem_on_hand_inventory.Configuration",['0',''])
                    ->where("newsystem_on_hand_inventory.pendingProcess", 0)
                    ->where("newsystem_on_hand_inventory.AvailablePhysical", '<>', 0)
                    ->whereIn("newsystem_on_hand_inventory.Warehouse", $this->api->getLocationID())
                    // ->whereIn("newsystem_product_variation_live.DefaultStore", $this->api->getLocationID())
                    ->where(function ($query) {
                        $query->whereIn("newsystem_product_variation_live.DefaultStore", $this->api->getLocationID())
                              ->orWhereIn("newsystem_product_variation_live.SecondaryStore", $this->api->getLocationID());
                    })
                    ->where("newsystem_product_variation_live.erplyID",">", 0)
                    ->where("newsystem_on_hand_inventory.binSOHPending", 1)
                    ->select(["newsystem_product_variation_live.erplyID as productID", "newsystem_on_hand_inventory.*"])
                    ->limit(300)
                    ->get();
       
        $chunkBinbay = $binbays->chunk(100);
        // dd($binbays);

        if(count($binbays) < 1){
            // LiveWarehouseLocation::where("id", $warehouses->id)->update(["binbaySOHPending" => 0]);
            // info("Synccare to Erply : All Binbay SOH Synced");
            return response("Binbay SOH Synced");
        }
        // dd($binbays);
        $param = array(
            "sessionKey" => $this->api->client->sessionKey,
            // "clientCode" => $this->api->client->clientCode,
            // "requestName" => "saveBinRecords",
            
        );

        $bulk = array();

        foreach($chunkBinbay as $bins){

            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "saveBinRecords",
                
            );
             
            $count = 1; 
            foreach($bins as $key => $bin){
                $param["binID".$count] = $bin->binbayID;
                $param["productID".$count] = $bin->productID;
                $param["amount".$count] = $bin->AvailablePhysical;
                $param["documentType".$count] = "INVENTORY_REGISTRATION";
                $param["creatorID".$count] = $creatorID;
                $count++;
                 
            }

            $bulk[] = $param;
 
        }

        // dd($bulk);

        if(count($bulk) < 1){
            //if not found than update binbaypending 0
            // LiveWarehouseLocation::where("id", $warehouses->id)->update(["binbayPending" => 0]);
            return response("No Bin Bay SOH Found.");
        }
 
        // $bulkReq = json_encode("saveBinRecords",$param, true);
        $bulk = json_encode($bulk, true);

        $paramBulk = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        $res = $this->api->sendRequest($bulk, $paramBulk,1);
        // $res = $this->api->sendRequest("saveBinRecords", $param);
        // dd($res);

        if($res["status"]["errorCode"] == 0 && !empty($res['requests'])){

            foreach($chunkBinbay as $key => $bins){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    foreach($bins as $bin){

                        $bin->binSOHPending = 0;
                        $bin->binSOHAdjust = 1;
                        $bin->save();

                    }
                }
            }

        }

        // if($res["status"]["errorCode"] == 0){
        //     foreach($binbays as $bin){
        //         $bin->binSOHPending = 0;
        //         $bin->save();
        //     }
        // }
        info("Synccare to Erply : Binbay SOH Syncing");
        return response()->json($res);
    }


    public function adjustBinRecord($req){
        // $this->isFinal($req, "Save Bin Records");
        $binbays = LiveOnHandInventory:: 
                    join("newsystem_product_variation_live", "newsystem_product_variation_live.ERPLYSKU", "newsystem_on_hand_inventory.ERPLYSKU") 
                    ->whereNotIn("newsystem_on_hand_inventory.Configuration",['0',''])
                    ->where("newsystem_on_hand_inventory.pendingProcess", 0)
                    ->where("newsystem_on_hand_inventory.AvailablePhysical", '<>', 0)
                    ->whereIn("newsystem_on_hand_inventory.Warehouse", $this->api->getLocationID())
                    // ->whereIn("newsystem_product_variation_live.DefaultStore", $this->api->getLocationID())
                    ->where(function ($query) {
                        $query->whereIn("newsystem_product_variation_live.DefaultStore", $this->api->getLocationID())
                              ->orWhereIn("newsystem_product_variation_live.SecondaryStore", $this->api->getLocationID());
                    })
                    ->where("newsystem_product_variation_live.erplyID",">", 0)
                    ->where("newsystem_on_hand_inventory.binSOHPending", 0)
                    ->where("newsystem_on_hand_inventory.binSOHAdjust", 1)
                    ->select(["newsystem_product_variation_live.erplyID as productID", "newsystem_on_hand_inventory.*"])
                    ->limit(300)
                    ->get();

        // $binbays = LiveOnHandInventory::join("newstystem_store_location_live","newstystem_store_location_live.LocationID", "newsystem_on_hand_inventory.Warehouse")
        //             ->where("newstystem_store_location_live.ENTITY", $this->api->client->ENTITY)
        //             ->join("newsystem_product_variation_live", "newsystem_product_variation_live.ERPLYSKU", "newsystem_on_hand_inventory.ERPLYSKU")
        //             // ->whereHas("warehouse")
        //             // ->with("warehouse")
        //             // ->where("newsystem_on_hand_inventory.Warehouse", $warehouses->LocationID)
        //             ->where("newsystem_on_hand_inventory.binSOHPending", 0)
        //             ->where("newsystem_on_hand_inventory.binSOHAdjust", 1)
        //             ->whereNotIn("newsystem_on_hand_inventory.Configuration", ['0',''])
        //             ->where("newsystem_on_hand_inventory.pendingProcess", 0)
        //             // ->where("newsystem_on_hand_inventory.AvailablePhysical", '>', 0)
        //             // ->where("newsystem_on_hand_inventory.Warehouse",'3R400')
        //             ->where("newsystem_product_variation_live.erplyID",">", 0)
        //             ->select(["newsystem_product_variation_live.erplyID as productID", "newsystem_on_hand_inventory.*"])
        //             ->limit(300)
        //             ->get();
        
        //for generic product

        // dd($binbays);

        if(count($binbays) < 1){
            // LiveWarehouseLocation::where("id", $warehouses->id)->update(["binbaySOHPending" => 0]);
            info("Synccare to Erply : All Binbay SOH Qty Adjust Synced");
            return response("Binbay SOH Adjust Synced");
        }
        // dd($binbays);
        

        $chunkBinbay = $binbays->chunk(100);
        // dd($chunkBinbay);
        $bulk = array();

        foreach($chunkBinbay  as $bins){
            // dd($bins);
            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "adjustBinQuantities",
                
            );
            $index = 0;
            foreach($bins as $key => $bin){
             
                $param["binID".$key+1] = $bin->binbayID;
                $param["productID".$key+1] = $bin->productID;
                $param["newAmount".$key+1] = $bin->AvailablePhysical;
                // $param["documentType".$key+1] = "INVENTORY_REGISTRATION";
                $param["creatorID".$key+1] = env('isLive') == true ? 2 : 2;
                $index++;
            }
             
            $bulk[] = $param;
 
        }

        // dd($bulk);

        if(count($bulk) < 1){
            //if not found than update binbaypending 0
            // LiveWarehouseLocation::where("id", $warehouses->id)->update(["binbayPending" => 0]);
            return response("No Bin Bay Location Found.");
        }
 
        $bulk = json_encode($bulk, true);

        $paramBulk = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        $res = $this->api->sendRequest($bulk, $paramBulk,1);
        // dd($res);
        if($res["status"]["errorCode"] == 0 && !empty($res['requests'])){

            foreach($chunkBinbay as $key => $bins){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    foreach($bins as $bin){

                        $bin->binSOHAdjust = 0;
                        $bin->save();

                    }
                }
            }

        }
        info("Synccare to Erply : Binbay SOH Adjustment Syncing");
        return response()->json($res);

    }


    public function getBin($wid, $code){

        $param = array( 
            "sessionKey" => $this->api->client->sessionKey,
            "warehouseID" => $wid,
            "code" => $code
        );

        $res = $this->api->sendRequest("getBins", $param);

        if($res["status"]["errorCode"] == 0 && !empty($res['records'])){
            return $res['records'][0]['binID'];
        }
        return '';
    }

    public function getBulkBin($binbay){

        $getBulk = array();

        foreach($binbay as $bin){
            $param = array( 
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "warehouseID" => $bin->warehouse->erplyID,
                "code" => $bin->Location,
                "requestName" => "getBins"
            );
           
            $getBulk[] = $param;
        }

        if(count($getBulk) < 1){
            info("Not Bin Location Found.");
            die;
        }
        $bparam = array(
            "sessionKey" => $this->api->client->sessionKey
        );

        $getBulk = json_encode($getBulk, true);

        $res = $this->api->sendRequest($getBulk, $bparam, 1);

        if($res["status"]["errorCode"] != 0){
            info("Error While getting bin from Erply...");
            die;
        }

        return $res;
        
        
    }




    

 
 
}
