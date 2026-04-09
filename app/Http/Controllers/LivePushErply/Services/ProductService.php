<?php

namespace App\Http\Controllers\LivePushErply\Services;
 
use Illuminate\Http\Request;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\{LiveProductCategory, LiveProductDescription, LiveProductGenericVariation, LiveProductGroup, LiveProductMatrix, LiveProductVariation, LiveWarehouseLocation};

class ProductService{
    //
    protected $api;
    protected $matrix;
    protected $variation;
    protected $group;
    protected $category;

    public function __construct(EAPIService $api, LiveProductMatrix $matrix, LiveProductVariation $variation, LiveProductGroup $group, LiveProductCategory $category){
        $this->api = $api;
        $this->matrix = $matrix;
        $this->variation = $variation;
        $this->group = $group;
        $this->category = $category;
       
    }

    public function syncMatrixProduct($req){
        return $this->syncMatrixProductV2($req);
        die;
        //GET WAREHOUSE ACCORDING TO ENTITY PSW OR ACADEMY

        $limit = $req->limit ? $req->limit : 100;
        if($req->sku){
            $matrix = $this->matrix
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType")
                    ->join("newstystem_store_location_live", function($q){
                        $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                        ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    })
                    ->where("newstystem_store_location_live.ENTITY",  $this->api->client->ENTITY)
                    ->where('WEBSKU', $req->sku)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID"])
                    ->get();
        }else{
            $matrix = $this->matrix
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType") 
                    ->join("newstystem_store_location_live", function($q){
                        $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                        ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    })
                    ->where("newstystem_store_location_live.ENTITY", $this->api->client->ENTITY)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    ->where('newsystem_product_matrix_live.erplyPending', 1)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    // ->where('newsystem_product_matrix_live.mUpdate', 1)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID"])
                    ->limit($limit)
                    ->get();
        }

        // dd($matrix);

        if($matrix->isEmpty()){
            info("Synccare to Erply : All Matrix Product Synced.");
            return response()->json(["status" => "success", "message" => "Synccare to Erply : All Matrix Product Synced."]);
        }

        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        );

