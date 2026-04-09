<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveSupplier;
use Illuminate\Http\Request;

class ErplySupplierService{
    //
    protected $api;
    protected $customer;

    public function __construct(EAPIService $api, LiveCustomer $customer){
        $this->api = $api;
        $this->customer = $customer;
       
    }

    public function syncSupplier(){

        $suppliers = LiveSupplier::where("pendingProcess", 1)->where("Name",'<>','')->limit(100)->get();

        $BundleArray = array();
        foreach($suppliers as $supplier){
            $reqArray = array(
                "requestName" => "saveSupplier",
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "companyName" => $supplier->Name,  
                "attributeName1" => "RECID",
                "attributeType1" => 'text',
                "attributeValue1" => $supplier->RECID,
                "attributeName2" => "ACCOUNTNUM",
                "attributeType2" => 'text',
                "attributeValue2" => $supplier->ACCOUNTNUM,
            ); 

            $supplierID = $this->getSupplier($supplier->ACCOUNTNUM, $supplier->RECID);
            if($supplierID != ''){
                $reqArray["supplierID"] = $supplierID;
            }
            // die();
            array_push($BundleArray,$reqArray );
                
        }
        // dd($BundleArray);
        if(count($BundleArray) < 1){
            info("All Supplier Synced.");
            return response("All Supplier Synced.");
        }

        $BundleArray = json_encode($BundleArray, true);
        $param = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );
        $res = $this->api->sendRequest($BundleArray, $param, 1);

        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($suppliers as $key => $c){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    $c->supplierID = $res['requests'][$key]['records'][0]['supplierID'];
                    $c->pendingProcess = 0;
                    $c->save();
                }
            }
            info("Supplier Created or Updated to Erply");
        }

        return response()->json(["status" => "success", "response" => $res]);
        

    }


    protected function getSupplier($an, $recid){
        $param = array(
            "searchAttributeName" => "RECID",
            "searchAttributeValue" => $recid,
            "sessionKey" => $this->api->client->sessionKey
        );

        $res = $this->api->sendRequest("getSuppliers", $param,0,0,0);
        // dd($res);
        if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            info("supplier exist ".$res['records'][0]['supplierID']);
            return $res['records'][0]['supplierID'];
        }

        return '';

    }
 
 
}
