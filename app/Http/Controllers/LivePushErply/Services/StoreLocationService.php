<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;

class StoreLocationService{
    //
    protected $api;
    protected $store;

    public function __construct(EAPIService $api, LiveWarehouseLocation $store){
        $this->api = $api;
        $this->store = $store;
    }

    public function syncStoreLocation($req){
        $isDebug = '';
        if($req->debug){
            $isDebug = 1;
        }

        $warehouses = $this->store->where('pendingProcess', 1)->where('ENTITY', $this->api->client->ENTITY)->limit(99)->get();
        // dd($warehouses);
        if(count($warehouses) < 1){
            info("Synccare to Erply ". $this->api->client->ENTITY ." : All Store Location Synced.");
            return response("All Store Locations Synced ".$this->api->client->ENTITY);
        }
        // dd($warehouses);
        //first save Assortment First
        $assortment = $this->syncAssortment($warehouses);
        // dd($assortment);


         //$this->api->verifySessionByKey($this->api->client->sessionKey);
        $bulkWarehouse = $this->makeBundle($warehouses, $assortment,$isDebug);
        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey, 
        );

        info("save warehouse bulk api callling...");
        // info($bulkWarehouse);
        
        $bulkRes = $this->api->sendRequest($bulkWarehouse, $bulkparam, 1, 0 , 0);
        // info($bulkRes);
        // print
        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            info("warehouse api response received");
            foreach($warehouses as $key => $c){
                $c->erplyID = $bulkRes['requests'][$key]['records'][0]['warehouseID'];
                $c->pendingProcess = 0;
                $c->save();
            }
            // info("warehouse ");
            
            // Log::info($bulkRes);
        }
        info("warehouse save update success");
        
        
        return response()->json(['status' => 200, 'message' => "Warehouse Location Created or Updated Successfully."]);

    }

    protected function makeBundle($data, $assortment, $isDebug){
       
        $BundleArray = array();
        foreach($data as $key  => $w){
            $param = array(
                "requestName" => "saveWarehouse",
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "name" => $w->LocationName,
                "code" => $w->LocationID,
                "email" => @$w->EMAIL,
                "phone" => @$w->PHONE,
                
                "assortmentID" => $assortment['requests'][$key]['records'][0]['assortmentID'],
                 
            );

            $index = 1;
            foreach($w->toArray() as $key => $val){
                if($key == 'LocationName' || $key == 'LocationID' || $key == 'EMAIL' || $key == 'PHONE'){

                    $param["attributeName".$index] = $key;
                    $param["attributeType".$index] = 'text';
                    $param["attributeValue".$index] = $val;
                    $index++;

                }
            }
            // dd($param);
            //now checking warehouse exist

            $warehouseID = $this->checkWarehouse($w->LocationID);
            if($warehouseID != ''){
                $param['warehouseID'] = $warehouseID;
            } 
            array_push($BundleArray,$param );
             
        }
        if($isDebug == 1){
            dd($BundleArray);
        }
        $BundleArray = json_encode($BundleArray, true);
         
        return $BundleArray; 
    }

    public function syncAssortment($warehouse){

        $bulkParam = array();

        foreach($warehouse as $w){
            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "saveAssortment",
                // "assortmentID" => $w->erplyAssortmentID,
                "name" => $w->LocationID,
                "code" => $w->LocationID,
                "attributeName1" => "name",
                "attributeType1" => "text",
                "attributeValue1" => $w->LocationID 
            );
            // echo $w->LocationID."<br>";
            // dd($param);
            $AID = $this->getAssortmentByNameValue($w->LocationID);
            // echo $AID;
            // die;
            if($AID != ''){
                $param['assortmentID'] = $AID;
            }
            array_push($bulkParam, $param);
        }
        // dd($bulkParam);

        $bulkParam = json_encode($bulkParam, true);

        $bulkP = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey
        );
        $bulkRes = $this->api->sendRequest($bulkParam, $bulkP, 1,0,0);
        // dd($bulkRes);
        // info($bulkRes);
        info("Response received from save bulk assortment");
        if($bulkRes['status']['errorCode'] == 0 && !empty($bulkRes['requests'])){
            foreach($warehouse as $key => $val){
                $val->erplyAssortmentID = $bulkRes['requests'][$key]['records'][0]['assortmentID'];
                $val->save();
            }
            info("Assortment Save/Updated Successfully.");
            return $bulkRes;
        }


    }

    public function checkWarehouse($locationid){
        info("checking warehouse location to erply db ");
        $param = array(
            "code" => $locationid,
            "sessionKey" => $this->api->client->sessionKey
        );

        $res = $this->api->sendRequest("getWarehouses", $param,0,0,0);
        // dd($res);
        // info($res);
        if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            info("Warehouse Location ID exist ".$res['records'][0]['warehouseID']);
            return $res['records'][0]['warehouseID'];
        }else{
            info("check warehouse location not exist to  erply db ". $locationid);
            return '';
        }
        

        return '';

    }

    public function getAssortmentByNameValue($val){

        $param = array(
            "searchAttributeName" => "name",
            "searchAttributeValue" => $val,
            "sessionKey" => $this->api->client->sessionKey,
            // "pageNo" => 2
        );
        // dd($param);
        $res = $this->api->sendRequest("getAssortments", $param,0,0,0);
        // dd($res);
        if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            info('assortment exist return assortment id');
            //updating local db
            // Warehouse::findOrfail($wid)->update(['erplyAssortmentID'=>$res['records'][0]['assortmentID']]);
            return $res['records'][0]['assortmentID'];
        }
        return '';
    }
}
