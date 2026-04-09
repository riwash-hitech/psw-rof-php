<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService; 
use App\Models\PswClientLive\Local\LiveItemLocation;
use App\Models\PswClientLive\Local\LiveProductGenericMatrix;
use App\Models\PswClientLive\Local\LiveProductGenericVariation;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;

class ProductAssortmentService{
    //
    protected $api;
    protected $store;

    public function __construct(EAPIService $api, LiveWarehouseLocation $store){
        $this->api = $api;
        $this->store = $store;
    }

    public function productAssortment($req){
        
        // $isOk = false;

        // if($req->isfinal){
        //     $isOk = true;
        // }

        // if($isOk == false){
        //     die;
        // }

        // if($req->warehouse){
        $warehouses = LiveWarehouseLocation::where('productAssortment', 1)->limit(7)->get();

        if($warehouses->isEmpty()){
            LiveWarehouseLocation::where('productAssortment', 0)->update(["productAssortment" => 1]);
            die;
        }
        // }else{
        //     $warehouses = LiveWarehouseLocation::where('erplyAssortmentID', '>', 0)->where('productAssortment', 1)->limit(1)->get();
        // }
        // dd($warehouses);
        
        $bulkRequest = array();
        $updateProduct = array();

        foreach($warehouses as $w){
            
            $products = LiveItemLocation::
            join("newsystem_product_variation_live", "newsystem_product_variation_live.ERPLYSKU", "newsystem_item_locations_live.ERPLYSKU")
            ->where("newsystem_item_locations_live.warehouse", $w->LocationID)
            ->where("newsystem_item_locations_live.aPending", 1)
            ->where("newsystem_product_variation_live.erplyID",'>', 0)
            ->select(["newsystem_product_variation_live.erplyID as productID","newsystem_product_variation_live.ICSC as pICSC","newsystem_product_variation_live.WEBSKU as ppWebsku","newsystem_item_locations_live.*","newsystem_item_locations_live.id as locationItemID"])
            ->groupBy("newsystem_item_locations_live.ERPLYSKU","newsystem_item_locations_live.warehouse")
            ->limit(50)
            ->get();
             
            if(count($products) < 1){
                info("All Product Assortment Synced");
                LiveWarehouseLocation::where("id", $w->id)->update(["productAssortment" => 0]);
                // return response("All Product Assortment Synced");
            }else{
                $parentID = array();
            
                $PID = '';
                $lid = '';
                foreach($products as $key => $p){
                    $updateProduct[] = $p;
                    if($key < 100 && $p->productID != ''){
                        $pp = LiveProductMatrix::where("WEBSKU", $p->ppWebsku)->where("erplyID",">", 0)->first();
                        if($pp){
                            if(!in_array($pp->erplyID, $parentID)){
                                $parentID[] = $pp->erplyID;
                            }
                        }
                        $PID .= $p->productID.",";
                        // $lid .= $p->locationItemID.",";
                        // LiveItemLocation::where("id", $p->locationItemID)->update(["aPending" => 0]);
                    }
                }
                // dd($PID);
                foreach($parentID as $ppA){
                    $PID .= $ppA.",";
                }
                
            //    dd($parentID);
                $chk = substr($PID, -1);
                if($chk == ","){
                    $PID = substr($PID, 0, -1);
                }
                // info($PID);
                $param = array(
                    "sessionKey" => $this->api->client->sessionKey,
                    "clientCode" => $this->api->client->clientCode,
                    "requestName" => "addAssortmentProducts",
                    "productIDs" => $PID,
                    "assortmentID" => $w->erplyAssortmentID,
                    // "status" => $status
                );
                // dd($param)
                $bulkRequest[] = $param;
            }

            // info(count($products));

            //nwo getting parent product id
           
            
        }   

        // dd($bulkRequest);

        if(count($bulkRequest) < 1){
            info("All Product Assortment Synceed");
            return response("All Product Assortment Synceed");
        }

        $bulkRequest = json_encode($bulkRequest, true);
        // dd($bulkRequest);
        // die;

        $bulkP = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey
        );

        $res = $this->api->sendRequest($bulkRequest, $bulkP, 1, 0,0);

