<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\LiveProductCategory;  

class ProductCategoryService{
    //
    protected $api;
    protected $category;

    public function __construct(EAPIService $api, LiveProductCategory $category){
        $this->api = $api;
        $this->category = $category;
       
    }

    public function syncProductCategory(){

        $categories = $this->category->where("pendingProcess", 1)->limit(99)->get();

        $bulkParams = array();
        $bulkParam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        foreach($categories as $c){
            $param = $this->makeBundleCategory($c,$this->api->client->sessionKey);
            array_push($bulkParams, $param); 
        }

        if(count($bulkParams) < 1){
            info("All Categories Synced.");
            return response()->json(["status" => "success", "message" => "All Categories Synced."]);
        }

        $bulkParams = json_encode($bulkParams, true);

        $bulkRes = $this->api->sendRequest($bulkParams, $bulkParam,1,0,0);
        // info($bulkRes);
        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            foreach($categories as $key => $c){
                if($bulkRes['requests'][$key]['status']['errorCode'] == 0){
                    $c->erplyCatID = $bulkRes['requests'][$key]['records'][0]['productCategoryID'];
                    $c->pendingProcess = 0;
                    $c->save();
                }
            }
            info("Product Category Created or Updated to Erply");
        }

        return response()->json(["status" => "success", "response" => $bulkRes]);

    }

    protected function makeBundleCategory($c,$sessionKey){
        $param = array(
            "requestName" => "saveProductCategory",
            "sessionKey" => $sessionKey,
            "clientCode" => $this->api->client->clientCode,
            "name" => $c->name, 
            "attributeName1" => "ProductType",
            "attributeType1" => "text",
            "attributeValue1" => $c->name 
         ); 
        
        $gParam = array(
            "searchAttributeName" => 'ProductType', 
            "searchAttributeValue" => $c->name,
            "sessionKey" => $sessionKey
        );
        $res = $this->api->sendRequest("getProductCategories", $gParam,0,0,0);
        if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            info("Category ID Exist ". $res['records'][0]['productCategoryID']); 
            $param['productCategoryID'] = $res['records'][0]['productCategoryID'];
        }  
        return $param;
    }
 
}
