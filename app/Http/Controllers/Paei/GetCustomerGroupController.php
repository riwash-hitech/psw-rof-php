<?php

namespace App\Http\Controllers\Paei;

use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetCustomerGroupService;
use App\Http\Controllers\Paei\Services\GetCustomerService;
use App\Http\Controllers\Services\EAPIService;
use Illuminate\Http\Request;
use App\Traits\UserOperationTrait;

class GetCustomerGroupController extends Controller
{
    //
    protected $service;
    protected $api;
    use UserOperationTrait;
    protected $userOperationInterface;

    public function __construct(GetCustomerGroupService $service, EAPIService $api, UserOperationInterface $userOperationInterface){
        $this->service = $service;
        $this->api = $api;
        $this->userOperationInterface = $userOperationInterface;
    }

    public function getCustomerGroups(){
        //   [
                //   'take' => '200',
                //   'sort' => json_encode([
                //     "selector" => "changed",
                //     "desc" => false,
                //     ]),
                //   'match' => '>=',
                //     'changed' => $lastModified,
                //     'orderBy' => 'changed',
                //     'orderByDirection' => 'ASC',
                //    ]
        // $param = array(
        //     "take" => "100",
        //     "sort" => json_encode([
        //         "selector" => "changed",
        //         "desc" => false
        //     ]),
        //     "match" => ">=",
        //     "changed" => $this->service->getLastUpdateDate(),
        //     "orderBy" => 'changed',
        //     "orderByDirection" => 'ASC'
        //  );

        //  $res = $this->api->sendRequestBySwagger("https://api-crm-au.erply.com/v1/customers", $param);
        //  if(count($res) > 0){
        //    return $this->service->saveUpdate($res);
        //  }
        info("Cron Customer Group Sync");
         $param = array(
            "orderBy" => "changed",
            "orderByDir" => "asc",
            "recordsOnPage" => "200", 
            "changedSince" => $this->service->getLastUpdateDate(), 
         );
         $res = $this->api->sendRequest("getCustomerGroups", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->saveUpdate($res['records']);
         }
    }

    public function getOperationLogCustomerGroup(){
         
        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "tableName" => "customerGroups", 
            "addedFrom" => $this->getLastUpdateDateDelete("customerGroups"), 
        );
        // dd($this->api->client);
         $res = $this->api->sendRequest("getUserOperationsLog", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0){

            if(empty($res['records'])){
                info("All CustomerGroups Operation Log Up-to-date");
                return response()->json(["status" => 200, "message" => "All CustomerGroups Operation Log Up-to-date"]);
            }

            $this->userOperationInterface->deleteRecords($res['records'], $this->api->client->clientCode);
           
         }
         info("CustomerGroups Operation Log Fetched Successfully.");
         return response()->json(["status" => 200, "message" => "CustomerGroups Operation Log Fetched Successfully."]);

    }

}
