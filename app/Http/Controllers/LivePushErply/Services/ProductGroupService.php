<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;

class ProductGroupService{
    //
    protected $api;
    protected $group;

    public function __construct(EAPIService $api, LiveProductGroup $group){
        $this->api = $api;
        $this->group = $group;
       
    }

    public function syncProductGroup($req){
        
        return $this->syncProductGroupV2($req); 
        die;
        $groups = $this->group->
                // join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_group_live.parentSchoolGroup")
                // ->where("newsystem_product_group_live.pendingProcess", 0)
                // ->where("newsystem_product_group_live.parentPending", 1)
                // ->where("newstystem_store_location_live.erplyParentGroupID", ">", 0)
                // ->select(["newsystem_product_group_live.*", "newstystem_store_location_live.erplyParentGroupID"])
                whereNull('erplyGroupID')
                // where("erplyGID", '>', 0)
                // ->whereNull('parentSchoolGroup')
                ->orderBy("SchoolName",'asc')
                ->limit(100)
                ->get();
        // dd($groups);        
        if(count($groups) < 1){
            info("Synccare to Erply : All Group Synced.");
            return response()->json("All Group Synced");
            die;
        }
        $bulkParams = array();
        $bulkParam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        foreach($groups as $g){
            $param = $this->makeBundleGroup($g,$this->api->client->sessionKey);
            array_push($bulkParams, $param); 
        }

        if(count($bulkParams) < 1){
            info("All Group Synced");
            return response()->json("All Group Synced.");
        }
        // dd($bulkParams);

        $bulkParams = json_encode($bulkParams, true);

        $bulkRes = $this->api->sendRequest($bulkParams, $bulkParam,1,0,0);
        // info($bulkRes);
        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            foreach($groups as $key => $g){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){
                    $g->erplyGroupID = $bulkRes['requests'][$key]['records'][0]['productGroupID'];
                    // $g->pendingProcess = 0;
                    // $g->parentPending = 0;
                    $g->save();
                }
            }
            info("Product Group Created or Updated to Erply");
        }

        return response()->json(["status" => "success", "response" => $bulkRes]);

    }
 
    public function syncProductGroupV2($req){
        $isDebug = '';

        if($req->debug){
            $isDebug = $req->debug;
        }

        $sharedID = 0;
        if(env("isLive") == false){
            $sharedID = 3438;
        }

        if(env("isLive") == true){
            $sharedID = 0;
        }
        
        $datas = LiveProductGroup::join("newstystem_store_location_live", "newstystem_store_location_live.LocationID","newsystem_product_group_live.parentSchoolGroup")
                    // ->whereNull("newsystem_product_group_live.erplyGroupID")
                    ->where("newstystem_store_location_live.ENTITY", $this->api->client->ENTITY) 
                    ->where("newsystem_product_group_live.pendingProcess", 1)
                    // ->where("newsystem_product_group_live.erplyGroupID",'>', 1)
                    ->where("newstystem_store_location_live.parentGroupID", '>', 0)
                    
                    ->select("newsystem_product_group_live.*", "newstystem_store_location_live.parentGroupID")
                    ->orderBy("newsystem_product_group_live.SchoolName",'asc')
                    ->limit(100)
                    ->get();

        // dd($datas);
        if($datas->isEmpty()){
            info("All product group syncced to Erply");
            return response("All product group syncced to Erply");
        }

        if($isDebug == 1){
            dd($datas);
        }
        

        //first get req from erply
        $getBulk = array();

        foreach($datas as $data){
            $getParam = array(
                "searchAttributeName" => 'SchoolIDv2', 
                "searchAttributeValue" => $data->SchoolID,
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "getProductGroups",
            );
            $getBulk[] = $getParam;
        }

        if(count($getBulk) < 1){
            info("No Product Groups Founds.");
            return response("No product Groups Founds");
        }

        $getBulk = json_encode($getBulk, true);

        $bulkParam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        $getRes = $this->api->sendRequest($getBulk, $bulkParam, 1);

        // dd($getRes);

        if($getRes["status"]["errorCode"] != 0){
            info("Error while getting product groups from erply!!!");
            return response("Error while getting product groups from erply!!!");
        }

        $saveBulk = array();
        $deleteBulkTemp = array();
        foreach($datas as $key => $data){

            if($getRes["requests"][$key]["status"]["errorCode"] == 0){
                $param = array(
                    "requestName" => "saveProductGroup",
                    "sessionKey" => $this->api->client->sessionKey,
                    "clientCode" => $this->api->client->clientCode,
                    "name" => $data->SchoolName, 
                    "parentGroupID" => $data->checkShared == 0 ? $sharedID : $data->parentGroupID, 
                    "attributeName1" => "SchoolIDv2",
                    "attributeType1" => "int",
                    "attributeValue1" => $data->SchoolID 
                ); 

                // if($data->erplyGroupID > 0){
                //     $param["productGroupID"] = $data->erplyGroupID;
                // }

                if(!empty($getRes["requests"][$key]["records"])){
                    // $cc = count($getRes["requests"][$key]["records"]);
                    // if($cc > 1){
                    //     $data->erplyDuplicate = 1;
                    //     $data->save();
                    // }
                    $param["productGroupID"] = $getRes["requests"][$key]["records"][0]["productGroupID"];
 
                } 
                $saveBulk[] = $param;

            }
        }
        /**
         * TEMP CODE DELETE DUPLICATE PRODUCT ID
         */
        // dd($deleteBulkTemp);
        // die;
        //  $deleteBulkTemp = json_encode($deleteBulkTemp, true);
        //  $res = $this->api->sendRequest($deleteBulkTemp, $bulkParam, 1);

        // dd($res);
        // die;
        if($isDebug == 11){
            dd($saveBulk);
        }
        // dd($saveBulk);

        if(count($saveBulk) < 1){
            info("No Product Groups Founds.");
            return response("No product Groups Founds");
        }
        $saveDatas = $saveBulk;
        // return response()->json($saveBulk);
        // dd($saveBulk);
        $saveBulk = json_encode($saveBulk, true);

        $res = $this->api->sendRequest($saveBulk, $bulkParam, 1);

        if($isDebug == 2){
            dd($res);
        }

        if($res['status']['errorCode'] != 0){
            info("Error while saving product groups.". $res['status']['errorCode']);
            return response("Error while saving product groups.". $res['status']['errorCode']);
        }

        foreach($saveDatas as $key => $data){
            if($res["requests"][$key]["status"]["errorCode"] == 0 && !empty($res["requests"][$key]["records"])){
                LiveProductGroup::where("SchoolID", $data["attributeValue1"])
                ->update(
                    [
                        "erplyGroupID" => $res['requests'][$key]['records'][0]['productGroupID'],
                        "pendingProcess" => 0
                    ]
                );    
                info("Product Group : Created or Updated : ".$res['requests'][$key]['records'][0]['productGroupID']);
            }else{
                info("Error while saving product group ".$res['requests'][$key]["status"]["errorCode"]);
            }
        }

        return response()->json($res);

    }

    protected function makeBundleGroup($g,$sessionKey){

        //now getting parent id
        $warehouse = LiveWarehouseLocation::where("pendingProcess", 0)->where("LocationID", $g->parentSchoolGroup)->first();

        $param = array(
            "requestName" => "saveProductGroup",
            "sessionKey" => $sessionKey,
            "clientCode" => $this->api->client->clientCode,
            // "productGroupID" => $g->erplyGroupID ? $g->erplyGroupID : $g->erplyGID, 
            "name" => $g->SchoolName, 
            // "parentGroupID" => $g->erplyParentGroupID, 
            "attributeName1" => "SchoolID",
            "attributeType1" => "int",
            "attributeValue1" => $g->SchoolID 
         ); 

         if($warehouse){
            $param["parentGroupID"] = $warehouse->parentGroupID;
         }else{
            $param["parentGroupID"] = 1239;
         }
        
        // $gParam = array(
        //     "searchAttributeName" => 'SchoolID', 
        //     "searchAttributeValue" => $g->SchoolID,
        //     "sessionKey" => $sessionKey
        // );
        // $res = $this->api->sendRequest("getProductGroups", $gParam,0,0,0);
        // if($res['status']['errorCode'] == 0 && !empty($res['records'])){
        //     info("Group ID Exist ". $res['records'][0]['productGroupID']); 
        //     $param['productGroupID'] = $res['records'][0]['productGroupID'];
        // }  
        return $param;
    }


    //updating parent group location 
    public function updateParentGroup(){

        return $this->pushParentGroup();
        die;

        // return $this->pushParentGroup(); 
        // die;
        $groups = LiveProductGroup::whereNull("parentSchoolGroup")->limit(200)->get();

        if($groups->isEmpty()){
            return response("All Group Parent Syncced");
        }

        foreach($groups as $g){

            $pg = LiveProductVariation::where("DefaultStore",'<>','')->where("SchoolID", $g->SchoolID)->first();
            if($pg){
                $g->parentSchoolGroup = $pg->DefaultStore;
                $g->save();
            }
        }

        return response("All Group Parent Synccing");
    }

    //push warehouse location as product group 

    public function pushParentGroup(){
        
        // die;
        //now creating new parent group with ascending order

        $pg = LiveWarehouseLocation::whereNull("parentGroupID")->where("ENTITY", $this->api->client->ENTITY)->orderBy("CITY", 'asc')->limit(100)->get();
        
        // dd($pg);
        if($pg->isEmpty()){
            info("All Parent Group Syncced to Erply.");
            return response("All Parent Group Syncced to Erply.");
        }
        // dd($pg);
        $getBulk = $this->getGroupsParent($pg);
        // dd($getBulk);
        if($getBulk["status"] == false){
            info("Error while getting parent group information from erply");
            die;
        }

        $getBulk = $getBulk["data"];
        // dd($getBulk);
        $bulkParam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        $bulkReq = array();

        foreach($pg as $key => $gg){

            if($getBulk["requests"][$key]["status"]["errorCode"] == 0){
                $param = array(
                    "requestName" => "saveProductGroup",
                    "sessionKey" => $this->api->client->sessionKey,
                    "clientCode" => $this->api->client->clientCode,
                    "name" => $gg->CITY, 
                    "attributeName1" => "LocationID",
                    "attributeType1" => "text",
                    "attributeValue1" => $gg->LocationID,// ? $gg->LocationID : "Unknown"
                ); 
                
                if(!empty($getBulk["requests"][$key]["records"])){
                    $param["productGroupID"] = $getBulk["requests"][$key]["records"][0]["productGroupID"];
                }

                $bulkReq[] = $param;
            }
        }
        

        if(count($bulkReq) < 1){
            info("All Parent :  Group Synced");
            return response()->json("All Group Synced.");
        }
        // dd($bulkReq);
        $readyParam = $bulkReq;
        $bulkParams = json_encode($bulkReq, true);

        $bulkRes = $this->api->sendRequest($bulkParams, $bulkParam,1);
        // info($bulkRes);
        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            foreach($readyParam as $key => $g){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){
                    LiveWarehouseLocation::where("LocationID", $g["attributeValue1"])->update(
                        [
                            "parentGroupID" => $bulkRes['requests'][$key]['records'][0]['productGroupID']
                        ]
                    );
                     
                }
            }
            info("Parent Product Group Created or Updated to Erply");
        }

        return response()->json($bulkRes);

    }

    public function getGroupsParent($datas){

        $param = array(
            "sessionKey" => $this->api->client->sessionKey,
            // "typeID" => 1,
            // "ownerID" => $data->customerID,
            // "recordsOnPage" => 100
        );
        
        $getBulk = array();

        foreach($datas as $data){
            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "getProductGroups",
                "searchAttributeName" => "LocationID",
                "searchAttributeValue" => $data->LocationID,

                // "recordsOnPage" => 100
            );

            $getBulk[] = $param;

        }
        // dd($getBulk);
        if(count($getBulk) < 0){

            info("No Parent Product Group Found.");
            return ['status' => false]; 
        }

        $getBulk = json_encode($getBulk, true);

        // $res = $this->api->sendRequest("getAddresses", $param);
        $res = $this->api->sendRequest($getBulk, $param, 1);
        // dd($res);

        if($res["status"]["errorCode"] != 0){
            info("Error while getting addresses ". $res["status"]["errorCode"]);
            return ['status' => false]; 
        }

        return ['status' => true, "data" => $res];
    }


    public function deleteProductGroup(){
        echo "cron distabled";
        die;
        $isParent = 0;

        $warehouse = LiveWarehouseLocation::where('ENTITY',$this->api->client->ENTITY)->pluck("LocationID")->toArray();
        // dd($warehouse);
        if($isParent == 1){
            $groups = LiveWarehouseLocation::where('ENTITY','<>', $this->api->client->ENTITY)->where("parentGroupID", '>', 0)->get();
        }else{
            $groups = LiveProductGroup::where("erplyDeleted", 0)->where("erplyGroupID",'>', 0)->whereNotIn("parentSchoolGroup", $warehouse)->limit(100)->get();
        } 
        // return response()->json($groups);
        dd($groups);
        
        if($groups->isEmpty()){
            info("All product groups have been deleted.");
            return response("All products have been deleted");
        }
        
        $bulkParam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        //first get bulk request

        $getBulk = array();

        foreach($groups as $gg){
            $param = array(
                "requestName" => "getProductGroups",
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "productGroupID" => $isParent == 1 ? $gg->parentGroupID : $gg->erplyGroupID,  
             );  

            $getBulk[] = $param;
        }
        // dd($getBulk);
        $getBulk = json_encode($getBulk, true);

        $getRes = $this->api->sendRequest($getBulk, $bulkParam, 1);

        if($getRes["status"]["errorCode"] != 0){
            info("Error while getting product groups from erply...");
            return response("Error while getting product groups from erply");
        }
        // dd($getRes);
        $bulkReq = array();
        $bulkReqCopy = array();
        foreach($groups as $key => $gg){

            if($getRes["requests"][$key]["status"]["errorCode"] == 0){
                if(!empty($getRes["requests"][$key]["records"])){
                    $param = array(
                        "requestName" => "deleteProductGroup",
                        "sessionKey" => $this->api->client->sessionKey,
                        "clientCode" => $this->api->client->clientCode,
                        "productGroupID" => $isParent == 1 ? $gg->parentGroupID : $gg->erplyGroupID,  
                    );  

                    $bulkReq[] = $param;
                    $param["id"] = $gg->id;
                    $bulkReqCopy[] = $param;
                }else{
                    if($isParent == 0){
                        $gg->erplyDeleted = 1;
                        $gg->save();
                    }
                }
            }

            
        }
        
        // dd($bulkReq);

        if(count($bulkReq) < 1){
            info("All Product Group Deleted...");
            return response()->json("All Product Group Deleted.");
        }
        // dd($bulkReq);
       
        $bulkParams = json_encode($bulkReq, true);

        $bulkRes = $this->api->sendRequest($bulkParams, $bulkParam,1);
        // dd($bulkRes);
        if($bulkRes["status"]["errorCode"] != 0){
            info("Error while calling delete product group api");
            return response()->json($bulkRes);
        }
        foreach($bulkReqCopy as $key => $g){
            if($bulkRes["requests"][$key]["status"]["errorCode"] == 0){

                if($isParent == 0){
                    LiveProductGroup::where("id", $g["id"])->update(["erplyDeleted" => 1]);
                }
                
            }
        }
        info("Erply Product Group deleted successfully.");
        return response()->json($bulkRes);
    }

    public function checkSecondarySchool($req){

        $datas = LiveProductGroup::where("checkShared", 1)->limit(100)->get();

        if($datas->isEmpty()){
            info("All School Secondary Location Checked Successfully.");
            return response("All School Secondary Location Checked Successfully.");
        }

        foreach($datas as $data){
            $chk = LiveProductVariation::where("SchoolID", $data->SchoolID)->where("DefaultStore", $data->parentSchoolGroup)
                    ->whereNotIn("SecondaryStore", [$data->parentSchoolGroup, ''])->first();
            if($chk){
                // dd($chk);
                $data->SecondaryParentSchoolGroup = $chk->SecondaryStore;
                $data->checkShared = 0;
                $data->save();
            }else{
                $data->checkShared = 2;
                $data->save();
            }
        }

        return response("School Secondary Location Checking...");
    }
 
}