        if($res["status"]["errorCode"] == 0){
            foreach($updateProduct as $pp){
                LiveItemLocation::where("id", $pp["id"])->update(["aPending" => 0]);
            }
        }
        info("Product Assortment Synceed");
        // info($res);
        return response()->json($res); 


    }

    public function removeProductAssortment($req){

        //now removing product assortment if it is not associated whether PSW OR Academy

        /****************************** Product Assortment using Default store and Secondary Store *************************************************************/
        /*
            FLAGS  
            ASSORTMENTREMOVEPENDING = 1 PENDING DEFAULT LOCATION REMOVE
            ASSORTMENTREMOVEPENDING = 2 PENDING SECONDARY LOCATION REMOVE
            ASSORTMENTREMOVEPENDING = 3 BOTH LOCATIOM ASSORTMENT REMOVED
        */

        $warehouses = LiveWarehouseLocation::where('removePA', 1)->where("pendingProcess", 0)->where("erplyAssortmentID",'>', 0)->where("ENTITY", '<>', $this->api->client->ENTITY)->limit(7)->get();
        
        // dd($warehouses);


        if($warehouses->isEmpty()){
            LiveWarehouseLocation::where('ENTITY', '<>', $this->api->client->ENTITY)->update(["removePA" => 1]);
            info("All Product Assortment Removed and Again Updating Pending Remove Assorment Flag in Location Table...");
            return response("All Product Assortment Removed and Again Updating Pending Remove Assorment Flag in Location Table...");
            die;
        }
        
        
        $bulkRequest = array();
        $productIDs = array();
        $bulkIndex = 0;
        foreach($warehouses as $w){
            
            //FOR MATRIX DEFAULT STORE LOCATION
            $products = LiveProductMatrix::where("DefaultStore", $w->LocationID)
                        ->where("assortmentRemovePending", 1)
                        // ->where("erplyEnabled", 1)
                        ->where("erplyID",">", 0)
                        ->limit(30)
                        ->get();
            // dd($products);                        
            if($products->isEmpty()){
                //IF EMPTY THEN VARIATION PRODUCT ASSORTMNET DEFAULT STORE
                $products = LiveProductVariation::where("DefaultStore", $w->LocationID)
                        ->where("assortmentRemovePending", 1)
                        // ->where("erplyEnabled", 1)
                        ->where("erplyID",">", 0)
                        ->limit(30)
                        ->get();
            }

            //IF BOTH EMPTY NOW START FOR SECONDARY LOCATION ASSORTMENT
            //FOR MATRIX SECONDAY STORE LOCATION
            if($products->isEmpty()){
                $products = LiveProductMatrix::where("SecondaryStore", $w->LocationID)
                            ->where("assortmentRemovePending", 2)
                            // ->where("erplyEnabled", 1)
                            ->where("erplyID",">", 0)
                            ->limit(30)
                            ->get();
            }
            if($products->isEmpty()){
                //IF EMPTY THEN VARIATION PRODUCT ASSORTMNET SECONDARY STORE
                $products = LiveProductVariation::where("SecondaryStore", $w->LocationID)
                        ->where("assortmentRemovePending", 2)
                        // ->where("erplyEnabled", 1)
                        ->where("erplyID",">", 0)
                        ->limit(30)
                        ->get();
            }

            //IF PRODUCTS EMPTY THEN PRODUCT ASSORTMNET COMPLETED FOR THIS LOCATION AND UPDATE PRODUCTASSORTMENT FLAG TO 0
            if(count($products) < 1){
                info("All Non Associated Product Assortment Removed");
                LiveWarehouseLocation::where("id", $w->id)->update(["removePA" => 0]);
                // return response("All Product Assortment Synced");
            }else{ 
                //IF NOT EMPTY PREPARE FOR SYNC TO ERPLY 
                $PID = ''; 
                foreach($products as $key => $p){   
                    $PID .= $p->erplyID.","; 
                    $productIDs[$bulkIndex][] = $p->erplyID;
                }
                // dd($productIDs);
                $bulkIndex++;

                //removing comma from pids
                $chk = substr($PID, -1);
                if($chk == ","){
                    $PID = substr($PID, 0, -1);
                }
                // info($PID);
                $param = array(
                    "sessionKey" => $this->api->client->sessionKey,
                    "clientCode" => $this->api->client->clientCode,
                    "requestName" => "removeAssortmentProducts",
                    "productIDs" => $PID,
                    "assortmentID" => $w->erplyAssortmentID,
                    // "status" => $status
                );
                // dd($param)
                $bulkRequest[] = $param; 
            }   
        }   

        // dd($bulkRequest);

        if(count($bulkRequest) < 1){
            info("All Product Assortment Remove.");
            return response("All Product Assortment Remove");
        }

        $bulkRequest = json_encode($bulkRequest, true);
        // dd($bulkRequest);
        // die;

        $bulkP = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey
        );

        $res = $this->api->sendRequest($bulkRequest, $bulkP, 1, 0,0);
        // dd($res);
        if($res["status"]["errorCode"] == 0){
            foreach($productIDs as $key =>  $pids){
                // dd($pids);
                if($res["requests"][$key]["status"]["errorCode"] == 0){
                    foreach($pids as $pid){

                        $checkMatrix = LiveProductMatrix::where("erplyID", $pid)->first();
                        if($checkMatrix){
                            $checkMatrix->update(["assortmentRemovePending" => $checkMatrix->assortmentRemovePending + 1]);
                        }else{
                            $checkVariation = LiveProductVariation::where("erplyID", $pid)->first();
                            if($checkVariation){
                                $checkVariation->update(["assortmentRemovePending" => $checkVariation->assortmentRemovePending+1]);
                            }
                        }
                    }
                }

            }
        }
        info("Product Assortment Removing...");
        // info($res);
        return response()->json($res); 

    }


    public function genericAssortment($req){
        $isDebug = '';
        if($req->debug){
            $isDebug = 1;
        }
        $isAcademy = $this->api->flag;

        $datas = LiveProductGenericMatrix::where($isAcademy == true ? "erplyID" : "pswErplyID",">", 0)->where($isAcademy == true ? "aPending" : "pswAssortmentPending", 1)->limit(100)->get();

        if($datas->isEmpty()){
            $datas = LiveProductGenericVariation::where($isAcademy == true ? "erplyID" : "pswErplyID", ">", 0)->where($isAcademy == true ? "aPending" : "pswAssortmentPending", 1)->limit(100)->get();
        }

        if($isDebug == 1){
            dd($datas);
        }

        if($datas->isEmpty()){
            info("Generic Product Assortment Completed");
            return response("Generic Product Assortment Completed");
        }

        $PID = '';
        foreach($datas as $mp){
            $ppid = $isAcademy == true ? $mp->erplyID : $mp->pswErplyID;
            $PID .= $ppid.",";
        }

        $chk = substr($PID, -1);
        if($chk == ","){
            $PID = substr($PID, 0, -1);
        }


        $warehouses = LiveWarehouseLocation::where("pendingProcess", 0)->where("ENTITY", $this->api->client->ENTITY)->get();

        $bulk = array();
        foreach($warehouses as $wh){
            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "addAssortmentProducts",
                "productIDs" => $PID,
                // "productIDs" => 196883,
                "assortmentID" => $wh->erplyAssortmentID,
            );
            $bulk[] = $param;    
        }


        if(count($bulk) < 1){
            return response("No Product Found");
        }

        $bulk = json_encode($bulk, true);
        // dd($bulkRequest);
        // die;

        $bulkP = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey
        );

        $res = $this->api->sendRequest($bulk, $bulkP, 1, 0,0);
        
        if($res["status"]["errorCode"] == 0){
            foreach($datas as $mp){

                if($isAcademy == true){
                    $mp->aPending = 0;
                    $mp->save();
                }
                if($isAcademy == false){
                    $mp->pswAssortmentPending = 0;
                    $mp->save();
                }

            }
        }

        return response()->json($res);

 
        
        
    }


    public function productAssortmentV2($req){
        $limit = $req->limit ? $req->limit : 95;
        $isDebug = '';
        if($req->debug){
            $isDebug = 1;
        }

        // info("API : ". $req->env." ".$req->entity);
        /****************************** Product Assortment using Default store and Secondary Store *************************************************************/
        /*
            FLAGS  
            ASSORTMENTPENDING = 1 DEFAULT STORE LOCATION PENDING
            ASSORTMENTPENDING = 2 SECONDARY STORE LOCATION PENDING
            ASSORTMENTPENDING = 3 BOTH ASSORTMENT DONE
        */

        $warehouses = LiveWarehouseLocation::where('productAssortment', 1)->where("ENTITY", $this->api->client->ENTITY)->limit(3)->get();

        if($warehouses->isEmpty()){
            LiveWarehouseLocation::where('productAssortment', 0)->where("ENTITY", $this->api->client->ENTITY)->update(["productAssortment" => 1]);
            info("All Product Assortment Completed and Again Updating Pending Assorment Flag in Location Table...");
            return response("All Product Assortment Completed and Again Updating Pending Assorment Flag in Location Table...");
            die;
        }
        
        // dd($warehouses);
        $bulkRequest = array();
        $productIDs = array();
        $bulkIndex = 0;
        foreach($warehouses as $w){
            
            //FOR MATRIX DEFAULT STORE LOCATION
            $products = LiveProductMatrix::where("DefaultStore", $w->LocationID)
                        ->where("assortmentPending", 1)
                        // ->where("erplyEnabled", 1)
                        ->where("erplyID",">", 0)
                        ->where("erplyPending", 0)
                        ->limit($limit)
                        ->get();
            // dd($products);                        
            if($products->isEmpty()){
                //IF EMPTY THEN VARIATION PRODUCT ASSORTMNET DEFAULT STORE
                $products = LiveProductVariation::where("DefaultStore", $w->LocationID)
                        ->where("assortmentPending", 1)
                        // ->where("erplyEnabled", 1)
                        ->where("erplyID",">", 0)
                        ->where("erplyPending", 0)
                        ->limit($limit)
                        ->get();
            }

            //IF BOTH EMPTY NOW START FOR SECONDARY LOCATION ASSORTMENT
            //FOR MATRIX SECONDAY STORE LOCATION
            if($products->isEmpty()){
                $products = LiveProductMatrix::where("SecondaryStore", $w->LocationID)
                            ->where("assortmentPending", 2)
                            // ->where("erplyEnabled", 1)
                            ->where("erplyID",">", 0)
                            ->where("erplyPending",">", 0)
                            ->limit($limit)
                            ->get();
            }
            if($products->isEmpty()){
                //IF EMPTY THEN VARIATION PRODUCT ASSORTMNET SECONDARY STORE
                $products = LiveProductVariation::where("SecondaryStore", $w->LocationID)
                        ->where("assortmentPending", 2)
                        // ->where("erplyEnabled", 1)
                        ->where("erplyID",">", 0)
                        ->where("erplyPending",">", 0)
                        ->limit($limit)
                        ->get();
            }

            // if($isDebug == 1){
            //     dd($products);
            // }

            //IF PRODUCTS EMPTY THEN PRODUCT ASSORTMNET COMPLETED FOR THIS LOCATION AND UPDATE PRODUCTASSORTMENT FLAG TO 0
            if(count($products) < 1){
                info("All Product Assortment Synced");
                LiveWarehouseLocation::where("id", $w->id)->update(["productAssortment" => 0]);
                // return response("All Product Assortment Synced");
            }else{ 
                //IF NOT EMPTY PREPARE FOR SYNC TO ERPLY 
                $PID = ''; 
                foreach($products as $key => $p){   
                    $PID .= $p->erplyID.","; 
                    $productIDs[$bulkIndex][] = $p;
                }
                // dd($productIDs);
                $bulkIndex++;

                //removing comma from pids
                $chk = substr($PID, -1);
                if($chk == ","){
                    $PID = substr($PID, 0, -1);
                }
                // info($PID);
                $param = array(
                    "sessionKey" => $this->api->client->sessionKey,
                    "clientCode" => $this->api->client->clientCode,
                    "requestName" => "addAssortmentProducts",
                    "productIDs" => $PID,
                    "assortmentID" => $w->erplyAssortmentID,
                    // "status" => $status
                );
                // dd($param)
                $bulkRequest[] = $param; 
            }   
        }   

        // dd($bulkRequest);

        if(count($bulkRequest) < 1){
            info("All Product Assortment Synceed");
            return response("All Product Assortment Synceed");
        }

        $bulkRequest = json_encode($bulkRequest, true);
        // dd($bulkRequest);
        // die;

        $bulkP = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey
        );

        $res = $this->api->sendRequest($bulkRequest, $bulkP, 1, 0,0);
        // dd($res);
        if($isDebug == 1){
            dd($res);
        }
        if($res["status"]["errorCode"] == 0){
            foreach($productIDs as $key =>  $pids){
                // dd($pids);
                if($res["requests"][$key]["status"]["errorCode"] == 0){
                    foreach($pids as $pid){

                        $checkMatrix = LiveProductMatrix::where("WEBSKU", $pid["WEBSKU"])->where("erplyID", $pid["erplyID"])->first();
                        if($checkMatrix){
                            $checkMatrix->update(["assortmentPending" => $checkMatrix->assortmentPending == 1 ? 2 : 3]);
                        }else{
                            $checkVariation = LiveProductVariation::where("ERPLYSKU", $pid["ERPLYSKU"])->where("erplyID", $pid["erplyID"])->first();
                            if($checkVariation){
                                $checkVariation->update(["assortmentPending" => $checkVariation->assortmentPending == 1 ? 2 : 3]);
                            }
                        }
                    }
                }

            }
        }
        // $title = $isAcademy == true ? "Academy" : "PSW";
        info($this->api->client->ENTITY. " Product Assortment Syncing...");
        // info($res);
        return response()->json($res); 
        
    }

     
}
