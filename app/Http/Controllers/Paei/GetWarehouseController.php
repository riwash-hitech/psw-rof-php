<?php

namespace App\Http\Controllers\Paei;

use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetWarehouseService;
use App\Http\Controllers\Services\EAPIService;
use Illuminate\Http\Request;
use App\Traits\UserOperationTrait;

class GetWarehouseController extends Controller
{
    //
    protected $service;
    protected $api;
    protected $userOperationInterface;
    use UserOperationTrait;

    public function __construct(GetWarehouseService $service, EAPIService $api, UserOperationInterface $userOperationInterface){
        $this->service = $service;
        $this->api = $api;
        $this->userOperationInterface = $userOperationInterface;

    }

    public function getWarehouse(){
        $param = array(
            "take" => "100",
            "sort" => json_encode([
                "selector" => "changed",
                "desc" => false
            ]),
            "match" => ">=",
            "changed" => $this->service->getLastUpdateDate(),
            "orderBy" => 'changed',
            "orderByDirection" => 'ASC'
         );

         $res = $this->api->sendRequestBySwagger("https://api-am-au.erply.com/v1/warehouse", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0){
           return $this->service->saveUpdate($res['data']['warehouses']);
         }

        // $param = array(
        //     "take" => 200,
        // );
        
        // $res = $this->api->sendRequest("getWarehouses", $param);
        // // dd($res);
        
        // if($res['status']['errorCode'] == 0){
        // return $this->service->saveUpdate($res['records']);
        // }
    }

    public function getDefaultTimeZone(){
        return $this->service->getDefaultTimeZone();
    }

    public function getOperationLog(){
         
        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "tableName" => "warehouses", 
            "addedFrom" => $this->getLastUpdateDateDelete("warehouses"), 
        );
        // dd($this->api->client);
         $res = $this->api->sendRequest("getUserOperationsLog", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0){

            if(empty($res['records'])){
                info("All warehouses Operation Log Up-to-date");
                return response()->json(["status" => 200, "message" => "All warehouses Operation Log Up-to-date"]);
            }

            $this->userOperationInterface->deleteRecords($res['records'], $this->api->client->clientCode);
           
         }
         info("warehouses Operation Log Fetched Successfully.");
         return response()->json(["status" => 200, "message" => "warehouses Operation Log Fetched Successfully."]);

    }
}
