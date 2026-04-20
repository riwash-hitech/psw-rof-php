<?php

namespace App\Http\Controllers\Paei;

use App\Classes\UserLogger;
use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetProductService;
use App\Http\Controllers\Services\EAPIService;
use App\Models\{StockColorSize, StockDetail};
use App\Traits\UserOperationTrait;
use Illuminate\Http\Request;

class GetMatrixProductController extends Controller
{
    //
    protected $api;
    protected $service;
    protected $client;
    use UserOperationTrait;

    //for updating psw existing products
    protected $matrix;
    protected $variation;
    protected $userOperationService;
    protected $userOperationInterface;

    public function __construct(EAPIService $api, GetProductService $service, StockDetail $sd, StockColorSize $vp, UserOperationInterface $userOperationInterface)
    {
        // info("const from get matrix");
        $this->api = $api;

        $this->service = $service;
        // $this->client =$client;
        // $this->client->sessionKey = $this->api->verifySessionByKey($client->sessionKey);
        $this->matrix = $sd;
        $this->variation = $vp;
        $this->userOperationInterface = $userOperationInterface;
    }

    public function getProduct(Request $request){
        info("Cron Called for Product Sync This is Old Version");

        $syncType = $request->syncType ?? 'changeSince';
        $orderBy = $request->orderBy ?? 'changed';
        $sortBy = $request->sortBy ?? 'asc';
        $limit = $request->limit ?? 100;

        $param = array(
            "orderBy" => $orderBy,
            "orderByDir" => $sortBy,
            $syncType => $this->service->getLastUpdateDate(),
            "recordsOnPage" => $limit,
            "includeMatrixVariations" => 1,
            "getPackagingMaterials" => 1,
            'status' => 'active',
            "getRecipes" => 1,
            "getRelatedFiles" => 1,
            "getRelatedProducts" => 1,
            "getReplacementProducts" => 1,
            // 'type' => 'PRODUCT',
            // "productIDs" => '264088,262102,262086,262105,262089,262119,262121,262123,262124,262115,262093,262109,263157,263488',
            // "searchAttributeName" => 'defaultStore',
            // "searchAttributeValue" => '3R390',
            // "getStockInfo" => 1,
            // "getFIFOCost" => 1,
            // "getFIFOCost" => 1,
            // "getFIFOCost" => 1,
            // "active" => 1,
            "sessionKey" => $this->api->client->sessionKey
         );
// dd($this->service->getLastUpdateDate(), $param);

        //  print_r($param);
        //  die;
        $res = $this->api->sendRequest("getProducts", $param,0,0,0);
        if(isset($request->debug) && $request->debug == 1){
            dd($res,$param);
        }
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){

            return $this->service->saveUpdate($res['records']);
         }

    }

    public function getProductV2(){

        info("Cron Called for Product Sync This is Old Version");

        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "addedSince" => $this->service->getTempUpdateDate(1),
            "recordsOnPage" => "500",
            "includeMatrixVariations" => 1,
            "getPackagingMaterials" => 1,
            "getRecipes" => 1,
            "getRelatedFiles" => 1,
            "getRelatedProducts" => 1,
            "getReplacementProducts" => 1,
            // "getStockInfo" => 1,
            // "getFIFOCost" => 1,
            // "getFIFOCost" => 1,
            // "getFIFOCost" => 1,
            // "active" => 1,
            "sessionKey" => $this->api->client->sessionKey
         );

        //  dd($param);
        //  die;
         $res = $this->api->sendRequest("getProducts", $param,0,0,0);
        // dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->saveUpdateV2($res['records']);
         }
    }

    public function getProductPIM(){
        $param = array(
            "take" => "200",
            "sort" => json_encode([
                "selector" => "changed",
                "desc" => false
            ]),
            "match" => ">=",
            "changed" => $this->service->getLastUpdateDate(),
            "orderBy" => 'changed',
            "active" => 1,
            "orderByDirection" => 'ASC'
         );

         $res = $this->api->sendRequestBySwagger("https://api-pim-au.erply.com/v1/product", $param);
         if(count($res) > 0){
            // dd($res[0]);
           return $this->service->saveUpdatePIM($res);

         }
    }

    public function letsUpdateMatrix(){
        $param = array(
            // "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "2000",
            "includeMatrixVariations" => 0,
            "active" => 0,
            // "pageNo" => $this->page,
            // "addedSince" => $this->getLastUpdatedDate(),
         );

        //  print_r($param);
        //  die;
         $res = $this->api->sendRequest("getProducts", $param);
        // dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
             foreach($res['records'] as $p){
                $this->matrix->where('web_sku', $p['code'])->update(['erplyPending'=> 0, 'erplyProductID'=> $p['productID'], 'erplyAdded'=>date('Y-m-d H:i:s', $p['added'])]);
             }
             return response()->json("Matrix Product Updated");
         }
    }

    protected function getLastUpdatedDate(){
        $latest = $this->matrix->where('erplyPending', 0)->orderBy('erplyAdded', 'desc')->first();
        if($latest){
            return strtotime($latest->erplyAdded);
        }
        return 0;
    }

    // public function deleteProduct(){
    //     $this->userOperationInterface->deleteProduct($this->api->client->clientCode, );
    // }

    public function getOperationLogProduct(){

        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "tableName" => "products",
            "addedFrom" => $this->getLastUpdateDateDelete("products"),
        );
        // dd($this->api->client);
         $res = $this->api->sendRequest("getUserOperationsLog", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0){

            if(empty($res['records'])){
                info("All Product Operation Log Up-to-date");
                return response()->json(["status" => 200, "message" => "All Product Operation Log Up-to-date"]);
            }

            $this->userOperationInterface->deleteRecords($res['records'], $this->api->client->clientCode);

         }
         info("Product Operation Log Fetched Successfully.");
         return response()->json(["status" => 200, "message" => "Product Operation Log Fetched Successfully."]);

    }


}
