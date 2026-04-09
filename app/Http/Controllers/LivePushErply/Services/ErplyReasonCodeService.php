<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\InventoryRegistration;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveExpensesAccount;
use App\Models\PswClientLive\Local\LiveExpensesAccountList;
use App\Models\PswClientLive\Local\LiveOnHandInventory;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;

class ErplyReasonCodeService{
    //
    protected $api;
    // protected $customer;

    public function __construct(EAPIService $api){
        $this->api = $api;
       
    }

    public function syncReasonCode(){

        $datas = LiveExpensesAccountList::whereNull("erplyID")->where("erplyPending", 1)->limit(1)->first();

        if(!$datas){
            info("Synccare to ERPLY : All Reason Code Syncced");
            return response("All Reason Codes Syncced");
        }
        // dd($datas);
        $bulk = array();
        // foreach($datas as $data){

            $param = array(
                "code" => "$datas->ledgerAccount",
                "isDefaultTypeForStockTakings" => false,
                "name" => array(
                    "en" => $datas->name
                ),
                "type" => $datas->accountType == 1 ? "CASH_OUT" : "CASH_IN",
            );

            if($datas->erplyID > 0){
                $param["id"] = $datas->erplyID;
            }

        //     $bulk = $param;
        // }

        // dd($bulk);
        // $finalBulk = $bulk;

        // $bulk = array(
        //     "requests" => $bulk
        // );
        
        $bulk = json_encode($param, true);
        // return $bulk;
        $res = $this->api->sendRequestByCDNApiPostWithClientCode("https://api-am-au.erply.com/v1/reason-code", $bulk);
        
        if($res["status"]["errorCode"] == 0){
            foreach($res["data"] as $d){
                $datas->erplyPending = 0;
                $datas->erplyID = $d;
                $datas->save();
            }

            return response()->json($res);
        }
        return response()->json($res);
       

        
         
    } 

    public function getReasonCode($val){

        $param = array(
            "purpose" => $val,
            "sessionKey" => $this->api->client->sessionKey
        );

        $res = $this->api->sendRequest("getReasonCodes", $param);

        if($res["status"]["errorCode"] == 0 && !empty($res["records"])){
            return $res["records"][0]["reasonID"];
        }

        return '';
    }
 
 
}
