<?php

namespace App\Http\Controllers\Paei;

use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetProductGroupService;
use App\Http\Controllers\Services\EAPIService;
use Illuminate\Http\Request;
use App\Traits\UserOperationTrait;

class GetProductGroupController extends Controller
{
    //
    protected $service;
    protected $api;
    use UserOperationTrait;
    protected $userOperationInterface;

    public function __construct(GetProductGroupService $service, EAPIService $api, UserOperationInterface $userOperationInterface){
        $this->service = $service;
        $this->api = $api;
        // $this->api->verifySession();
        $this->userOperationInterface = $userOperationInterface;
    }

    public function getProductGroup(){
        // echo "hello sir";
        // die;
        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "1000",
            "active" => 1,
            // "pageNo" => $this->page,
            "changedSince" => $this->service->getLastUpdateDate(),
         );
        //  print_r($param);
        //  die;
         $res = $this->api->sendRequest("getProductGroups", $param);
        // dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->saveUpdateOldAPI($res['records']);
         }
        //  $param = [
        //     "take" => "3000",
        //     "sort" => json_encode([
        //         "selector" => "added",
        //         "desc" => false
        //     ]),
        //     "match" => ">=",
        //     "added" => $this->service->getLastUpdateDate(),
        //     "orderBy" => 'added',
        //     "orderByDirection" => 'ASC'
        //  ];

        //  $p2 =[
        //        'take' => '100000',
        //        'sort' => json_encode([
        //           "selector" => "added",
        //           "desc" => false,
        //         ]),
        //        'filter' => '[["added", ">=", ' . $this->service->getLastUpdateDate() . ']]',
        //     ];
        // // echo $this->service->getLastUpdateDate();

        // // $p2 = json_encode($p2, true);
        // //  print_r($p2);
        // //  die;
        //  $res = $this->api->sendRequestBySwagger("https://api-pim-au.erply.com/v1/product/group", $param);
        // //  dd($res);
        //  if(count($res) > 0){
        //     return $this->service->saveUpdate($res);
        //  }

        //  return response()->json(['status'=>200, 'message'=>"Product Group Data Not Found!"]);
    }

    public function getOperationLog(){

        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "tableName" => "productGroups",
            "addedFrom" => $this->getLastUpdateDateDelete("productGroups"),
        );
        // dd($this->api->client);
         $res = $this->api->sendRequest("getUserOperationsLog", $param);
        //  dd($res);
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
