<?php

namespace App\Http\Controllers\Paei;

use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetProductCategoryService;
use App\Http\Controllers\Services\EAPIService;
use Illuminate\Http\Request;
use App\Traits\UserOperationTrait;

class GetProductCategoryController extends Controller
{
    //
    use UserOperationTrait;
    protected $service;
    protected $api;
    protected $userOperationInterface;

    public function __construct(GetProductCategoryService $service, EAPIService $api, UserOperationInterface $userOperationInterface){
        $this->service = $service;
        $this->api = $api;
        $this->userOperationInterface = $userOperationInterface;
    }

    public function getProductCategory(){
        // $param = array(
        //     "take" => "30",
        //     "sort" => json_encode([
        //         "selector" => "changed",
        //         "desc" => false
        //     ]),
        //     "match" => ">=",
        //     "changed" => $this->service->getLastUpdateDate(),
        //     "orderBy" => 'changed',
        //     "orderByDirection" => 'ASC'
        //  ); 
        //  $res = $this->api->sendRequestBySwagger("https://api-pim-au.erply.com/v1/product/category", $param);
        //  dd($res);
        //  if(count($res) > 0){
        //     return $this->service->saveUpdate($res);
        //  }

        $param = array(
            "orderBy" => "changed",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "active" => 1,
            // "pageNo" => $this->page,
            "changedSince" => $this->service->getLastUpdateDate(), 
         );
 
         $res = $this->api->sendRequest("getProductCategories", $param);
        // dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->saveUpdateOldAPI($res['records']);
         }
    }

    public function getOperationLog(){
         
        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "tableName" => "productCategory", 
            "addedFrom" => $this->getLastUpdateDateDelete("productCategory"), 
        );
        // dd($this->api->client);
         $res = $this->api->sendRequest("getUserOperationsLog", $param);
         dd($res);
         if($res['status']['errorCode'] == 0){

            if(empty($res['records'])){
                info("All productGroups Operation Log Up-to-date");
                return response()->json(["status" => 200, "message" => "All productGroups Operation Log Up-to-date"]);
            }

            $this->userOperationInterface->deleteRecords($res['records'], $this->api->client->clientCode);
           
         }
         info("productGroups Operation Log Fetched Successfully.");
         return response()->json(["status" => 200, "message" => "productGroups Operation Log Fetched Successfully."]);

    }
}