        //first get bulk 
        $getBulkReq = array();
        foreach($matrix as $mp){
            $checkParam = array(
                "requestName" => "getProducts",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "code" =>  $mp->WEBSKU
            );
            $getBulkReq[] = $checkParam;
        }

        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);

        if($getBulkRes["status"]["errorCode"] != 0){
            info("Error While getting products by code ".$getBulkRes["status"]["errorCode"]);
            return response("Error While getting products by code ".$getBulkRes["status"]["errorCode"]);
        }

        $bundleReq = array();
        foreach($matrix as $mkey => $mp){
            // $v = $this->variation->where('web_sku', $mp->web_sku)->where('newSystemInternetActive', 1)->first();
            if($getBulkRes["requests"][$mkey]["status"]["errorCode"] == 0){ 
                
                // $pid =  $this->productCheck($mp->WEBSKU);
                $param = array(
                    "requestName" => "saveProduct",
                    "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                    "clientCode" => $this->api->client->clientCode,
                    "type" => "MATRIX", 
                    "code" => $mp->WEBSKU,
                    "code2" => "",
                    "code3" => $mp->ConfigName,
                    "code5" => $mp->PLMStatus,
                    "code6" => $mp->ProductSubType,
                    "code7" => $mp->Category_Name,
                    "code8" => $mp->WebEnabled == 1 ? 'Y' : 'N',
                    "code9" => $mp->Category_Name,
                    "displayedInWebshop" => $mp->WebEnabled,
                    "active" => $mp->erplyEnabled,
                    "status" => $mp->erplyEnabled == 1 ? "ACTIVE" : 'ARCHIVED',
                    "name" => $mp->ItemName,
                    // "description" => $mp->newSystemShortDescription != '' ? $mp->newSystemShortDescription : $mp->newSystemStockDescription,
                    // "longdesc" => $mp->newSystemLongDescription,
                    "groupID" => $mp->erplyGroupID,
                    "categoryID" => $mp->erplyCatID,
                    "priceWithVAT" => $mp->RetailSalesPrice,
                    // "length" => ,
                    // "width" => ,
                    // "height" => ,
                    // "netWeight" => (double)$mp->ItemWeightGrams/1000,
                    // "sessionKey" => $this->api->client->sessionKey,
                    "dimensionID1" => 1,
                    "dimensionID2" => 8, 
                    "attributeName1" => "GenericProduct", 
                    "attributeType1" => "text", 
                    "attributeValue1" => $mp->genericProduct, 

                );

                $des = LiveProductDescription::where('WEBSKU', $mp->WEBSKU)->first();
                if($des){
                    $param["longdesc"] = $des->LongDescription;
                }
    

                if(!empty($getBulkRes["requests"][$mkey]["records"])){
                    //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    $param['productID'] = $getBulkRes['requests'][$mkey]['records'][0]['productID'];
                }
                //adding attributes\
                // $index = 1;
                // foreach($mp->toArray() as $key => $val){
                //     if($key == 'SOFStatus' || $key == "DefaultStore" || $key == "SecondaryStore" || $key == "ITEMID" || $key == "ColourID" || $key == "SizeID" || $key == "CONFIGID" || $key == "SchoolID"){
                //         $param["attributeName".$index] = $key;
                //         $param["attributeType".$index] =  'text';
                //         $param["attributeValue".$index] = $val;
                //         $index++;
                //     }
                // }
                array_push($bundleReq, $param);
            }
        }
        // dd($bundleReq);
        if(count($bundleReq) < 1){
            return response()->json(["message" => "No Product Found"]);
        }
        // info($bundleReq);

        $matrixReq = $bundleReq;

        $bundleReq = json_encode($bundleReq,true);
        

        info("Matrix Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($matrixReq as $key => $mp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){
                    LiveProductMatrix::where("WEBSKU", $mp["code"])->update(
                        [
                            "erplyPending" => 0,
                            "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                        ]
                    );
                    // $mp->erplyPending = 0; 
                    // $mp->erplyID = $bulkRes['requests'][$key]['records'][0]['productID'];
                    // $mp->save();
                    info("matrix product create or updated ".$bulkRes['requests'][$key]['records'][0]['productID']." sku ".$mp['code']);
                }else{
                    LiveProductMatrix::where("WEBSKU", $mp["code"])->update(
                        [
                            "erplyPending" => 2,
                            // "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                        ]
                    );
                     
                    info("Error matrix product create or updated ".$bulkRes['requests'][$key]['status']['errorCode'].' SKU '. $mp['code']);
                }
            }
        }

        return response()->json(['status' => 'success', 'response' => $bulkRes]);
    }

    public function syncMatrixProductV2($req){ 

        $isDebug = '';
        if($req->debug){
            $isDebug = $req->debug;
        }
        //GET WAREHOUSE ACCORDING TO ENTITY PSW OR ACADEMY
        $isAcademy = $this->api->flag;

        if(env('isLive') == true){
            $checkCron = false;
            if(@$req->isfinal == true){
                $checkCron = true;
            }
            if($checkCron == false){
                // info("*******************************Matrix Update Cron Blocked ********************");
                die;
            }
        }

        $isFinalLiveEnv = $this->api->isLiveEnv();

        $limit = $req->limit ? $req->limit : 100;
        if($req->sku){
            $matrix = $this->matrix
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType")
                    ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID" ,"newsystem_product_matrix_live.DefaultStore")
                    // ->join("newstystem_store_location_live", function($q){
                    //     $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                    //     ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    // })
                    ->where("newstystem_store_location_live.ENTITY",  $this->api->client->ENTITY)
                    ->where('WEBSKU', $req->sku)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_group_live.pendingProcess', 0)
                    ->where( $isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID","newsystem_product_category_live.pswCatID"])
                    ->get();
        }else{
            $matrix = $this->matrix
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType") 
                    ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID" ,"newsystem_product_matrix_live.DefaultStore")
                    // ->join("newstystem_store_location_live", function($q){
                    //     $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                    //     ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    // })
                    ->where("newstystem_store_location_live.ENTITY", $this->api->client->ENTITY)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_group_live.pendingProcess', 0)
                    ->where($isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->where('newsystem_product_matrix_live.erplyPending', 1)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    // ->where('newsystem_product_matrix_live.mUpdate', 1)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID","newsystem_product_category_live.pswCatID"])
                    ->limit($limit)
                    ->orderBy("updated_at", "asc")
                    ->get();
        } 
        if($isDebug == 1){
            dd($matrix);
        } 

        if($matrix->isEmpty()){ 
            //checking erply pending 2
            $wh = LiveWarehouseLocation::where("ENTITY", $this->api->client->ENTITY)->pluck("LocationID")->toArray();
            $chkp2 = LiveProductMatrix::where("erplyPending", 2)->whereIn("DefaultStore", $wh)->first();
            if($chkp2){
                LiveProductMatrix::where("erplyPending", 2)->whereIn("DefaultStore", $wh)->update(["erplyPending" => 1]);
            } 
            //if erplyid null and erplypending 0 then update 
            LiveProductMatrix::whereNull("erplyID")->where("erplyPending", 0)->update(["erplyPending" => 1, "variationPending" => 1]); 
            return response()->json(["status" => "success", "message" => "Synccare to Erply : All Matrix Product Synced."]);
        }

        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        );

        //first get bulk 
        $getBulkReq = array();
        foreach($matrix as $mp){
            $mp->updated_at = date("Y-m-d H:i:s");
            $mp->save();
            $checkParam = array(
                "requestName" => "getProducts",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "code" =>  $mp->WEBSKU
            );
            $getBulkReq[] = $checkParam;
        }

        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);

        if($getBulkRes["status"]["errorCode"] != 0){
            info("Error While getting products by code ".$getBulkRes["status"]["errorCode"]);
            return response("Error While getting products by code ".$getBulkRes["status"]["errorCode"]);
        }

        $bundleReq = array();
        foreach($matrix as $mkey => $mp){
            // $v = $this->variation->where('web_sku', $mp->web_sku)->where('newSystemInternetActive', 1)->first();
            if($getBulkRes["requests"][$mkey]["status"]["errorCode"] == 0){ 
                
                // $pid =  $this->productCheck($mp->WEBSKU);
                $param = array(
                    "requestName" => "saveProduct",
                    "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                    "clientCode" => $this->api->client->clientCode,
                    "type" => "MATRIX", 
                    "code" => $mp->WEBSKU,
                    "code2" => "",
                    "code3" => $mp->ConfigName,
                    "code5" => $mp->PLMStatus,
                    "code6" => $mp->ProductSubType,
                    "code7" => $mp->Category_Name,
                    "code8" => $mp->WebEnabled == 1 ? 'Y' : 'N',
                    "code9" => $mp->Category_Name,
                    "displayedInWebshop" => $mp->WebEnabled,
                    "active" => $isFinalLiveEnv == 0 ? 1 : $mp->erplyEnabled,
                    "status" => $isFinalLiveEnv == 0 ? "ACTIVE" : ($mp->erplyEnabled == 1 ? "ACTIVE" : 'ARCHIVED'),
                    "name" => $mp->ItemName,
                    // "description" => $mp->newSystemShortDescription != '' ? $mp->newSystemShortDescription : $mp->newSystemStockDescription,
                    // "longdesc" => $mp->newSystemLongDescription,
                    "groupID" => $mp->erplyGroupID,
                    "categoryID" => $isAcademy == true ? $mp->erplyCatID : $mp->pswCatID,
                    "priceWithVAT" => $mp->RetailSalesPrice,
                    // "length" => ,
                    // "width" => ,
                    // "height" => ,
                    // "netWeight" => (double)$mp->ItemWeightGrams/1000,
                    // "sessionKey" => $this->api->client->sessionKey,
                    "dimensionID1" => 1,
                    "dimensionID2" => 8, 
                    // "attributeName1" => "GenericProduct", 
                    // "attributeType1" => "text", 
                    // "attributeValue1" => $mp->genericProduct, 

                );

                $des = LiveProductDescription::where('WEBSKU', $mp->WEBSKU)->first();
                if($des){
                    $param["longdesc"] = $des->LongDescription;
                }
    

                if(!empty($getBulkRes["requests"][$mkey]["records"])){
                    //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    $param['productID'] = $getBulkRes['requests'][$mkey]['records'][0]['productID'];
                }
                //adding attributes\
                $index = 1;
                foreach($mp->toArray() as $key => $val){
                    if($key == "genericProduct" || $key == "customItemName" || $key == "receiptDescription"){
                        if($val != '' && is_null($val) == false){
                            $param["attributeName".$index] = $key == "receiptDescription" ? "Receipt_Product_Description" : ($key == "customItemName" ? "Receipt_Product_Name" : "GenericProduct");
                            $param["attributeType".$index] =  'text';
                            $param["attributeValue".$index] = $key == "receiptDescription" ? str_replace("'", "", $mp->SchoolName .' '. $mp->ItemName) : $val;
                            $index++;
                        }
                    }
                }
                array_push($bundleReq, $param);
            }
        } 
        if(count($bundleReq) < 1){
            return response()->json(["message" => "No Product Found"]);
        } 

        if($isDebug == 2){
            dd($bundleReq);
        }

        $matrixReq = $bundleReq; 
        $bundleReq = json_encode($bundleReq,true);
        

        info("Matrix Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($matrixReq as $key => $mp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){

                    $updateDetails = array(
                        "erplyPending" => 0,
                        "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID'],
                        "erplyError" => ''
                    );

                    // if(env("isLive") == false){
                    //     $updateDetails["variationPending"] = 1;
                    // }

                    LiveProductMatrix::where("WEBSKU", $mp["code"])->update(
                        $updateDetails
                    );
                    // $mp->erplyPending = 0; 
                    // $mp->erplyID = $bulkRes['requests'][$key]['records'][0]['productID'];
                    // $mp->save();
                    // info("matrix product create or updated ".$bulkRes['requests'][$key]['records'][0]['productID']." sku ".$mp['code']);
                }else{

                    if($bulkRes['requests'][$key]['status']['errorField'] == "groupID"){
                        //now set group pending
                        LiveProductGroup::where("erplyGroupID", $mp["groupID"])->update(["pendingProcess" => 1]);
                    }

                    LiveProductMatrix::where("WEBSKU", $mp["code"])->update(
                        [
                            "erplyPending" => 2,
                            // "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                            "erplyError" => json_encode($bulkRes['requests'][$key], true)
                        ]
                    );
                     
                    info("Error matrix product create or updated ".$bulkRes['requests'][$key]['status']['errorCode'].' SKU '. $mp['code']);
                }
            }
            info($isAcademy == true ? "Academy" : "PSW"." Matrix Product Created Or Updated ");
        }

        return response()->json(['status' => 'success', 'response' => $bulkRes]);
    }

    public function archiveMatrixProduct($req){

        //ARCHIVING PRODUCTS ACCORDING TO PSW OR ACADEMY

        // $warehouses = LiveWarehouseLocation::where("pendingProcess", 0)->where("ENTITY", '<>', $this->api->client->ENTITY)->get();

        $limit = $req->limit ? $req->limit : 100;
        if($req->sku){
            $matrix = LiveProductMatrix::
                    join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType")
                    ->join("newstystem_store_location_live", function($q){
                        $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                        ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    })
                    ->where("newstystem_store_location_live.ENTITY", '<>', $this->api->client->ENTITY)
                    ->where('WEBSKU', $req->sku)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID"])
                    ->get();
        }else{
            $matrix = LiveProductMatrix::
                    join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType") 
                    ->join("newstystem_store_location_live", function($q){
                        $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                        ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    })
                    ->where("newstystem_store_location_live.ENTITY", '<>', $this->api->client->ENTITY)
                    // ->where('newsystem_product_group_live.DefaultStore','>') //temp
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    ->where('newsystem_product_matrix_live.erplyPending', 1)
                    ->where('newsystem_product_matrix_live.erplyID','>', 0)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    // ->where('newsystem_product_matrix_live.mUpdate', 1)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID"])
                    ->limit($limit)
                    ->get();
        }
        // echo "Empty Matrix";
        

        $acad = LiveWarehouseLocation::where("ENTITY", $this->api->client->ENTITY)->pluck("LocationID")->toArray();
        // dd($acad);

        //now getting matrix product where store location empty and not belongs to academy 
        $matrix = LiveProductMatrix::where("erplyID" ,'>', 0)
                    ->where("erplyPending" , 1)
                    // ->where("DefaultStore",'<>', '')
                    ->whereNotIn("DefaultStore", $acad)
                    ->limit($limit)
                    ->get();
        // dd($matrix);
        if($matrix->isEmpty()){
            info("Synccare to Erply : All Matrix Product Archived According to PSW Or Academy");
            return response()->json(["status" => "success", "message" => "Synccare to Erply : All Matrix Product Archived According to PSW Or Academy."]);
        }

        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        );

        //first get bulk 
        $getBulkReq = array();
        foreach($matrix as $mp){
            $checkParam = array(
                "requestName" => "getProducts",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "code" =>  $mp->WEBSKU
            );
            $getBulkReq[] = $checkParam;
        }
        if(count($getBulkReq) < 1){
            info("All Products Archived According to PSW OR ACADEMY");
            return response("All Products Archived According to PSW OR ACADEMY");
            die;
        }

        // dd($getBulkReq);


        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);

        if($getBulkRes["status"]["errorCode"] != 0){
            info("Error While getting products by code ".$getBulkRes["status"]["errorCode"]);
            return response("Error While getting products by code ".$getBulkRes["status"]["errorCode"]);
        }


        /*
        * ONLY REMOVING PRODUCTS THAT ARE EXIST IN ERPLY
        *  WITH LIMITED PARAMETER 
        */


        $bundleReq = array();
        foreach($matrix as $mkey => $mp){
            // $v = $this->variation->where('web_sku', $mp->web_sku)->where('newSystemInternetActive', 1)->first();
            if($getBulkRes["requests"][$mkey]["status"]["errorCode"] == 0 && !empty($getBulkRes["requests"][$mkey]["records"])){ 
                
                // $pid =  $this->productCheck($mp->WEBSKU);
                $param = array(
                    "requestName" => "saveProduct",
                    "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                    "clientCode" => $this->api->client->clientCode,
                    "type" => "MATRIX", 
                    "code" => $mp->WEBSKU,
                    "code2" => "",
                    "code3" => $mp->ConfigName,
                    "code5" => $mp->PLMStatus,
                    "code6" => $mp->ProductSubType,
                    "code7" => $mp->Category_Name,
                    "code8" => $mp->WebEnabled == 1 ? 'Y' : 'N',
                    "code9" => $mp->Category_Name,
                    "displayedInWebshop" => $mp->WebEnabled,
                    "active" => 0,  
                    "status" => 'ARCHIVED',
                    "name" => $mp->ItemName,
                    // "description" => $mp->newSystemShortDescription != '' ? $mp->newSystemShortDescription : $mp->newSystemStockDescription,
                    // "longdesc" => $mp->newSystemLongDescription,
                    "groupID" => $mp->erplyGroupID,
                    "categoryID" => $mp->erplyCatID,
                    "priceWithVAT" => $mp->RetailSalesPrice,
                    // "length" => ,
                    // "width" => ,
                    // "height" => ,
                    // "netWeight" => (double)$mp->ItemWeightGrams/1000,
                    // "sessionKey" => $this->api->client->sessionKey,
                    "dimensionID1" => 1,
                    "dimensionID2" => 8, 
                    "attributeName1" => "GenericProduct", 
                    "attributeType1" => "text", 
                    "attributeValue1" => $mp->genericProduct, 

                );

                $des = LiveProductDescription::where('WEBSKU', $mp->WEBSKU)->first();
                if($des){
                    $param["longdesc"] = $des->LongDescription;
                }
                if(!empty($getBulkRes["requests"][$mkey]["records"])){
                    //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    $param['productID'] = $getBulkRes['requests'][$mkey]['records'][0]['productID'];
                }
                //adding attributes\
                // $index = 1;
                // foreach($mp->toArray() as $key => $val){
                //     if($key == 'SOFStatus' || $key == "DefaultStore" || $key == "SecondaryStore" || $key == "ITEMID" || $key == "ColourID" || $key == "SizeID" || $key == "CONFIGID" || $key == "SchoolID"){
                //         $param["attributeName".$index] = $key;
                //         $param["attributeType".$index] =  'text';
                //         $param["attributeValue".$index] = $val;
                //         $index++;
                //     }
                // }
                array_push($bundleReq, $param);
            }else{
                $mp->erplyPending = 2;
                $mp->save();
            }
        }
        // dd($bundleReq);
        if(count($bundleReq) < 1){
            return response()->json(["message" => "All product Removed according to psw or academy"]);
        }
        // info($bundleReq);

        $matrixReq = $bundleReq;

        $bundleReq = json_encode($bundleReq,true);
        

        info("Matrix Bulk Arching  Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($matrixReq as $key => $mp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){
                    LiveProductMatrix::where("WEBSKU", $mp["code"])->update(
                        [
                            "erplyPending" => 0,
                            "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                        ]
                    );
                    // $mp->erplyPending = 0; 
                    // $mp->erplyID = $bulkRes['requests'][$key]['records'][0]['productID'];
                    // $mp->save();
                    info("matrix product Archived Successfully. ".$bulkRes['requests'][$key]['records'][0]['productID']." sku ".$mp['code']);
                }else{
                    LiveProductMatrix::where("WEBSKU", $mp["code"])->update(
                        [
                            "erplyPending" => 2,
                            // "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                        ]
                    );
                     
                    info("Error matrix product archiving ".$bulkRes['requests'][$key]['status']['errorCode'].' SKU '. $mp['code']);
                }
            }
        }

        return response()->json(['status' => 'success', 'response' => $bulkRes]);

    }

    public function syncVariationProduct($req){

        // return $this->syncVariationProductV2($req);
        // die;
 
        info("Variation Sync Called");
        // $limit = $req->limit ? $req->limit : 20;
        // if($req->sku){
        //     $matrix = $this->matrix
        //             ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
        //             ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType")
        //             ->join("newstystem_store_location_live", function($q){
        //                 $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
        //                 ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
        //             })
        //             ->where("newstystem_store_location_live.ENTITY", $this->api->client->ENTITY)
        //             ->where('newsystem_product_matrix_live.WEBSKU', $req->sku)
        //             ->where('newsystem_product_matrix_live.erplyPending', 0)
        //             ->where('newsystem_product_matrix_live.colorFlag', 0)
        //             ->where('newsystem_product_group_live.erplyGroupID','>', 0)
        //             ->where('newsystem_product_category_live.erplyCatID', '>', 0)
        //             // ->where('newsystem_product_matrix_live.erplyEnabled', 1)
        //             ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID"])
        //             ->get();
        // }else{
        //     $matrix = $this->matrix
        //             ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
        //             ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType") 
        //             ->join("newstystem_store_location_live", function($q){
        //                 $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
        //                 ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
        //             })
        //             ->where("newstystem_store_location_live.ENTITY", '<>', $this->api->client->ENTITY)
        //             ->where('newsystem_product_group_live.erplyGroupID','>', 0)
        //             ->where('newsystem_product_category_live.erplyCatID', '>', 0)
        //             ->where('newsystem_product_matrix_live.erplyPending', 0)
        //             ->where('newsystem_product_matrix_live.erplyDeleted', 0)
        //             ->where('newsystem_product_matrix_live.barcodeDuplicate', 0)
        //             // ->where('newsystem_product_matrix_live.erplyEnabled', 1)
        //             ->where('newsystem_product_matrix_live.variationPending', 1)
        //             // ->where('newsystem_product_matrix_live.genericProduct', 1)
        //             ->where('newsystem_product_matrix_live.colorFlag', 0)
        //             ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID"])
        //             // ->limit($limit)
        //             // ->inRandomOrder()
        //             ->limit($limit)
        //             // ->inRandomOrder(1)
        //             ->get(); 
        // }
        // // info("Matrix Fetched");
        
        // if(count($matrix) < 1){
            
        //     LiveProductMatrix::where("variationPending", 2)->update(["variationPending" => 1]);

        //     info("Synccare to Erply : All Variation Product Synced.");
        //     return response()->json(["message" => "All Variation Product Synced to Erply "]);
        // }

        //updating count to matrix
        // foreach($matrix as $mmm){
        //     $mmm->pushCount = $mmm->puchCount + 1;
        //     $mmm->save(); 
        // }

        // dd($matrix);


        // dd($matrix);
        // info($matrix);
        $stores = LiveWarehouseLocation::where("ENTITY", $this->api->client->ENTITY)->pluck("LocationID")->toArray();
        //Now Getting Matrix Variation According to Matrix SKU
        $msku = array();
        $bundleReq = array();
        $vCount = 0;
        $isFirstMaxLimit = false;
        // foreach($matrix as $keym => $mp){
            // dd($mp);
            $variation = $this->variation
                    // ->join("newstystem_store_location_live", function($q){
                    //     $q->on("newsystem_product_variation_live.DefaultStore", '=', "newstystem_store_location_live.LocationID");
                    //     // ->orWhere("newsystem_product_variation_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    // })
                    ->whereNotIn("DefaultStore", $stores)
                    // ->where("newstystem_store_location_live.ENTITY", '<>', $this->api->client->ENTITY)
                    // ->rightJoin('newsystem_product_description_live', 'newsystem_product_description_live.WEBSKU','=' ,'newsystem_product_variation_live.WEBSKU')
                    // ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_variation_live.SchoolID")
                    // ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_variation_live.ProductType") 
                    // ->leftJoin('newsystem_product_size_sort_order_live', 'newsystem_product_size_sort_order_live.size', 'newsystem_product_variation_live.SizeID')
                    // ->join('newsystem_product_color_live', 'newsystem_product_color_live.name', 'newsystem_product_variation_live.ColourName')
                    // ->join('newsystem_product_size_live', 'newsystem_product_size_live.name', 'newsystem_product_variation_live.SizeID')
                    // ->leftJoin('newsystem_product_description_live', 'newsystem_product_description_live.WEBSKU','=' ,'newsystem_product_variation_live.WEBSKU')
                    // ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    // ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    // ->where('newsystem_product_color_live.erplyColorID', '>', 0)
                    // ->where('newsystem_product_size_live.erplySizeID', '>', 0)
                    // ->where('newsystem_product_variation_live.WEBSKU', $mp->WEBSKU)
                    ->where('newsystem_product_variation_live.erplyPending', 1)
                    ->where('newsystem_product_variation_live.erplyID','>', 1)
                    ->where('newsystem_product_variation_live.erplyDeleted', 0)
                    ->where('newsystem_product_variation_live.genericProduct', 1)
                    // ->where('newsystem_product_variation_live.DefaultStore', )
                    // ->where('newsystem_product_variation_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    // ->where('newsystem_product_variation_live.genericProduct', 1) //for bundle product
                    // ->where('newsystem_product_variation_live.deleted', 1) //for bundle product
                    // ->where('newsystem_product_variation_live.barcodeDuplicate', 0)
                    // ->where('newsystem_product_variation_live.colorFlag', 0)
                    ->select(
                        [
                            "newsystem_product_variation_live.*",
                            // "newsystem_product_group_live.erplyGroupID",
                            // "newsystem_product_category_live.erplyCatID",
                            // "newsystem_product_color_live.erplyColorID",
                            // "newsystem_product_size_live.erplySizeID", 
                        ])
                    // ->orderBy('newsystem_product_size_sort_order_live.sort_order', 'asc')
                    // ->toSql();
                    ->limit(100)
                    ->get();
            
            // dd($variation);
            // $actualVariation = count(LiveProductVariation::where("WEBSKU", $mp->WEBSKU)
            //                         ->where("erplyPending", 1)
            //                         // ->where("erplyEnabled", $eeFlag == true ? 0 : 1)
            //                         ->get()
            //                     );
            // echo $actualVariation;
            // die;
            //now checking total products
            $isAllVariation = true;

            // if(count($variation) == $actualVariation){
            //     $isAllVariation = true;
            // }else{
            //     // $mp->variationPending = 2;
            //     // $mp->save();
            // }
             

            // echo $isAllVariation == true ? "OK" : "ERROR";
            // die;

            if(count($variation) < 1){
                info("Empty Variations");
                die;
                //check first is color is - than flag it
                // $chkV = $this->variation->where('WEBSKU', $mp->WEBSKU)->first();
                // if($chkV){
                //     if($chkV->ColourName == "-"){
                //         //let's flag as not color
                //         info("No Color Flag Updated");
                //         $this->variation->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //         $this->matrix->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //     }else{
                //         $this->variation->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //         $this->matrix->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //     }
                // }
            }        
            // dd($variation);
            // die;
            if($isAllVariation == true){
                // info("Variation Fetched ". $mp->WEBSKU);
                // if($keym == 0){
                //     if(count($variation) > 99){
                //         $isFirstMaxLimit = true;
                //         info("First Max Limit Crossed ". count($variation));
                //     }
                // }

                $vCount = $vCount + count($variation);
                if($isFirstMaxLimit == false){
                    
                    // if($vCount > 99){ 
                    //     $vCount = $vCount - count($variation); 
                    //     break;
                    // }else{

                        foreach($variation as $key => $vp){
                            // $mm = $this->matrix->where('WEBSKU', $vp->WEBSKU)->first();
                            
                            // $pid =  $this->productCheck($vp->ERPLYSKU);
                            $isFinal = true;
                            //now checking is this generic or not
                            $isGeneric = false;
                            if($vp->genericProduct == 1){
                                $isGeneric = true;
                            }

                            $param = array(
                                "requestName" => "saveProduct",
                                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                                "clientCode" => $this->api->client->clientCode,
                                "type" => $isGeneric == true ? "BUNDLE" : "PRODUCT",
                                // "dimValueID1" => $vp->erplyColorID,
                                // "dimValueID2" => $vp->erplySizeID,
                                // "parentProductID" => $mm->erplyID,
                                // // "groupID" => $this->getGroupID($product->web_sku),
                                "code" => $vp->ERPLYSKU,
                                // "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ERPLYSKU,
                                // "code3" => $vp->ConfigName,
                                // "code5" => $vp->PLMStatus,
                                // "code6" => $vp->ProductSubType,
                                // "code7" => $vp->Category_Name,
                                // "code8" => $vp->WebEnabled == 1 ? 'Y' : 'N',
                                // "code9" => $vp->DefaultStore,
                                // "displayedInWebshop" => $vp->WebEnabled,
                                // "active" => $vp->erplyEnabled,
                                // "status" => $vp->erplyEnabled == 1 ? "ACTIVE" : 'ARCHIVED',
                                // "name" => $vp->ItemName.' '.$vp->ColourName.' '.$vp->SizeID,
                                // // "description" => $vp->newSystemShortDescription != '' ? $vp->newSystemShortDescription : $vp->newSystemStockDescription,
                                // // "longdesc" => $vp->newSystemLongDescription,
                                // "groupID" => $vp->erplyGroupID,
                                // "categoryID" => $vp->erplyCatID,
                                // "priceWithVAT" => $vp->RetailSalesPrice,
                                // // "length" => ,
                                // // "width" => ,
                                // // "height" => ,
                                // "netWeight" => (double)$vp->ItemWeightGrams /1000,
                                "sessionKey" => $this->api->client->sessionKey, 
                            );

                            if($isGeneric == true){
                                //now getting component id
                                $component = LiveProductGenericVariation::where("erplyPending", 0)->where("ITEMID", $vp->ITEMID)->where("ColourID", $vp->ColourID)->where("SizeID", $vp->SizeID)->first();
                                // dd($component);
                                if($component){
                                    $param["componentProductID1"] = $component->erplyID;
                                    $param["componentAmount1"] = 0; 
                                }else{
                                    // dd($vp);
                                    $isFinal = false;

                                }
                            }
                            // die;
                            // $des = LiveProductDescription::where('WEBSKU', $vp->WEBSKU)->first();
                            // if($des){
                            //     $param["longdesc"] = $des->LongDescription;
                            // }
                
                            // if($pid != ''){
                            //     //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                            //     // $param['productID'] = $vp->erplyID;
                            //     $param['productID'] = $pid;
                            // }
                            //adding attributes\
                            $index = 1;
                            // foreach($vp->toArray() as $key => $val){
                            //     if($key == 'SOFStatus' || $key == "SecondaryStore" || $key == "ITEMID" || $key == "ColourID" || $key == "SizeID" || $key == "CONFIGID" || $key == "SchoolID"){
                            //         $param["attributeName".$index] = $key;
                            //         $param["attributeType".$index] =  'text';
                            //         $param["attributeValue".$index] = $val;
                            //         $index++;
                            //     }
                            // }
                            if($isFinal == true){
                                array_push($bundleReq, $param);
                            }
                            // else{
                            //     $mp->variationPending = 2;
                            //     $mp->save();
                            // }
                        }
                    // }
                }
                // else{
                //     foreach($variation as $key => $vp){

                //         if($key >= 99){
                //             break;
                //         }

                //         // $mm = $this->matrix->where('WEBSKU', $vp->WEBSKU)->first();
                        
                //         // $pid =  $this->productCheck($vp->ERPLYSKU);
                //         $isFinal = true;
                //         //now checking is this generic or not
                //         $isGeneric = false;
                //         if($vp->genericProduct == 1){
                //             $isGeneric = true;
                //         }
                //         $param = array(
                //             "requestName" => "saveProduct",
                //             "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                //             "clientCode" => $this->api->client->clientCode,
                //             "type" => $isGeneric == true ? "BUNDLE" : "PRODUCT",
                //             // "dimValueID1" => $vp->erplyColorID,
                //             // "dimValueID2" => $vp->erplySizeID,
                //             // "parentProductID" => $mm->erplyID,
                //             // "groupID" => $this->getGroupID($product->web_sku),
                //             // "code" => $vp->ERPLYSKU,
                //             // "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ERPLYSKU,
                //             // "code3" => $vp->ConfigName,
                //             // "code5" => $vp->PLMStatus,
                //             // "code6" => $vp->ProductSubType,
                //             // "code7" => $vp->Category_Name,
                //             // "code8" => $vp->WebEnabled == 1 ? 'Y' : 'N',
                //             // "code9" => $vp->DefaultStore,
                //             // "displayedInWebshop" => $vp->WebEnabled,
                //             // "active" => $vp->erplyEnabled,
                //             // "status" => $vp->erplyEnabled == 1 ? "ACTIVE" : 'ARCHIVED',
                //             // "name" => $vp->ItemName.' '.$vp->ColourName.' '.$vp->SizeID,
                //             // "description" => $vp->newSystemShortDescription != '' ? $vp->newSystemShortDescription : $vp->newSystemStockDescription,
                //             // "longdesc" => $vp->newSystemLongDescription,
                //             // "groupID" => $vp->erplyGroupID,
                //             // "categoryID" => $vp->erplyCatID,
                //             // "priceWithVAT" => $vp->RetailSalesPrice,
                //             // "length" => ,
                //             // "width" => ,
                //             // "height" => ,
                //             "netWeight" => (double)$vp->ItemWeightGrams /1000,
                //             "sessionKey" => $this->api->client->sessionKey, 
                //         );

                //         if($isGeneric == true){
                //             //now getting component id
                //             $component = LiveProductGenericVariation::where("erplyPending", 0)->where("ITEMID", $vp->ITEMID)->where("ColourID", $vp->ColourID)->where("SizeID", $vp->SizeID)->first();
                //             if($component){
                //                 $param["componentProductID1"] = $component->erplyID;
                //                 $param["componentAmount1"] = 0;  //temp 0 or 1
                //             }else{
                //                 $isFinal = false;
                //             }
                //         }
                        
                //         $des = LiveProductDescription::where('WEBSKU', $vp->WEBSKU)->first();
                //         if($des){
                //             $param["longdesc"] = $des->LongDescription;
                //         }

                //         // if($pid != ''){
                //         //     //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                //         //     // $param['productID'] = $vp->erplyID;
                //         //     $param['productID'] = $pid;
                //         // }
                //         //adding attributes\
                //         $index = 1;
                //         foreach($vp->toArray() as $key => $val){
                //             if($key == 'SOFStatus' || $key == "SecondaryStore" || $key == "ITEMID" || $key == "ColourID" || $key == "SizeID" || $key == "CONFIGID" || $key == "SchoolID"){
                //                 $param["attributeName".$index] = $key;
                //                 $param["attributeType".$index] =  'text';
                //                 $param["attributeValue".$index] = $val;
                //                 $index++;
                //             }
                //         }
                //         if($isFinal == true){
                //             array_push($bundleReq, $param);
                //         }
                //         // else{
                //         //     $mp->variationPending = 2;
                //         //     $mp->save();
                //         // }
                //     }
                // } 
            }
        // }

        // dd($bundleReq);

        info("TOT REQ ". count($bundleReq));

        if(count($bundleReq) > 100){
            
            return response()->json(["message" => "Max Request Limit exceeded"]);
        }
       
 
        if(count($bundleReq) < 1){
            info("No Variation Product Found");
            return response()->json(["message" => "No Product Found"]);
        }

        // die;
        $bulkReqFinal = $bundleReq;


        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        );

        //now getting bulk product id
        $getBulkReq = array();

        foreach($bulkReqFinal as $fp){
            $checkParam = array(
                "requestName" => "getProducts",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "code" =>  $fp['code'],
                "orderBy" => "productID",
                "orderByDir" => "asc",
                "getFields" => "productID,name,code,code2"
            );

            $getBulkReq[] = $checkParam;
        }

        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);
        // dd($getBulkRes);
        if($getBulkRes["status"]["errorCode"] != 0){
            info("Error While getting Variation products by code ".$getBulkRes["status"]["errorCode"]);
            return response("Error While getting Variation products by code ".$getBulkRes["status"]["errorCode"]);
        }

        //now adding product id
        $productWithPid = array();

        foreach($bundleReq as $key => $pp){

            if($getBulkRes["requests"][$key]["status"]["errorCode"] == 0){ 
                if(!empty($getBulkRes["requests"][$key]["records"])){
                    //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    $pp['productID'] = $getBulkRes['requests'][$key]['records'][0]['productID'];
                    // $param['productID'] = $mp->erplyID;
                    $productWithPid[] = $pp;
                }else{
                    $this->variation->where('ERPLYSKU', $pp['code'])->update(["erplyDeleted" => 1,"checkErply" => 1,"erplyPending" => 0]);
                }

                
            }

        }

        // dd($productWithPid);
        $finalV2Req = $productWithPid;
        if(count($productWithPid) < 1){
            
            // foreach($bundleReq as $data){
            //     LiveProductVariation::where("ERPLYSKU", $data["code"])->update(["erplyPending" => 0, "erplyDeleted" => 0, "checkErply" => 1]);
            // }
            info("Variation Product Not found and updated as erply deleted and checkerply = 1");
            return response("Variation Product Not found and updated as erply deleted and checkerply = 1");
        }

        // die;
        // info("TOD REQ ". count($bundleReq));
        // return response()->json($bundleReq);
        // die;
        // info($bundleReq);
        // info("TOT REQ ". count($bundleReq));
        
        // dd($productWithPid);
        // info($bundleReq);
        $bundleReq = json_encode($productWithPid,true);
        // $bulkparam = array(
        //     "lang" => 'eng',
        //     "responseType" => "json",
        //     "sessionKey" => $this->api->client->sessionKey,
        // );

        info("Variation Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($finalV2Req as $key => $vp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){
                    $this->variation->where('ERPLYSKU', $vp['code'])->update(["erplyPending" => 0, "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID'] ]);
                     
                    // if($isFirstMaxLimit == false){
                    //     $this->matrix->where('erplyID', $vp['parentProductID'])->update(['variationPending' => 0]);
                    // }
                    // info("Variation product create or updated ". $vp['code']);
                }else{
                    // info($bulkRes['requests'][$key]);
                    // if($bulkRes['requests'][$key]['status']['errorField'] == "code2"){
                    //     $this->variation->where('ERPLYSKU', $vp['code'])->update(["barcodeDuplicate" => 1 ]);
                    //     // $this->matrix->where('erplyID', $vp['parentProductID'])->update(['barcodeDuplicate' => 1]);
                    //     info(" Barcode Duplicate". $vp['code']);
                    // }else{
                    //     info("Error Variation product create or updated ". $vp['code']." error code ".$bulkRes['requests'][$key]['status']['errorCode'] );
                    // }
                    
                }
            }
            info("Bundle Product Updated... ");
        }
        

        return response()->json(['status' => 'success', 'response' => $bulkRes]);
    }

    public function nullPendingProducts($req){

        //first getting null and pending 0,1 product and update its parent variation pending flag
        $nullPendingVar = LiveProductVariation::whereNull("erplyID")->whereIn("DefaultStore", $this->api->getLocationID())->groupBy("WEBSKU")->orderBy("updated_at", "asc")->limit(100)->get();
        
        // if($nullPendingVar->isEmpty()){
        //     $nullPendingVar = LiveProductVariation::where("erplyPending", 1)->where("updated_at", )->whereIn("DefaultStore", $this->api->getLocationID())->groupBy("WEBSKU")->orderBy("updated_at", "asc")->limit(100)->get();
        // }
        foreach($nullPendingVar as $npv){
            //first updating erply pending variation product
            $npv->updated_at = date("Y-m-d H:i:s");
            $npv->save();

            LiveProductVariation::where("WEBSKU", $npv->WEBSKU)->update(["erplyPending" => 1]);
            LiveProductMatrix::where("WEBSKU", $npv->WEBSKU)->update(["variationPending" => 1]);

        }

        //now getting pending process 1 and whose parent variationt pending = 0
        $variationPending = LiveProductVariation::where("erplyPending", 1)->where("erplyID",">", 0)->whereIn("DefaultStore", $this->api->getLocationID())->groupBy("WEBSKU")->orderBy("updated_at", 'asc')->limit(200)->get();
        foreach($variationPending as $vpPending){
            LiveProductMatrix::where("WEBSKU", $vpPending->WEBSKU)->update(["variationPending" => 1]);
            LiveProductVariation::where("WEBSKU", $vpPending->WEBSKU)->update(["updated_at" => date("Y-m-d H:i:s")]);
        }

        info("************************************* Cron For Pending Variation Product Null ErplyID Sync Service ********************************");
        return response("************************************* Cron For Pending Variation Product Null ErplyID Sync Service ********************************");

    }

    public function syncVariationProductV2($req){
        $isDebug = $req->debug ?? '';
        //GET WAREHOUSE ACCORDING TO ENTITY PSW OR ACADEMY
        $isAcademy = $this->api->flag;
        if(env('isLive') == true){
            $checkCron = false;
            if(@$req->isfinal == true){
                $checkCron = true;
            }
            if($checkCron == false){
                // info("******************************* Variation Product Update Cron Blocked ********************");
                die;
            }
        }
        
        info("Variation Sync Called");
        $limit = $req->limit ? $req->limit : 40;
        if($req->sku){
            $matrix =LiveProductMatrix::
                    join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType")
                    ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID" ,"newsystem_product_matrix_live.DefaultStore")
                    // ->join("newstystem_store_location_live", function($q){
                    //     $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                    //     ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    // })
                    ->where("newstystem_store_location_live.ENTITY", $this->api->client->ENTITY)
                    ->where('newsystem_product_matrix_live.WEBSKU', $req->sku)
                    ->where('newsystem_product_matrix_live.erplyPending', 0)
                    ->where('newsystem_product_matrix_live.colorFlag', 0)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', 1)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID","newsystem_product_category_live.pswCatID"])
                    ->get();
                if($isDebug == 1){
                    dd($matrix);
                }
        }else{
            $matrix = $this->matrix
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType") 
                    ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID" ,"newsystem_product_matrix_live.DefaultStore")
                    // ->join("newstystem_store_location_live", function($q){
                    //     $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                    //     ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    // })
                    ->where("newstystem_store_location_live.ENTITY", $this->api->client->ENTITY)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_group_live.pendingProcess', 0)
                    ->where($isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->where('newsystem_product_matrix_live.erplyPending', 0)
                    ->where('newsystem_product_matrix_live.barcodeDuplicate', 0)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', 1)
                    ->whereIn('newsystem_product_matrix_live.variationPending', [1,2])
                    ->when($req)
                    // ->where('newsystem_product_matrix_live.genericProduct', 1)
                    ->where('newsystem_product_matrix_live.colorFlag', 0)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID","newsystem_product_category_live.pswCatID"])
                    ->orderBy("updated_at", "asc")
                    // ->limit($limit)
                    // ->inRandomOrder()
                    ->limit($limit)
                    // ->inRandomOrder(1)
                    ->get(); 
        } 
        
        
       if($isDebug == 2){
            dd($matrix, $isAcademy, $this->api->client->ENTITY);    
        }
        
        if(count($matrix) < 1){
            
            LiveProductMatrix::where("variationPending", 2)->update(["variationPending" => 1]);

            info("Synccare to Erply : All Variation Product Synced.");
            return response()->json(["message" => "All Variation Product Synced to Erply "]);
        }

        //updating count to matrix
        // foreach($matrix as $mmm){
        //     $mmm->pushCount = $mmm->puchCount + 1;
        //     $mmm->save(); 
        // } 
        //Now Getting Matrix Variation According to Matrix SKU
        $msku = array();
        $bundleReq = array();
        $vCount = 0;
        $isFirstMaxLimit = false;
        foreach($matrix as $keym => $mp){
            $mp->variationPending = 1;
            $mp->save(); 
            $variation = $this->variation
                    // ->rightJoin('newsystem_product_description_live', 'newsystem_product_description_live.WEBSKU','=' ,'newsystem_product_variation_live.WEBSKU')
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_variation_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_variation_live.ProductType") 
                    ->leftJoin('newsystem_product_size_sort_order_live', 'newsystem_product_size_sort_order_live.size', 'newsystem_product_variation_live.SizeID')
                    ->join('newsystem_product_color_live', 'newsystem_product_color_live.name', 'newsystem_product_variation_live.ColourName')
                    ->join('newsystem_product_size_live', 'newsystem_product_size_live.name', 'newsystem_product_variation_live.SizeID')
                    // ->leftJoin('newsystem_product_description_live', 'newsystem_product_description_live.WEBSKU','=' ,'newsystem_product_variation_live.WEBSKU')
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_group_live.pendingProcess', 0)
                    ->where($this->api->flag == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->where($this->api->flag == true ? 'newsystem_product_color_live.erplyColorID' : 'newsystem_product_color_live.pswColorID', '>', 0)
                    ->where($this->api->flag == true ? 'newsystem_product_size_live.erplySizeID' : 'newsystem_product_size_live.pswSizeID', '>', 0)
                    ->where('newsystem_product_variation_live.WEBSKU', $mp->WEBSKU)
                    ->where('newsystem_product_variation_live.erplyPending', 1)
                    // ->where('newsystem_product_variation_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    // ->where('newsystem_product_variation_live.genericProduct', 1) //for bundle product
                    // ->where('newsystem_product_variation_live.deleted', 1) //for bundle product
                    ->where('newsystem_product_variation_live.barcodeDuplicate', 0)
                    ->where('newsystem_product_variation_live.colorFlag', 0)
                    ->select(
                        [
                            "newsystem_product_variation_live.*",
                            "newsystem_product_group_live.erplyGroupID",
                            "newsystem_product_category_live.erplyCatID",
                            "newsystem_product_category_live.pswCatID",
                            "newsystem_product_color_live.erplyColorID",
                            "newsystem_product_color_live.pswColorID",
                            "newsystem_product_size_live.erplySizeID",
                            "newsystem_product_size_live.pswSizeID", 
                        ])
                    ->orderBy('newsystem_product_size_sort_order_live.sort_order', 'asc')
                    // ->toSql();
                    ->get();
             
            $actualVariation = count(LiveProductVariation::where("WEBSKU", $mp->WEBSKU)
                                    ->where("erplyPending", 1)
                                    // ->where("erplyEnabled", $eeFlag == true ? 0 : 1)
                                    ->get()
                                );
                                
                               
 
            //now checking total products
            $isAllVariation = false;

            if(count($variation) == $actualVariation){
                $isAllVariation = true;
            }else{
                // $mp->variationPending = 2;
                // $mp->save();
            }
  

            if(count($variation) < 1){

                info("Empty Variations ". $mp->WEBSKU);
                if(LiveProductVariation::where('WEBSKU', $mp->WEBSKU)->first()){
                    LiveProductVariation::where('WEBSKU', $mp->WEBSKU)->update(["erplyPending" => 1]);
                    info("Variation Product Status Updated...");
                }
                

                //check first is color is - than flag it
                // $chkV = $this->variation->where('WEBSKU', $mp->WEBSKU)->first();
                // if($chkV){
                //     if($chkV->ColourName == "-"){
                //         //let's flag as not color
                //         info("No Color Flag Updated");
                //         $this->variation->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //         $this->matrix->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //     }else{
                //         $this->variation->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //         $this->matrix->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //     }
                // }
            }        
 
            if($isAllVariation == true){
 
                if($keym == 0){
                    if(count($variation) > 99){
                        $isFirstMaxLimit = true;
                        info("First Max Limit Crossed ". count($variation));
                    }
                }

                $vCount = $vCount + count($variation);
                if($isFirstMaxLimit == false){
                    
                    if($vCount > 99){ 
                        $vCount = $vCount - count($variation); 
                        break;
                    }else{
                        foreach($variation as $key => $vp){
                            // $mm = MatrixProduct::where('WEBSKU', $vp->WEBSKU)->first();
                            // $mm = LiveProductMatrix::where('WEBSKU', $vp->WEBSKU)->first();
                            
                            // $pid =  $this->productCheck($vp->ERPLYSKU);
                            $isFinal = true;
                            //now checking is this generic or not
                            $isGeneric = false;
                            if($vp->genericProduct == 1){
                                $isGeneric = true;
                            }

                            $param = array(
                                "requestName" => "saveProduct",
                                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                                "clientCode" => $this->api->client->clientCode,
                                "type" => $isGeneric == true ? "BUNDLE" : "PRODUCT",
                                "dimValueID1" => $this->api->flag == true ? $vp->erplyColorID : $vp->pswColorID,
                                "dimValueID2" => $this->api->flag == true ? $vp->erplySizeID : $vp->pswSizeID,
                                "parentProductID" => $mp->erplyID,
                                // "groupID" => $this->getGroupID($product->web_sku),
                                "code" => $vp->ERPLYSKU,
                                "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ERPLYSKU,
                                "code3" => $vp->ConfigName,
                                "code5" => $vp->PLMStatus,
                                "code6" => $vp->ProductSubType,
                                "code7" => $vp->Category_Name,
                                "code8" => $vp->WebEnabled == 1 ? 'Y' : 'N',
                                "code9" => $vp->DefaultStore,
                                "displayedInWebshop" => $vp->WebEnabled,
                                "active" => $this->api->isLiveEnv() == 0 ? 1 : $vp->erplyEnabled,
                                "status" => $this->api->isLiveEnv() == 0 ? "ACTIVE" : ($vp->erplyEnabled == 1 ? "ACTIVE" : 'ARCHIVED'),
                                "name" => $vp->ItemName.' '.$vp->ColourName.' '.$vp->SizeID,
                                // "description" => $vp->newSystemShortDescription != '' ? $vp->newSystemShortDescription : $vp->newSystemStockDescription,
                                // "longdesc" => $vp->newSystemLongDescription,
                                "groupID" => $vp->erplyGroupID,
                                "categoryID" => $this->api->flag == true ? $vp->erplyCatID : $vp->pswCatID,
                                "priceWithVAT" => $vp->RetailSalesPrice,
                                // "length" => ,
                                // "width" => ,
                                // "height" => ,
                                "netWeight" => (double)$vp->ItemWeightGrams /1000,
                                "sessionKey" => $this->api->client->sessionKey, 
                            );

                            if($isGeneric == true){
                                //now getting component id
                                $component = LiveProductGenericVariation:://where($this->api->flag == true ? "erplyPending" : "pswPending", 0)
                                            where($isAcademy == true ? "erplyID" : "pswErplyID", '>', 0)
                                            ->where("ITEMID", $vp->ITEMID)
                                            ->where("ColourID", $vp->ColourID)
                                            ->where("SizeID", $vp->SizeID)
                                            ->first(); 
                                if($component){
                                    $param["componentProductID1"] = $this->api->flag == true ? $component->erplyID : $component->pswErplyID;
                                    $param["componentAmount1"] = 1; 
                                }else{ 
                                    $isFinal = false;
                                }
                            } 
                            $des = LiveProductDescription::where('WEBSKU', $vp->WEBSKU)->first();
                            if($des){
                                $param["longdesc"] = $des->LongDescription;
                            }
                            // if($pid != ''){
                            //     //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                            //     // $param['productID'] = $vp->erplyID;
                            //     $param['productID'] = $pid;
                            // }
                            //adding attributes\
                            $index = 1;
                            foreach($vp->toArray() as $key => $val){
                                if($key == 'SOFStatus' || $key == "SecondaryStore" || $key == "ITEMID" || $key == "ColourID" || $key == "SizeID" || $key == "CONFIGID" || $key == "SchoolID" || $key == "customItemName" || $key == "receiptDescription" || $key == "ItemName"){
                                    if($val != '' && is_null($val) == false){
                                        $param["attributeName".$index] = $key == "receiptDescription" ? "Receipt_Product_Description" : ($key == "customItemName" ? "Receipt_Product_Name" : ($key == "ItemName" ? "Matrix_Product_Name" : $key));
                                        $param["attributeType".$index] =  'text';
                                        $param["attributeValue".$index] = $key == "receiptDescription" ? str_replace("'", "", $val) : $val;
                                        $index++;
                                    }
                                    if($key == "receiptDescription" &&  ($val == '' || is_null($val) == true)){
                                        $customReceiptDescription = @$vp->SchoolName .' '.@$vp->ItemName.' '.@$vp->ColourName.' '.@$vp->SizeID;
                                        $param["attributeName".$index] = "Receipt_Product_Description";
                                        $param["attributeType".$index] =  'text';
                                        $param["attributeValue".$index] = str_replace("'", "", $customReceiptDescription);
                                        $index++;
                                    }
                                }
                            }
                            if($isFinal == true){
                                array_push($bundleReq, $param);
                            }else{
                                $mp->variationPending = 2;
                                $mp->save();
                            }
                        }
                    }
                }else{
                    foreach($variation as $key => $vp){
                        if($key >= 99){
                            break 2;
                        }
                        // $mm = $this->matrix->where('WEBSKU', $vp->WEBSKU)->first();
                        // $mm = MatrixProduct::where('WEBSKU', $vp->WEBSKU)->first();
                        // $pid =  $this->productCheck($vp->ERPLYSKU);
                        $isFinal = true;
                        //now checking is this generic or not
                        $isGeneric = false;
                        if($vp->genericProduct == 1){
                            $isGeneric = true;
                        }
                        $param = array(
                            "requestName" => "saveProduct",
                            "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                            "clientCode" => $this->api->client->clientCode,
                            "type" => $isGeneric == true ? "BUNDLE" : "PRODUCT",
                            "dimValueID1" => $this->api->flag == true ? $vp->erplyColorID : $vp->pswColorID,
                            "dimValueID2" => $this->api->flag == true ? $vp->erplySizeID : $vp->pswSizeID,
                            "parentProductID" => $mp->erplyID,
                            // "groupID" => $this->getGroupID($product->web_sku),
                            "code" => $vp->ERPLYSKU,
                            "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ERPLYSKU,
                            "code3" => $vp->ConfigName,
                            "code5" => $vp->PLMStatus,
                            "code6" => $vp->ProductSubType,
                            "code7" => $vp->Category_Name,
                            "code8" => $vp->WebEnabled == 1 ? 'Y' : 'N',
                            "code9" => $vp->DefaultStore,
                            "displayedInWebshop" => $vp->WebEnabled,
                            "active" => $this->api->isLiveEnv() == 0 ? 1 : $vp->erplyEnabled,
                            "status" => $this->api->isLiveEnv() == 0 ? "ACTIVE" : ($vp->erplyEnabled == 1 ? "ACTIVE" : 'ARCHIVED'),
                            "name" => $vp->ItemName.' '.$vp->ColourName.' '.$vp->SizeID,
                            // "description" => $vp->newSystemShortDescription != '' ? $vp->newSystemShortDescription : $vp->newSystemStockDescription,
                            // "longdesc" => $vp->newSystemLongDescription,
                            "groupID" => $vp->erplyGroupID,
                            "categoryID" => $this->api->flag == true ? $vp->erplyCatID : $vp->pswCatID,
                            "priceWithVAT" => $vp->RetailSalesPrice,
                            // "length" => ,
                            // "width" => ,
                            // "height" => ,
                            "netWeight" => (double)$vp->ItemWeightGrams /1000,
                            "sessionKey" => $this->api->client->sessionKey, 
                        );

                        if($isGeneric == true){
                            //now getting component id
                            $component = LiveProductGenericVariation:://where($this->api->flag == true ? "erplyPending" : "pswPending", 0)
                                where($isAcademy == true ? "erplyID" : "pswErplyID", '>', 0)
                                ->where("ITEMID", $vp->ITEMID)
                                ->where("ColourID", $vp->ColourID)
                                ->where("SizeID", $vp->SizeID)
                                ->first();
                            if($component){
                                $param["componentProductID1"] = $this->api->flag == true ? $component->erplyID : $component->pswErplyID;
                                $param["componentAmount1"] = 1; 
                            }else{
                                $isFinal = false;
                            }
                        }
                        
                        $des = LiveProductDescription::where('WEBSKU', $vp->WEBSKU)->first();
                        if($des){
                            $param["longdesc"] = $des->LongDescription;
                        }

                        // if($pid != ''){
                        //     //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                        //     // $param['productID'] = $vp->erplyID;
                        //     $param['productID'] = $pid;
                        // }
                        //adding attributes\
                        $index = 1;
                        foreach($vp->toArray() as $key => $val){
                            if($key == 'SOFStatus' || $key == "SecondaryStore" || $key == "ITEMID" || $key == "ColourID" || $key == "SizeID" || $key == "CONFIGID" || $key == "SchoolID" || $key == "customItemName" || $key == "receiptDescription" || $key == "ItemName"){
                                if($val != '' && is_null($val) == false){
                                    $param["attributeName".$index] = $key == "receiptDescription" ? "Receipt_Product_Description" : ($key == "customItemName" ? "Receipt_Product_Name" : ($key == "ItemName" ? "Matrix_Product_Name" : $key));
                                    $param["attributeType".$index] =  'text';
                                    $param["attributeValue".$index] = $key == "receiptDescription" ? str_replace("'", "", $val) : $val;
                                    $index++;
                                }
                                if($key == "receiptDescription" &&  ($val == '' || is_null($val) == true)){
                                    $customReceiptDescription = @$vp->SchoolName .' '.@$vp->ItemName.' '.@$vp->ColourName.' '.@$vp->SizeID;
                                    $param["attributeName".$index] = "Receipt_Product_Description";
                                    $param["attributeType".$index] =  'text';
                                    $param["attributeValue".$index] = str_replace("'", "", $customReceiptDescription);
                                    $index++;
                                }
                            }
                        }
                        if($isFinal == true){
                            array_push($bundleReq, $param);
                        }else{
                            $mp->variationPending = 2;
                            $mp->save();
                        }
                    }
                } 
            }
        }  
        info($this->api->client->ENTITY. " TOT REQ ". count($bundleReq)); 
        if(count($bundleReq) > 99){ 
            return response()->json(["message" => "Max Request Limit exceeded"]);
        } 
        if(count($bundleReq) < 1){
            info("No Variation Product Found");
            return response()->json(["message" => "No Product Found"]);
        } 
        $bulkReqFinal = $bundleReq;
        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        ); 
        //now getting bulk product id
        $getBulkReq = array();
        foreach($bulkReqFinal as $fp){
            $checkParam = array(
                "requestName" => "getProducts",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "code" =>  $fp['code'],
                "orderBy" => "productID",
                "orderByDir" => "asc",
                "getFields" => "productID,name,code,code2"
            );
            $getBulkReq[] = $checkParam;
        }

        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1); 
        if($getBulkRes["status"]["errorCode"] != 0){
            info("Error While getting Variation products by code ".$getBulkRes["status"]["errorCode"]);
            return response("Error While getting Variation products by code ".$getBulkRes["status"]["errorCode"]);
        } 
        //now adding product id
        $productWithPid = array();

        foreach($bundleReq as $key => $pp){ 
            if($getBulkRes["requests"][$key]["status"]["errorCode"] == 0){ 
                if(!empty($getBulkRes["requests"][$key]["records"])){
                    //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    $pp['productID'] = $getBulkRes['requests'][$key]['records'][0]['productID'];
                    // $param['productID'] = $mp->erplyID;
                } 
                $productWithPid[] = $pp;
            } 
        } 
        $finalV2Req = $productWithPid;
        if(count($productWithPid) < 1){
            info("Variation Product Not found");
            return response("Variation Product Not found");
        }

        if($isDebug == 1){
            dd(vars: $finalV2Req);    
        }  
        $bundleReq = json_encode($productWithPid,true);
        // $bulkparam = array(
        //     "lang" => 'eng',
        //     "responseType" => "json",
        //     "sessionKey" => $this->api->client->sessionKey,
        // );

        info($this->api->client->ENTITY. " Variation Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($finalV2Req as $key => $vp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){
                    $this->variation->where('ERPLYSKU', $vp['code'])->update(["erplyPending" => 0, "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID'], 'erplyError' => '' ]);
                     
                    if($isFirstMaxLimit == false){
                        $this->matrix->where('erplyID', $vp['parentProductID'])->update(['variationPending' => 0]);
                    }
                    // info("Variation product create or updated ". $vp['code']);
                }else{

                    // info($bulkRes['requests'][$key]);
                    if($bulkRes['requests'][$key]['status']['errorField'] == "code2"){
                        $this->variation->where('ERPLYSKU', $vp['code'])->update(["barcodeDuplicate" => 1 ]);
                        $this->matrix->where('erplyID', $vp['parentProductID'])->update(['barcodeDuplicate' => 1]);
                        info(" Barcode Duplicate". $vp['code']);
                    }else{
                        info( $this->api->client->ENTITY." Error Variation product create or updated ". $vp['code']." error code ".$bulkRes['requests'][$key]['status']['errorCode'] );
                        $this->variation->where('ERPLYSKU', $vp['code'])->update(["erplyError" => json_encode($bulkRes['requests'][$key], true) ]);
                        $this->matrix->where('erplyID', $vp['parentProductID'])->update(['variationPending' => 0]);
                    }
                    
                }
            }
        }
        info($this->api->client->ENTITY ." Variation Product Created Or Updated "); 

        return response()->json(['status' => 'success', 'response' => $bulkRes]);
    }

    public function archiveVariationProduct($req){
        info("Variation Archive Called");
        $limit = $req->limit ? $req->limit : 20;
        if($req->sku){
            $matrix = $this->matrix
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType")
                    ->join("newstystem_store_location_live", function($q){
                        $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                        ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    })
                    ->where("newstystem_store_location_live.ENTITY", '<>', $this->api->client->ENTITY)
                    ->where('newsystem_product_matrix_live.WEBSKU', $req->sku)
                    ->where('newsystem_product_matrix_live.erplyPending', 0)
                    ->where('newsystem_product_matrix_live.colorFlag', 0)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', 1)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID"])
                    ->get();
        }else{
            $matrix = $this->matrix
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_matrix_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_matrix_live.ProductType") 
                    ->join("newstystem_store_location_live", function($q){
                        $q->on("newsystem_product_matrix_live.DefaultStore", '=', "newstystem_store_location_live.LocationID")
                        ->orWhere("newsystem_product_matrix_live.SecondaryStore", '=', "newstystem_store_location_live.LocationID");
                    })
                    ->where("newstystem_store_location_live.ENTITY", '<>', $this->api->client->ENTITY)
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    ->where('newsystem_product_matrix_live.erplyPending', 0)
                    ->where('newsystem_product_matrix_live.barcodeDuplicate', 0)
                    // ->where('newsystem_product_matrix_live.erplyEnabled', 1)
                    ->where('newsystem_product_matrix_live.variationPending', 1)
                    // ->where('newsystem_product_matrix_live.genericProduct', 1)
                    ->where('newsystem_product_matrix_live.colorFlag', 0)
                    ->select(["newsystem_product_matrix_live.*", "newsystem_product_group_live.erplyGroupID","newsystem_product_category_live.erplyCatID"])
                    // ->limit($limit)
                    // ->inRandomOrder()
                    ->limit($limit)
                    // ->inRandomOrder(1)
                    ->get(); 
        }
        // info("Matrix Fetched");
        
        if(count($matrix) < 1){
            
            LiveProductMatrix::where("variationPending", 2)->update(["variationPending" => 1]);

            info("Synccare to Erply : All Variation Product Synced.");
            return response()->json(["message" => "All Variation Product Synced to Erply "]);
        }

        //updating count to matrix
        // foreach($matrix as $mmm){
        //     $mmm->pushCount = $mmm->puchCount + 1;
        //     $mmm->save(); 
        // }

        // dd($matrix);


        // dd($matrix);
        // info($matrix);
        //Now Getting Matrix Variation According to Matrix SKU
        $msku = array();
        $bundleReq = array();
        $vCount = 0;
        $isFirstMaxLimit = false;
        foreach($matrix as $keym => $mp){
            // dd($mp);
            $variation = $this->variation
                    // ->rightJoin('newsystem_product_description_live', 'newsystem_product_description_live.WEBSKU','=' ,'newsystem_product_variation_live.WEBSKU')
                    ->join("newsystem_product_group_live", "newsystem_product_group_live.SchoolID", "newsystem_product_variation_live.SchoolID")
                    ->join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_variation_live.ProductType") 
                    ->leftJoin('newsystem_product_size_sort_order_live', 'newsystem_product_size_sort_order_live.size', 'newsystem_product_variation_live.SizeID')
                    ->join('newsystem_product_color_live', 'newsystem_product_color_live.name', 'newsystem_product_variation_live.ColourName')
                    ->join('newsystem_product_size_live', 'newsystem_product_size_live.name', 'newsystem_product_variation_live.SizeID')
                    // ->leftJoin('newsystem_product_description_live', 'newsystem_product_description_live.WEBSKU','=' ,'newsystem_product_variation_live.WEBSKU')
                    ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    ->where('newsystem_product_color_live.erplyColorID', '>', 0)
                    ->where('newsystem_product_size_live.erplySizeID', '>', 0)
                    ->where('newsystem_product_variation_live.WEBSKU', $mp->WEBSKU)
                    ->where('newsystem_product_variation_live.erplyPending', 1)
                    // ->where('newsystem_product_variation_live.erplyEnabled', $eeFlag == true ? 0 : 1)
                    // ->where('newsystem_product_variation_live.genericProduct', 1) //for bundle product
                    // ->where('newsystem_product_variation_live.deleted', 1) //for bundle product
                    ->where('newsystem_product_variation_live.barcodeDuplicate', 0)
                    ->where('newsystem_product_variation_live.colorFlag', 0)
                    ->select(
                        [
                            "newsystem_product_variation_live.*",
                            "newsystem_product_group_live.erplyGroupID",
                            "newsystem_product_category_live.erplyCatID",
                            "newsystem_product_color_live.erplyColorID",
                            "newsystem_product_size_live.erplySizeID", 
                        ])
                    ->orderBy('newsystem_product_size_sort_order_live.sort_order', 'asc')
                    // ->toSql();
                    ->get();
            
            // dd($variation);
            $actualVariation = count(LiveProductVariation::where("WEBSKU", $mp->WEBSKU)
                                    ->where("erplyPending", 1)
                                    // ->where("erplyEnabled", $eeFlag == true ? 0 : 1)
                                    ->get()
                                );
            // echo $actualVariation;
            // die;
            //now checking total products
            $isAllVariation = false;

            if(count($variation) == $actualVariation){
                $isAllVariation = true;
            }else{
                // $mp->variationPending = 2;
                // $mp->save();
            }
             

            // echo $isAllVariation == true ? "OK" : "ERROR";
            // die;

            if(count($variation) < 1){
                info("Empty Variations");
                //check first is color is - than flag it
                // $chkV = $this->variation->where('WEBSKU', $mp->WEBSKU)->first();
                // if($chkV){
                //     if($chkV->ColourName == "-"){
                //         //let's flag as not color
                //         info("No Color Flag Updated");
                //         $this->variation->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //         $this->matrix->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //     }else{
                //         $this->variation->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //         $this->matrix->where('WEBSKU', $mp->WEBSKU)->update(['colorFlag' => 1]);
                //     }
                // }
            }        
            // dd($variation);
            // die;
            if($isAllVariation == true){
                info("Variation Fetched ". $mp->WEBSKU);
                if($keym == 0){
                    if(count($variation) > 99){
                        $isFirstMaxLimit = true;
                        info("First Max Limit Crossed ". count($variation));
                    }
                }

                $vCount = $vCount + count($variation);
                if($isFirstMaxLimit == false){
                    
                    if($vCount > 99){ 
                        $vCount = $vCount - count($variation); 
                        break;
                    }else{
                        foreach($variation as $key => $vp){
                            $mm = $this->matrix->where('WEBSKU', $vp->WEBSKU)->first();
                            
                            // $pid =  $this->productCheck($vp->ERPLYSKU);
                            $isFinal = true;
                            //now checking is this generic or not
                            $isGeneric = false;
                            if($vp->genericProduct == 1){
                                $isGeneric = true;
                            }

                            $param = array(
                                "requestName" => "saveProduct",
                                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                                "clientCode" => $this->api->client->clientCode,
                                // "type" => $isGeneric == true ? "BUNDLE" : "PRODUCT",
                                // "dimValueID1" => $vp->erplyColorID,
                                // "dimValueID2" => $vp->erplySizeID,
                                // "parentProductID" => $mm->erplyID,
                                // "groupID" => $this->getGroupID($product->web_sku),
                                // "code" => $vp->ERPLYSKU,
                                // "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ERPLYSKU,
                                // "code3" => $vp->ConfigName,
                                // "code5" => $vp->PLMStatus,
                                // "code6" => $vp->ProductSubType,
                                // "code7" => $vp->Category_Name,
                                // "code8" => $vp->WebEnabled == 1 ? 'Y' : 'N',
                                // "code9" => $vp->DefaultStore,
                                // "displayedInWebshop" => $vp->WebEnabled,
                                "active" => 0,
                                "status" => 'ARCHIVED',
                                // "name" => $vp->ItemName.' '.$vp->ColourName.' '.$vp->SizeID,
                                // "description" => $vp->newSystemShortDescription != '' ? $vp->newSystemShortDescription : $vp->newSystemStockDescription,
                                // "longdesc" => $vp->newSystemLongDescription,
                                // "groupID" => $vp->erplyGroupID,
                                // "categoryID" => $vp->erplyCatID,
                                // "priceWithVAT" => $vp->RetailSalesPrice,
                                // "length" => ,
                                // "width" => ,
                                // "height" => ,
                                // "netWeight" => (double)$vp->ItemWeightGrams /1000,
                                // "sessionKey" => $this->api->client->sessionKey, 
                            );

                            // if($isGeneric == true){
                            //     //now getting component id
                            //     $component = LiveProductGenericVariation::where("erplyPending", 0)->where("ITEMID", $vp->ITEMID)->where("ColourID", $vp->ColourID)->where("SizeID", $vp->SizeID)->first();
                            //     // dd($component);
                            //     if($component){
                            //         $param["componentProductID1"] = $component->erplyID;
                            //         $param["componentAmount1"] = 1; 
                            //     }else{
                            //         // dd($vp);
                            //         $isFinal = false;

                            //     }
                            // }
                            // die;
                            // $des = LiveProductDescription::where('WEBSKU', $vp->WEBSKU)->first();
                            // if($des){
                            //     $param["longdesc"] = $des->LongDescription;
                            // }
                
                            // if($pid != ''){
                            //     //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                            //     // $param['productID'] = $vp->erplyID;
                            //     $param['productID'] = $pid;
                            // }
                            //adding attributes\
                            // $index = 1;
                            // foreach($vp->toArray() as $key => $val){
                            //     if($key == 'SOFStatus' || $key == "SecondaryStore" || $key == "ITEMID" || $key == "ColourID" || $key == "SizeID" || $key == "CONFIGID" || $key == "SchoolID"){
                            //         $param["attributeName".$index] = $key;
                            //         $param["attributeType".$index] =  'text';
                            //         $param["attributeValue".$index] = $val;
                            //         $index++;
                            //     }
                            // }
                            if($isFinal == true){
                                array_push($bundleReq, $param);
                            }else{
                                $mp->variationPending = 2;
                                $mp->save();
                            }
                        }
                    }
                }else{
                    foreach($variation as $key => $vp){

                        if($key >= 99){
                            break 2;
                        }

                        $mm = $this->matrix->where('WEBSKU', $vp->WEBSKU)->first();
                        
                        // $pid =  $this->productCheck($vp->ERPLYSKU);
                        $isFinal = true;
                        //now checking is this generic or not
                        $isGeneric = false;
                        if($vp->genericProduct == 1){
                            $isGeneric = true;
                        }
                        $param = array(
                            "requestName" => "saveProduct",
                            "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                            "clientCode" => $this->api->client->clientCode,
                            // "type" => $isGeneric == true ? "BUNDLE" : "PRODUCT",
                            // "dimValueID1" => $vp->erplyColorID,
                            // "dimValueID2" => $vp->erplySizeID,
                            // "parentProductID" => $mm->erplyID,
                            // "groupID" => $this->getGroupID($product->web_sku),
                            // "code" => $vp->ERPLYSKU,
                            // "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ERPLYSKU,
                            // "code3" => $vp->ConfigName,
                            // "code5" => $vp->PLMStatus,
                            // "code6" => $vp->ProductSubType,
                            // "code7" => $vp->Category_Name,
                            // "code8" => $vp->WebEnabled == 1 ? 'Y' : 'N',
                            // "code9" => $vp->DefaultStore,
                            // "displayedInWebshop" => $vp->WebEnabled,
                            "active" => 0,
                            "status" => 'ARCHIVED',
                            // "name" => $vp->ItemName.' '.$vp->ColourName.' '.$vp->SizeID,
                            // "description" => $vp->newSystemShortDescription != '' ? $vp->newSystemShortDescription : $vp->newSystemStockDescription,
                            // "longdesc" => $vp->newSystemLongDescription,
                            // "groupID" => $vp->erplyGroupID,
                            // "categoryID" => $vp->erplyCatID,
                            // "priceWithVAT" => $vp->RetailSalesPrice,
                            // "length" => ,
                            // "width" => ,
                            // "height" => ,
                            // "netWeight" => (double)$vp->ItemWeightGrams /1000,
                            // "sessionKey" => $this->api->client->sessionKey, 
                        );

                        // if($isGeneric == true){
                        //     //now getting component id
                        //     $component = LiveProductGenericVariation::where("erplyPending", 0)->where("ITEMID", $vp->ITEMID)->where("ColourID", $vp->ColourID)->where("SizeID", $vp->SizeID)->first();
                        //     if($component){
                        //         $param["componentProductID1"] = $component->erplyID;
                        //         $param["componentAmount1"] = 1; 
                        //     }else{
                        //         $isFinal = false;
                        //     }
                        // }
                        
                        // $des = LiveProductDescription::where('WEBSKU', $vp->WEBSKU)->first();
                        // if($des){
                        //     $param["longdesc"] = $des->LongDescription;
                        // }

                        // if($pid != ''){
                        //     //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                        //     // $param['productID'] = $vp->erplyID;
                        //     $param['productID'] = $pid;
                        // }
                        //adding attributes\
                        // $index = 1;
                        // foreach($vp->toArray() as $key => $val){
                        //     if($key == 'SOFStatus' || $key == "SecondaryStore" || $key == "ITEMID" || $key == "ColourID" || $key == "SizeID" || $key == "CONFIGID" || $key == "SchoolID"){
                        //         $param["attributeName".$index] = $key;
                        //         $param["attributeType".$index] =  'text';
                        //         $param["attributeValue".$index] = $val;
                        //         $index++;
                        //     }
                        // }
                        if($isFinal == true){
                            array_push($bundleReq, $param);
                        }else{
                            $mp->variationPending = 2;
                            $mp->save();
                        }
                    }
                } 
            }
        }

        // dd($bundleReq);

        info("TOT REQ ". count($bundleReq));

        if(count($bundleReq) > 99){
            
            return response()->json(["message" => "Max Request Limit exceeded"]);
        }
       
 
        if(count($bundleReq) < 1){
            info("No Variation Product Found");
            return response()->json(["message" => "No Product Found"]);
        }

        // die;
        $bulkReqFinal = $bundleReq;


        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        );

        //now getting bulk product id
        $getBulkReq = array();

        foreach($bulkReqFinal as $fp){
            $checkParam = array(
                "requestName" => "getProducts",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "code" =>  $fp['code'],
                "orderBy" => "productID",
                "orderByDir" => "asc",
                "getFields" => "productID,name,code,code2"
            );

            $getBulkReq[] = $checkParam;
        }

        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);
        // dd($getBulkRes);
        if($getBulkRes["status"]["errorCode"] != 0){
            info("Error While getting Variation products by code ".$getBulkRes["status"]["errorCode"]);
            return response("Error While getting Variation products by code ".$getBulkRes["status"]["errorCode"]);
        }

        //now adding product id
        $productWithPid = array();

        foreach($bundleReq as $key => $pp){

            if($getBulkRes["requests"][$key]["status"]["errorCode"] == 0){ 
                if(!empty($getBulkRes["requests"][$key]["records"])){
                    //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    $pp['productID'] = $getBulkRes['requests'][$key]['records'][0]['productID'];
                    // $param['productID'] = $mp->erplyID;
                    $productWithPid[] = $pp;
                }else{

                }

                
            }

        }

        // dd($productWithPid);
        $finalV2Req = $productWithPid;
        if(count($productWithPid) < 1){
            info("Variation Product Not found");
            return response("Variation Product Not found");
        }

        // die;
        // info("TOD REQ ". count($bundleReq));
        // return response()->json($bundleReq);
        // die;
        // info($bundleReq);
        // info("TOT REQ ". count($bundleReq));
        
        
        // info($bundleReq);
        $bundleReq = json_encode($productWithPid,true);
        // $bulkparam = array(
        //     "lang" => 'eng',
        //     "responseType" => "json",
        //     "sessionKey" => $this->api->client->sessionKey,
        // );

        info("Variation Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($finalV2Req as $key => $vp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){
                    $this->variation->where('ERPLYSKU', $vp['code'])->update(["erplyPending" => 0, "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID'] ]);
                     
                    if($isFirstMaxLimit == false){
                        $this->matrix->where('erplyID', $vp['parentProductID'])->update(['variationPending' => 0]);
                    }
                    info("Variation product create or updated ". $vp['code']);
                }else{
                    // info($bulkRes['requests'][$key]);
                    if($bulkRes['requests'][$key]['status']['errorField'] == "code2"){
                        $this->variation->where('ERPLYSKU', $vp['code'])->update(["barcodeDuplicate" => 1 ]);
                        $this->matrix->where('erplyID', $vp['parentProductID'])->update(['barcodeDuplicate' => 1]);
                        info(" Barcode Duplicate". $vp['code']);
                    }else{
                        info("Error Variation product create or updated ". $vp['code']." error code ".$bulkRes['requests'][$key]['status']['errorCode'] );
                    }
                    
                }
            }
        }

        return response()->json(['status' => 'success', 'response' => $bulkRes]);
    }


    public function updateErplySkuIcsc(){
        
        $variations = $this->variation->where("vUpdate", 1)->where("ICSC", "<>", "")->where("erplyID", ">", 0)->where("erplyPending", 0)->limit(99)->get();

        $bulkRequest = array();
        foreach($variations as $vp){
            $param = array(
                "clientCode" => $this->api->client->clientCode,
                "sessionKey" => $this->api->client->sessionKey,
                "requestName" => "saveProduct",
                "productID" => $vp->erplyID,
                "code" => $vp->ERPLYSKU,
                "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ERPLYSKU,
                "attributeName1" => "ICSC",
                "attributeType1" => "text",
                "attributeValue1" => $vp->ICSC
            );

            $bulkRequest[] = $param;
        }

        if(count($bulkRequest) < 1){
            return response("All Variation Products Updated");
        }

        $bulkRequest = json_encode($bulkRequest, true);

        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        );

        info("Variation Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bulkRequest, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            foreach($variations as $vp){
                $vp->vUpdate = 0;
                $vp->save();
            }
        }

        return response()->json($bulkRes);
    }


    public function productCheck($sku){ 
        info("Checking Erply Exist ". $sku);
        $count = 1;  
        $checkParam = array(
            "code" => "$sku",
            "sessionKey" => $this->api->client->sessionKey,
        );
           
           
        $checkRes = $this->api->sendRequest("getProducts", $checkParam,0,0,0);
        //  dd($checkRes);
        if($checkRes['status']['errorCode'] == 0 && !empty($checkRes['records'])){ 
            info("Product exist in erply db ".$checkRes['records'][0]['productID']);
            //create variation products
            return $checkRes['records'][0]['productID'];
        }
         
        return '';  
    } 
    //update product to Archived where erply enabled = 0
    public function updateMatrixPorductStatus(){

    }

    public function updateVariationPorductStatus(){

    } 
    public function checkProductExistInErply(){
        
        $wh = LiveWarehouseLocation::where("ENTITY", $this->api->client->ENTITY)->pluck("LocationID")->toArray();
        $datas = LiveProductVariation::where('erplyDeleted', 1)->whereNotIn("DefaultStore", $wh)->where("checkErply", 1)->limit(100)->get();
        $flag = false;
        if($datas->isEmpty()){
            $flag = true;
            $datas = LiveProductMatrix::where('erplyDeleted', 1)->whereNotIn("DefaultStore", $wh)->where("checkErply", 1)->limit(100)->get();
        }

        if($datas->isEmpty()){
            info("All Deleted Product Checked.");
            return response("All Deleted Product Checked");
        }
        // dd($datas);

        $getBulkReq = array();
        foreach($datas as $data){
            $checkParam = array(
                "requestName" => "getProducts",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "code" =>  $flag == true ? $data->WEBSKU : $data->ERPLYSKU,
                "getFields" => "productID,type"
            );
            $getBulkReq[] = $checkParam;
        }
        if(count($getBulkReq) < 1){
            info("All Deleted Product Checked.");
            return response("All Deleted Product Checked");
        }
        $getBulkReq = json_encode($getBulkReq, true);
        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        );

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);
    
        if($getBulkRes['status']['errorCode'] == 0){
            foreach($datas as $key => $data){
                if($getBulkRes["requests"][$key]['status']['errorCode'] == 0){
                    if(empty($getBulkRes['requests'][$key]['records'])){
                        //product deleted in erply
                        //just update erply pending = 1 and erplyID null 
                        // info("Empty Get Records...");
                        $data->erplyPending = 1;
                        $data->erplyID = null;
                        $data->checkErply = 0;
                        $data->save();
                        info("Product Deleted From Erply.");
                    }else{
                        //product exists in erply 
                        //just update erplyDeleted = 0
                        $data->erplyID = $getBulkRes['requests'][$key]['records'][0]["productID"];
                        $data->erplyDeleted = 0;
                        $data->checkErply = 1;
                        $data->erplyPending = 1;
                        $data->save();
                        info("Product Doesn't Deleted From Erply.");
                    }
                }
            }
        }

        info("Checking product exists in erply...");

        return response()->json($getBulkRes);


    }

 
 
}
