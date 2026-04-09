<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Paei\Services\GetProductStockService;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\ProductStock;
use App\Models\PAEI\Warehouse;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;

class GetProductStockController extends Controller
{
    //
    protected $service;
    protected $api;

    public function __construct(GetProductStockService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function getStock(){
        
        $warehouses = LiveWarehouseLocation::where("ENTITY", $this->api->client->ENTITY)->get();
        

        // dd($warehouses);
         
        foreach($warehouses as $w){
            $stockInHand = ProductStock::where('warehouseID', $w->erplyID)->where('clientCode', $this->api->client->clientCode)->orderBy('lastSoldDate', 'desc')->first();
            if ($stockInHand) {
                $lastModifiedDateTime = strtotime($stockInHand->lastModifiedDateTime);
            } else {
                $lastModifiedDateTime = 0;
            }
            $param = array(
                "orderBy" => "changed",
                "orderByDir" => "asc",
                // "recordsOnPage" => "5",
                "warehouseID" => $w->erplyID, 
                "status" => "ACTIVE_AND_NOT_FOR_SALE",
                "getAmountReserved" => 1,
                "getSuggestedPurchasePrice" => 1,
                "getFirstPurchaseDate" => 1,
                "getLastSoldDate" => 1,
                // "" => ,
                "changedSince" => $lastModifiedDateTime,//$this->service->getLastUpdateDate(), 
            );
            $res = $this->api->sendRequest("getProductStock", $param);
            // dd($res);
            if($res['status']['errorCode'] == 0 && !empty($res['records'])){
                 $this->service->saveUpdate($res['records'], $w->erplyID);
            }
        }

        return response()->json(['status'=>200, 'message'=>"Product Stock fetched Successfully."]);



    }
}
