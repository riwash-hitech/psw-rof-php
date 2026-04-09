<?php

namespace App\Http\Controllers\LivePushErply\Services;
 
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\LiveProductCategory;
use App\Models\PswClientLive\Local\LiveProductDescription;
use App\Models\PswClientLive\Local\LiveProductGenericMatrix;
use App\Models\PswClientLive\Local\LiveProductGenericVariation;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductVariation;
use Illuminate\Http\Request;
use App\Traits\DebugTrait;
use Illuminate\Support\Facades\DB;

class ProductGenericService{
    //

    use DebugTrait;
    protected $api; 

    public function __construct(EAPIService $api){
        $this->api = $api; 
    }

    public function syncMatrixProduct($req){
        $isDebug = '';
        if($req->debug){
            $isDebug = 1;
        }

        $groupID = 0;
        if($this->api->isLiveEnv() == 0 ){
            //Academy Staging group id 
             
            $groupID = 2283;
            
        }

        if($this->api->isLiveEnv() == 1 ){
            //Academy Staging group id 
            if($this->api->flag == true){
                $groupID = 1174;
            }

            if($this->api->flag == false){
                $groupID = 1102;
            }
        }

        $isAcademy = $this->api->client->ENTITY == "Academy" ? true : false;
         
        $limit = $req->limit ? $req->limit : 100;
        if($req->sku){
            $matrix = LiveProductGenericMatrix::
                    // join("newsystem_product_group_live", "newsystem_product_group_live.SchoolName", "newsystem_product_matrix_live.SchoolName")
                    join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_generic_matrix_live.ProductType")
                    ->where('ERPLYSKU', $req->sku)
                    // ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->select(["newsystem_product_generic_matrix_live.*","newsystem_product_category_live.erplyCatID","newsystem_product_category_live.pswCatID"])
                    ->get();
        }else{
            $matrix = LiveProductGenericMatrix::
                    // join("newsystem_product_group_live", "newsystem_product_group_live.SchoolName", "newsystem_product_matrix_live.SchoolName")
                    join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_generic_matrix_live.ProductType") 
                    // ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->whereIn($isAcademy == true ? 'newsystem_product_generic_matrix_live.erplyPending' : 'newsystem_product_generic_matrix_live.pswPending', [1,2])
                    // ->where('newsystem_product_matrix_live.mUpdate', 1)
                    ->select(["newsystem_product_generic_matrix_live.*","newsystem_product_category_live.erplyCatID","newsystem_product_category_live.pswCatID"])
                    ->orderBy("updated_at", 'asc')
                    ->limit($limit)
                    ->get();
        }
        // dd($matrix);

        if($matrix->isEmpty()){
            info("Synccare to Erply : All Generic Matrix Product Synced.");
            return response()->json(["status" => "success", "message" => "Synccare to Erply : All Generic Matrix Product Synced."]);
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
                "code" =>  $mp->ERPLYSKU,
                "orderBy" => "productID",
                "orderByDir" => "asc"
            );
            $getBulkReq[] = $checkParam;
        }

        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);
        // dd($getBulkRes);
        if($getBulkRes["status"]["errorCode"] != 0){
            info("Error While getting Generic Matrix products by code ".$getBulkRes["status"]["errorCode"]);
            return response("Error While getting generic matrix products by code ".$getBulkRes["status"]["errorCode"]);
        }


        $bundleReq = array();
        foreach($matrix as $mkey => $mp){
            // $v = $this->variation->where('web_sku', $mp->web_sku)->where('newSystemInternetActive', 1)->first();
            // $pid = '';
            // $pid =  $this->productCheck($mp->ERPLYSKU);
            if($getBulkRes["requests"][$mkey]["status"]["errorCode"] == 0){ 
                $param = array(
                    "requestName" => "saveProduct",
                    "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                    "clientCode" => $this->api->client->clientCode,
                    "type" => "MATRIX", 
                    "code" => $mp->ERPLYSKU,
                    "status" => "NOT_FOR_SALE",
                    // "code2" => "",
                    // "code3" => $mp->ConfigName,
                    // "code5" => $mp->PLMStatus,
                    // "code6" => $mp->ProductSubType,
                    // "code7" => $mp->Category_Name,
                    // "code8" => $mp->WebEnabled == 1 ? 'Y' : 'N',
                    // "code9" => $mp->Category_Name,
                    // "displayedInWebshop" => $mp->WebEnabled,
                    // "active" => $mp->newSystemInternetActive,
                    // "status" => $mp->newSystemInternetActive == 1 ? "ACTIVE" : 'ARCHIVED',
                    "name" => $mp->ItemName,
                    // "description" => $mp->newSystemShortDescription != '' ? $mp->newSystemShortDescription : $mp->newSystemStockDescription,
                    // "longdesc" => $mp->newSystemLongDescription,
                    "groupID" => $groupID,
                    "categoryID" => $isAcademy == true ? $mp->erplyCatID : $mp->pswCatID,
                    "priceWithVAT" => $mp->RetailSalesPrice,
                    // "length" => ,
                    // "width" => ,
                    // "height" => ,
                    // "netWeight" => (double)$mp->ItemWeightGrams/1000,
                    // "sessionKey" => $this->api->client->sessionKey,
                    "dimensionID1" => 1,
                    "dimensionID2" => 8, 
                );

                $des = LiveProductDescription::where('WEBSKU', $mp->WEBSKU)->first();
                if($des){
                    $param["longdesc"] = $des->LongDescription;
                }
    

                if(!empty($getBulkRes["requests"][$mkey]["records"])){
                    //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    $param['productID'] = $getBulkRes['requests'][$mkey]['records'][0]['productID'];
                    // $param['productID'] = $mp->erplyID;
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

        if($isDebug == 1){
            dd($bundleReq);
        }
        // dd($bundleReq);
        if(count($bundleReq) < 1){
            return response()->json(["message" => "No Product Found"]);
        }
        // info($bundleReq);

        $matrixReq = $bundleReq;
        
        $bundleReq = json_encode($bundleReq,true);
        // $bulkparam = array(
        //     "lang" => 'eng',
        //     "responseType" => "json",
        //     "sessionKey" => $this->api->client->sessionKey,
        // );

        info("Generic Matrix Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($matrixReq as $key => $mp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){

                    if($isAcademy == true){
                        LiveProductGenericMatrix::where('ERPLYSKU', $mp["code"])->update(
                            [
                                "erplyPending" => 0,
                                "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                            ]
                        );
                    }

                    if($isAcademy == false){
                        LiveProductGenericMatrix::where('ERPLYSKU', $mp["code"])->update(
                            [
                                "pswPending" => 0,
                                "pswErplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                            ]
                        );
                    }
                    
                    // $mp->erplyPending = 0;
                    // // $mp->mUpdate = 0;
                    // $mp->erplyID = $bulkRes['requests'][$key]['records'][0]['productID'];
                    // $mp->save();
                    // info("Generic matrix product generic create or updated ".$bulkRes['requests'][$key]['records'][0]['productID']." sku ".$mp['code']);
                }else{
                    if($isAcademy == true){
                        LiveProductGenericMatrix::where('ERPLYSKU', $mp["code"])->update(
                            [
                                "erplyPending" => 2,
                                // "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                            ]
                        );
                    }

                    if($isAcademy == false){
                        LiveProductGenericMatrix::where('ERPLYSKU', $mp["code"])->update(
                            [
                                "pswPending" => 2,
                                // "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                            ]
                        );
                    }
                    

                    info("Error matrix product generic create or updated ".$bulkRes['requests'][$key]['status']['errorCode'].' SKU '. $mp['code']);
                }
            }
            info($this->api->client->ENTITY."********************************************* Generic matrix product generic create or updated****************************************************");
        }

        return response()->json(['status' => 'success', 'response' => $bulkRes]);
    }

    public function syncMatrixProductV2($req){

        if(env('isLive') == true){
            $checkCron = false;
            if(@$req->isfinal == true){
                $checkCron = true;
            }
            if($checkCron == false){
                // info("******************************* Generic Matrix Update Cron Blocked ********************");
                die;
            }
        }

        $groupID = 0;
        if($this->api->isLiveEnv() == false ){
            //Academy Staging group id 
            if($this->api->flag == true){
                $groupID = 2283;
            }
            if($this->api->flag == false){
                $groupID = 0;
            }
        }

        if(env('isLive') == true ){
            //Academy Staging group id 
            if($this->api->flag == true){
                $groupID = 1174;
            }
            if($this->api->flag == false){
                $groupID = 0;
            }
        }


        $limit = $req->limit ? $req->limit : 100;
        if($req->sku){
            $matrix = LiveProductGenericMatrix::
                    // join("newsystem_product_group_live", "newsystem_product_group_live.SchoolName", "newsystem_product_matrix_live.SchoolName")
                    join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_generic_matrix_live.ProductType")
                    ->where('ERPLYSKU', $req->sku)
                    // ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where($this->api->flag == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->select(["newsystem_product_generic_matrix_live.*","newsystem_product_category_live.erplyCatID","newsystem_product_category_live.pswCatID"])
                    ->get();
        }else{
            $matrix = LiveProductGenericMatrix::
                    // join("newsystem_product_group_live", "newsystem_product_group_live.SchoolName", "newsystem_product_matrix_live.SchoolName")
                    join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_generic_matrix_live.ProductType") 
                    // ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where($this->api->flag == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->where($this->api->flag == true ? 'newsystem_product_generic_matrix_live.erplyPending' : 'newsystem_product_generic_matrix_live.pswPending', 1)
                    // ->where('newsystem_product_matrix_live.mUpdate', 1)
                    ->select(["newsystem_product_generic_matrix_live.*","newsystem_product_category_live.erplyCatID","newsystem_product_category_live.pswCatID"])
                    ->limit($limit)
                    ->get();
        }
        // dd($matrix);

        if($matrix->isEmpty()){
            info("Synccare to Erply : All Generic Matrix Product Synced.");
            return response()->json(["status" => "success", "message" => "Synccare to Erply : All Generic Matrix Product Synced."]);
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
                "code" =>  $mp->ERPLYSKU,
                "orderBy" => "productID",
                "orderByDir" => "asc"
            );
            $getBulkReq[] = $checkParam;
        }

        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);
        // dd($getBulkRes);
        if($getBulkRes["status"]["errorCode"] != 0){
            info("Error While getting Generic Matrix products by code ".$getBulkRes["status"]["errorCode"]);
            return response("Error While getting generic matrix products by code ".$getBulkRes["status"]["errorCode"]);
        }


        $bundleReq = array();
        foreach($matrix as $mkey => $mp){
            // $v = $this->variation->where('web_sku', $mp->web_sku)->where('newSystemInternetActive', 1)->first();
            // $pid = '';
            // $pid =  $this->productCheck($mp->ERPLYSKU);
            if($getBulkRes["requests"][$mkey]["status"]["errorCode"] == 0){ 
                $param = array(
                    "requestName" => "saveProduct",
                    "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                    "clientCode" => $this->api->client->clientCode,
                    "type" => "MATRIX", 
                    "code" => $mp->ERPLYSKU,
                    "status" => "NOT_FOR_SALE",
                    // "code2" => "",
                    // "code3" => $mp->ConfigName,
                    // "code5" => $mp->PLMStatus,
                    // "code6" => $mp->ProductSubType,
                    // "code7" => $mp->Category_Name,
                    // "code8" => $mp->WebEnabled == 1 ? 'Y' : 'N',
                    // "code9" => $mp->Category_Name,
                    // "displayedInWebshop" => $mp->WebEnabled,
                    // "active" => $mp->newSystemInternetActive,
                    // "status" => $mp->newSystemInternetActive == 1 ? "ACTIVE" : 'ARCHIVED',
                    "name" => $mp->ItemName,
                    // "description" => $mp->newSystemShortDescription != '' ? $mp->newSystemShortDescription : $mp->newSystemStockDescription,
                    // "longdesc" => $mp->newSystemLongDescription,
                    "groupID" => $groupID,
                    "categoryID" => $this->api->flag == true ? $mp->erplyCatID : $mp->pswCatID,
                    "priceWithVAT" => $mp->RetailSalesPrice,
                    // "length" => ,
                    // "width" => ,
                    // "height" => ,
                    // "netWeight" => (double)$mp->ItemWeightGrams/1000,
                    // "sessionKey" => $this->api->client->sessionKey,
                    "dimensionID1" => 1,
                    "dimensionID2" => 8, 
                );

                $des = LiveProductDescription::where('WEBSKU', $mp->WEBSKU)->first();
                if($des){
                    $param["longdesc"] = $des->LongDescription;
                }
    

                if(!empty($getBulkRes["requests"][$mkey]["records"])){
                    //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    $param['productID'] = $getBulkRes['requests'][$mkey]['records'][0]['productID'];
                    // $param['productID'] = $mp->erplyID;
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
        // $bulkparam = array(
        //     "lang" => 'eng',
        //     "responseType" => "json",
        //     "sessionKey" => $this->api->client->sessionKey,
        // );

        info("Generic Matrix Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($matrixReq as $key => $mp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){

                    if($this->api->flag == true){
                        LiveProductGenericMatrix::where('ERPLYSKU', $mp["code"])->update(
                            [
                                "erplyPending" => 0,
                                "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                            ]
                        );
                    }
                    if($this->api->flag == false){
                        LiveProductGenericMatrix::where('ERPLYSKU', $mp["code"])->update(
                            [
                                "erplyPending" => 0,
                                "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                            ]
                        );
                    }
                    // $mp->erplyPending = 0;
                    // // $mp->mUpdate = 0;
                    // $mp->erplyID = $bulkRes['requests'][$key]['records'][0]['productID'];
                    // $mp->save();
                    // info("Generic matrix product generic create or updated ".$bulkRes['requests'][$key]['records'][0]['productID']." sku ".$mp['code']);
                }else{
                    LiveProductGenericMatrix::where('ERPLYSKU', $mp["code"])->update(
                        [
                            "erplyPending" => 2,
                            "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID']
                        ]
                    );
                    info("Error matrix product generic create or updated ".$bulkRes['requests'][$key]['status']['errorCode'].' SKU '. $mp['code']);
                }
            }
            $title = $this->api->flag == true ? "Academy" : "PSW";
            info("*********************************************".$title." Generic matrix product generic create or updated****************************************************");

        }

        return response()->json(['status' => 'success', 'response' => $bulkRes]);
    }


    public function checkNullPendingProduct($req){

        $isAcademy = $this->api->client->ENTITY == "Academy" ? true : false;

        //now checking if pending variation product and parent product variation pending 0 then update to 1
        $pendingVariationByFlag = LiveProductGenericVariation::whereNull($isAcademy == true ? "erplyID" : "pswErplyID")->orderBy("updated_at", "asc")->limit(50)->get();
        // dd($pendingVariationByFlag);

        // $this->setInfo($pendingVariationByFlag,1,"HEllo from ****************************". $this->api->client->ENTITY);

        foreach($pendingVariationByFlag as $pvbf){
            $updateCol = $isAcademy == true ? "variationPending" : "pswVariationPending";
            $updateCol0 = $isAcademy == true ? "erplyPending" : "pswPending";
            LiveProductGenericVariation::where("ERPLYSKU", $pvbf->ERPLYSKU)->update([ $updateCol0 => 1, "updated_at" => date('Y-m-d H:i:s')]);

            //first check matrix 
            // echo $pvbf->ERPLYSKU;

            // $matrix = DB::connection("mysql2")->select("snewsystem_product_generic_matrix_live")

            $matrix = LiveProductGenericMatrix::where("ERPLYSKU", $pvbf->ERPLYSKU)->update([ $updateCol => 1]);

            // dd($matrix, $pvbf);

            // LiveProductGenericMatrix::where("ERPLYSKU", $pvbf->ERPLYSK)->update([ $updateCol => 1]);
            // $pvbf->updated_at = date('Y-m-d H:i:s');
            // $pvbf->save();
        }

        echo  "Done";
    }

    public function syncVariationProduct($req){
        $isDebug = '';
        if($req->debug){
            $isDebug = 1;
        }
        $isAcademy = $this->api->client->ENTITY == "Academy" ? true : false;

        if($this->api->client->ENV == 1){
            $checkCron = false;
            if(@$req->isfinal == true){
                $checkCron = true;
            }
            if($checkCron == false){
                // info("*******************************Generic Variation Update Cron Blocked ********************");
                die;
            }
        }

        $groupID = 0;
        if($this->api->client->ENV == 0 ){
            //Academy Staging group id 
            if($this->api->flag == true){
                $groupID = 2283;
            }
            if($this->api->flag == false){
                $groupID = 0;
            }
        }

        if($this->api->client->ENV == 1){
            //Academy Staging group id 
            if($this->api->client->ENTITY == "Academy"){
                $groupID = 1174;
            }
            if($this->api->client->ENTITY == "PSW"){
                $groupID = 1102;
            }
        }
       
        info("Variation Sync Called");
        $limit = $req->limit ? $req->limit : 40;
        if($req->sku){
            $matrix = LiveProductGenericMatrix::
                    // join("newsystem_product_group_live", "newsystem_product_group_live.SchoolName", "newsystem_product_matrix_live.SchoolName")
                    join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_generic_matrix_live.ProductType")
                    ->where('newsystem_product_generic_matrix_live.ERPLYSKU', $req->sku)
                    ->where($isAcademy == true ? 'newsystem_product_generic_matrix_live.erplyPending' : 'newsystem_product_generic_matrix_live.pswPending', 0)
                    // ->where('newsystem_product_matrix_live.colorFlag', 0)
                    // ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->select(["newsystem_product_generic_matrix_live.*", "newsystem_product_category_live.erplyCatID", "newsystem_product_category_live.pswCatID"])
                    ->get();
        }else{
            $matrix = LiveProductGenericMatrix::
                    // join("newsystem_product_group_live", "newsystem_product_group_live.SchoolName", "newsystem_product_matrix_live.SchoolName")
                    join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_generic_matrix_live.ProductType") 
                    // ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_generic_matrix_live.erplyPending' : 'newsystem_product_generic_matrix_live.pswPending', 0)
                    
                    // ->where('newsystem_product_matrix_live.barcodeDuplicate', 0)
                    ->where($isAcademy == true ? 'newsystem_product_generic_matrix_live.variationPending' : 'newsystem_product_generic_matrix_live.pswVariationPending', 1)
                    // ->where('newsystem_product_matrix_live.colorFlag', 0)
                    ->select(["newsystem_product_generic_matrix_live.*", "newsystem_product_category_live.erplyCatID", "newsystem_product_category_live.pswCatID"])
                    // ->limit($limit)
                    // ->inRandomOrder()
                    ->limit($limit)
                    ->orderBy("updated_at", 'asc')
                    // ->inRandomOrder(1)
                    ->get();
        }

        // if($isDebug == 1){
        //     dd($matrix);
        // }

        // info("Matrix Fetched");
        // dd($matrix);
        if(count($matrix) < 1){

            //now checking if psw pending 2 product
            $pending2 = LiveProductGenericVariation::where($isAcademy == true ? "erplyPending" : "pswPending", 2)->pluck("ERPLYSKU")->toArray();
            
            LiveProductGenericVariation::whereIn("ERPLYSKU", $pending2)
                                        ->update(
                                            [
                                                $isAcademy == true ? "erplyPending" : "pswPending" => 1
                                            ]
                                        );
            LiveProductGenericMatrix::whereIn("ERPLYSKU", $pending2)->update(
                [
                    $isAcademy == true ? "variationPending" : "pswVariationPending" => 1
                ]
            );

            



            info("All Variation Product generic Synced to Erply ". $this->api->client->ENTITY);
            return response()->json(["message" => "All Variation Product generic Synced to Erply "]);
        }
        // dd($matrix);
        // info($matrix);
        //Now Getting Matrix Variation According to Matrix SKU
        $msku = array();
        $bundleReq = array();
        $vCount = 0;
        $isFirstMaxLimit = false;
        foreach($matrix as $keym => $mp){
            if($isAcademy == true){
                $mp->variationPending = 1;
                $mp->save();
            }else{
                $mp->pswVariationPending = 1;
                $mp->save();
            }
            // dd($mp);
            $variation = LiveProductGenericVariation::
                    // ->rightJoin('newsystem_product_description_live', 'newsystem_product_description_live.WEBSKU','=' ,'newsystem_product_variation_live.WEBSKU')
                    // join("newsystem_product_group_live", "newsystem_product_group_live.SchoolName", "newsystem_product_variation_live.SchoolName")
                    join("newsystem_product_category_live", "newsystem_product_category_live.name", "newsystem_product_generic_variation_live.ProductType") 
                    ->leftJoin('newsystem_product_size_sort_order_live', 'newsystem_product_size_sort_order_live.size', 'newsystem_product_generic_variation_live.SizeID')
                    ->join('newsystem_product_color_live', 'newsystem_product_color_live.name', 'newsystem_product_generic_variation_live.ColourName')
                    ->join('newsystem_product_size_live', 'newsystem_product_size_live.name', 'newsystem_product_generic_variation_live.SizeID')
                    // ->leftJoin('newsystem_product_description_live', 'newsystem_product_description_live.WEBSKU','=' ,'newsystem_product_variation_live.WEBSKU')
                    // ->where('newsystem_product_group_live.erplyGroupID','>', 0)
                    // ->where('newsystem_product_category_live.erplyCatID', '>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_category_live.erplyCatID' : 'newsystem_product_category_live.pswCatID', '>', 0)
                    // ->where('newsystem_product_color_live.erplyColorID', '>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_color_live.erplyColorID' : 'newsystem_product_color_live.pswColorID', '>', 0)
                    // ->where('newsystem_product_size_live.erplySizeID', '>', 0)
                    ->where($isAcademy == true ? 'newsystem_product_size_live.erplySizeID' : 'newsystem_product_size_live.pswSizeID', '>', 0)
                    ->where('newsystem_product_generic_variation_live.ERPLYSKU', $mp->ERPLYSKU)
                    ->where($isAcademy == true ? 'newsystem_product_generic_variation_live.erplyPending' : 'newsystem_product_generic_variation_live.pswPending', 1)
                    // ->whereNull('newsystem_product_generic_variation_live.errorMsg')
                    // ->where('newsystem_product_variation_live.barcodeDuplicate', 0)
                    // ->where('newsystem_product_variation_live.colorFlag', 0)
                    ->select(
                        [
                            "newsystem_product_generic_variation_live.*",
                            "newsystem_product_category_live.erplyCatID",
                            "newsystem_product_category_live.pswCatID",
                            "newsystem_product_color_live.erplyColorID",
                            "newsystem_product_color_live.pswColorID",
                            "newsystem_product_size_live.erplySizeID", 
                            "newsystem_product_size_live.pswSizeID", 
                        ])
                    ->orderBy('newsystem_product_size_sort_order_live.sort_order', 'asc')
                    // ->limit(1)
                    // ->toSql();
                    ->get();
            
            // dd($variation);

            if(count($variation) < 1){
                info("Empty Variations");
                // check first is color is - than flag it
                // $chkV = LiveProductGenericVariation::where('ERPLYSKU', $mp->ERPLYSKU)->first();
                // if($chkV){
                //     if($chkV->ColourName == "-"){
                //         //let's flag as not color
                //         info("No Color Flag Updated");
                //         LiveProductGenericVariation::where('ERPLYSKU', $mp->ERPLYSKU)->update(['colorFlag' => 1]);
                //         LiveProductGenericMatrix::where('ERPLYSKU', $mp->ERPLYSKU)->update(['colorFlag' => 1]);
                //     }else{
                //         LiveProductGenericVariation::where('ERPLYSKU', $mp->ERPLYSKU)->update(['colorFlag' => 1]);
                //         LiveProductGenericMatrix::where('ERPLYSKU', $mp->ERPLYSKU)->update(['colorFlag' => 1]);
                //     }
                // }
            }        
            // dd($variation);
            // die;
            info("Variation Fetched ". $mp->ERPLYSKU);
            if($keym == 0){
                if(count($variation) > 99){
                    $isFirstMaxLimit = true;
                    info("First Max Limit Crossed ". count($variation));
                }
            }
            // if($isFirstMaxLimit == true){
            //     echo "Max Limit Crossed";
            //     die;
            // }
            $vCount = $vCount + count($variation);
            if($isFirstMaxLimit == false){
                
                if($vCount > 99){ 
                    $vCount = $vCount - count($variation); 
                    break;
                }else{
                    foreach($variation as $key => $vp){
                        $mm = LiveProductGenericMatrix::where('ERPLYSKU', $vp->ERPLYSKU)->first();
                        
                        // $pid = '';
                        // $pid =  $this->productCheck($vp->ICSC);
                        $param = array(
                            "requestName" => "saveProduct",
                            "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                            "clientCode" => $this->api->client->clientCode,
                            "type" => "PRODUCT",
                            "dimValueID1" => $isAcademy == true ? $vp->erplyColorID : $vp->pswColorID,
                            "dimValueID2" => $isAcademy == true ? $vp->erplySizeID : $vp->pswSizeID,
                            "parentProductID" => $isAcademy == true ? $mm->erplyID : $mm->pswErplyID,
                            "status" => "NOT_FOR_SALE",
                            // "groupID" => $this->getGroupID($product->web_sku),
                            "code" => $vp->ICSC,
                            "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ICSC,
                            "code3" => $vp->ConfigName,
                            // "code5" => $vp->PLMStatus,
                            // "code6" => $vp->ProductSubType,
                            // "code7" => $vp->Category_Name,
                            // "code8" => $vp->WebEnabled == 1 ? 'Y' : 'N',
                            // "code9" => $vp->DefaultStore,
                            // "displayedInWebshop" => $vp->WebEnabled,
                            // "active" => $vp->newSystemInternetActive,
                            // "status" => $vp->newSystemInternetActive == 1 ? "ACTIVE" : 'ARCHIVED',
                            "name" => $vp->ItemName.' '.$vp->ColourName.' '.$vp->SizeID,
                            // "description" => $vp->newSystemShortDescription != '' ? $vp->newSystemShortDescription : $vp->newSystemStockDescription,
                            // "longdesc" => $vp->newSystemLongDescription,
                            "groupID" => $groupID,
                            "categoryID" => $isAcademy == true ? $vp->erplyCatID : $vp->pswCatID,
                            "priceWithVAT" => $vp->RetailSalesPrice,
                            // "length" => ,
                            // "width" => ,
                            // "height" => ,
                            // "netWeight" => (double)$vp->ItemWeightGrams /1000,
                            // "sessionKey" => $this->api->client->sessionKey, 
                        );

                        $des = LiveProductDescription::where('WEBSKU', $vp->ERPLYSKU)->first();
                        if($des){
                            $param["longdesc"] = $des->LongDescription;
                        }
            
                        // if($pid != ''){
                        //     //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                        //     $param['productID'] = $pid;
                        //     // $param['productID'] = $vp->erplyID;
                        // }
                        //adding attributes\
                        $index = 1;
                        foreach($vp->toArray() as $key => $val){
                            if($key == "ITEMID" || $key == "ColourID" || $key == "SizeID"){
                                $param["attributeName".$index] = $key;
                                $param["attributeType".$index] =  'text';
                                $param["attributeValue".$index] = $val;
                                $index++;
                            }
                        }
                        array_push($bundleReq, $param);
                    }
                }
            }else{
                foreach($variation as $key => $vp){

                    if($key >= 99){
                        break 2;
                    }

                    $mm = LiveProductGenericMatrix::where('ERPLYSKU', $vp->ERPLYSKU)->first();
        
                    // $pid =  $this->productCheck($vp->ICSC);
                    // $pid = '';
                    $param = array(
                        "requestName" => "saveProduct",
                        "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                        "clientCode" => $this->api->client->clientCode,
                        "type" => "PRODUCT",
                        "dimValueID1" => $isAcademy == true ? $vp->erplyColorID : $vp->pswColorID,
                        "dimValueID2" => $isAcademy == true ? $vp->erplySizeID : $vp->pswSizeID,
                        "parentProductID" => $isAcademy == true ? $mm->erplyID : $mm->pswErplyID,
                        // "groupID" => $this->getGroupID($product->web_sku),
                        "code" => $vp->ICSC,
                        "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ICSC,
                        "code3" => $vp->ConfigName,
                        "status" => "NOT_FOR_SALE",
                        // "code5" => $vp->PLMStatus,
                        // "code6" => $vp->ProductSubType,
                        // "code7" => $vp->Category_Name,
                        // "code8" => $vp->WebEnabled == 1 ? 'Y' : 'N',
                        // "code9" => $vp->DefaultStore,
                        // "displayedInWebshop" => $vp->WebEnabled,
                        // "active" => $vp->newSystemInternetActive,
                        // "status" => $vp->newSystemInternetActive == 1 ? "ACTIVE" : 'ARCHIVED',
                        "name" => $vp->ItemName.' '.$vp->ColourName.' '.$vp->SizeID,
                        // "description" => $vp->newSystemShortDescription != '' ? $vp->newSystemShortDescription : $vp->newSystemStockDescription,
                        // "longdesc" => $vp->newSystemLongDescription,
                        "groupID" => $groupID,
                        "categoryID" => $isAcademy == true ? $vp->erplyCatID : $vp->pswCatID,
                        "priceWithVAT" => $vp->RetailSalesPrice,
                        // "length" => ,
                        // "width" => ,
                        // "height" => ,
                        // "netWeight" => (double)$vp->ItemWeightGrams /1000,
                        // "sessionKey" => $this->api->client->sessionKey, 
                    );
                    
                    $des = LiveProductDescription::where('WEBSKU', $vp->ERPLYSKU)->first();
                    if($des){
                        $param["longdesc"] = $des->LongDescription;
                    }

                    // if($pid != ''){
                    //     //IF PRODUCT ID IS NOT EMPTY THAN ADD PRODUCT ID TO REQUEST PARAMETER AND IT WILL UPDATE PRODUCT
                    //     $param['productID'] = $pid;
                    //     // $param['productID'] = $vp->erplyID;
                    // }
                    //adding attributes\
                    $index = 1;
                    foreach($vp->toArray() as $key => $val){
                        if($key == "ITEMID" || $key == "ColourID" || $key == "SizeID"){
                            $param["attributeName".$index] = $key;
                            $param["attributeType".$index] =  'text';
                            $param["attributeValue".$index] = $val;
                            $index++;
                        }
                    }
                    array_push($bundleReq, $param);
                }
            } 
        }

        // dd($bundleReq);

        info("TOT REQ ". count($bundleReq));
        // $bulkReqFinal = $bundleReq;
        // dd($bulkReqFinal);
        // die;
        // info("TOD REQ ". count($bundleReq));
        // return response()->json($bundleReq);
        // die;
        // info($bundleReq);
        // info("TOT REQ ". count($bundleReq));
        
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
                }

                $productWithPid[] = $pp;
            }

        }

        if($isDebug == 1){
            dd($productWithPid);
        }

        // dd($productWithPid);
        $finalV2Req = $productWithPid;
        if(count($productWithPid) < 1){
            info("Generic Variation Product Not found");
            return response("Generic Variation Product Not found");
        }

        // info($bundleReq);
        $bundleReq = json_encode($productWithPid,true);
         

        info("Generic Variation Bulk Save Request Calling...");
        $bulkRes = $this->api->sendRequest($bundleReq, $bulkparam,1,0,0);

        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            //first update to db
            foreach($finalV2Req as $key => $vp){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){

                    if($isAcademy == true){
                        LiveProductGenericVariation::where('ICSC', $vp['code'])->update(
                            [
                                "erplyPending" => 0, 
                                "erplyID" => $bulkRes['requests'][$key]['records'][0]['productID'] 
                            ]
                        ); 
                        if($isFirstMaxLimit == false){
                            LiveProductGenericMatrix::where('erplyID', $vp['parentProductID'])->update(['variationPending' => 0]);
                        }
                    }

                    if($isAcademy == false){
                        LiveProductGenericVariation::where('ICSC', $vp['code'])->update(
                            [
                                "pswPending" => 0, 
                                "pswErplyID" => $bulkRes['requests'][$key]['records'][0]['productID'] 
                            ]
                        ); 
                        if($isFirstMaxLimit == false){
                            LiveProductGenericMatrix::where('pswErplyID', $vp['parentProductID'])->update(['pswVariationPending' => 0]);
                        }
                    }
                    

                    // info("Generic Variation product create or updated ". $vp['code']);
                }else{
                    // info($bulkRes['requests'][$key]);
                    // if($bulkRes['requests'][$key]['status']['errorField'] == "code2"){
                    //     LiveProductGenericVariation::where('ERPLYSKU', $vp['code'])->update(["barcodeDuplicate" => 1 ]);
                    //     LiveProductGenericMatrix::where('erplyID', $vp['parentProductID'])->update(['barcodeDuplicate' => 1]);
                    //     info(" Barcode Duplicate". $vp['code']);
                    // }else{
                    if($isAcademy == true){
                        LiveProductGenericVariation::where('ICSC', $vp['code'])
                        ->update(
                            [
                                // "errorMsg" => $bulkRes['requests'][$key]['status']['errorCode'].' ' 
                                "erplyPending" => 2
                            ]
                        );
                    }
                    if($isAcademy == false){
                        LiveProductGenericVariation::where('ICSC', $vp['code'])
                        ->update(
                            [
                                "errorMsg" => json_encode($bulkRes['requests'][$key]),
                                "pswPending" => 2
                            ]
                        );
                    }

                    info("Error Variation product create or updated ". $vp['code']." error code ".$bulkRes['requests'][$key]['status']['errorCode'].' '.$bulkRes['requests'][$key]['status']['errorField'] );
                    // }
                    
                }
            }
            $title = $isAcademy == true ? "Academy" : "PSW";
            info("********************************************".$title." Generic Variation Product Created or Updated to ERPLY***********************************************");
        }

        return response()->json(['status' => 'success', 'response' => $bulkRes]);
    }

    public function checkDuplicateProduct(){
        $products = LiveProductGenericMatrix::where("duplicateChecked", 1)->limit(100)->get();
        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey,
        );

        //first get bulk 
        $getBulkReq = array();
        foreach($products as $mp){
            $checkParam = array(
                "requestName" => "getProducts",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "code" =>  $mp->ERPLYSKU,
                "orderBy" => "productID",
                "orderByDir" => "asc"
            );
            $getBulkReq[] = $checkParam;
        }

        $getBulkReq = json_encode($getBulkReq, true);

        $getBulkRes = $this->api->sendRequest($getBulkReq, $bulkparam, 1);
        // dd($getBulkRes);
        foreach($products as $key => $mp){
            if($getBulkRes["requests"][$key]["status"]["errorCode"] == 0){
                // if(count($getBulkRes["requests"][$key]["records"]) > 1){
                    //this is duplicate product
                    $dupID = '';
                    foreach($getBulkRes["requests"][$key]["records"] as $rec){
                        if($mp->erplyID != $rec["productID"]){
                            $dupID .=  $rec["productID"].',';
                        }
                    }
                    $mp->duplicateChecked = 0;
                    $mp->pids = $dupID;
                    $mp->save();
                // }else{
                //     $mp->duplicateChecked = 0;
                //     $mp->pids = $dupID;
                //     $mp->save();
                // }
            }
        }

        return response()->json($getBulkRes["status"]);
    }


    // public function updateErplySkuIcsc(){
        
    //     $variations = $this->variation->where("vUpdate", 1)->where("ICSC", "<>", "")->where("erplyID", ">", 0)->where("erplyPending", 0)->limit(99)->get();

    //     $bulkRequest = array();
    //     foreach($variations as $vp){
    //         $param = array(
    //             "clientCode" => $this->api->client->clientCode,
    //             "sessionKey" => $this->api->client->sessionKey,
    //             "requestName" => "saveProduct",
    //             "productID" => $vp->erplyID,
    //             "code" => $vp->ERPLYSKU,
    //             "code2" => $vp->EANBarcode ? $vp->EANBarcode : $vp->ERPLYSKU,
    //             "attributeName1" => "ICSC",
    //             "attributeType1" => "text",
    //             "attributeValue1" => $vp->ICSC
    //         );

    //         $bulkRequest[] = $param;
    //     }

    //     if(count($bulkRequest) < 1){
    //         return response("All Variation Products Updated");
    //     }

    //     $bulkRequest = json_encode($bulkRequest, true);

    //     $bulkparam = array(
    //         "lang" => 'eng',
    //         "responseType" => "json",
    //         "sessionKey" => $this->api->client->sessionKey,
    //     );

    //     info("Variation Bulk Save Request Calling...");
    //     $bulkRes = $this->api->sendRequest($bulkRequest, $bulkparam,1,0,0);

    //     if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
    //         foreach($variations as $vp){
    //             $vp->vUpdate = 0;
    //             $vp->save();
    //         }
    //     }

    //     return response()->json($bulkRes);
    // }


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
 
 
}
