<?php

namespace App\Http\Controllers\Paei\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\API\APIServices\WarehouseApiService;
use App\Models\PAEI\Warehouse;
use Illuminate\Http\Request;
use App\Traits\WmsValidationTrait;

class WarehouseApiController extends Controller
{
    //
    protected $service;
    use WmsValidationTrait;


    public function __construct(WarehouseApiService $service){
        $this->service = $service;
        // $this->variation = $vp;
    }

    public function getWarehouses(Request $req){

        return $this->service->getWarehouses($req);

    }

    public function getWarehouseByID(Request $req){
        if($req->id){
            return $this->service->getByWarehouseID($req->id);
        }
        return response()->json(["status" => 400, "message" => "ID Field is Required!"]);

    }


    //for pos warehouse 

    public function getWarehouseList(Request $req){
        return $this->service->getWarehouseList($req);
    }

    public function warehouseWiseOrders(Request $req){
        return $this->service->warehouseWiseOrders($req);
    }

    public function orderLineItemOnly(Request $req){
        return $this->service->orderLineItemOnly($req);
    }

    public function readyToFulfill(Request $req){
        return $this->service->readyToFulfill($req);
    }

    public function fulfilledOrders(Request $req){
        return $this->service->fulfilledOrders($req);
    }

    public function readyToBePicked(Request $req){
        return $this->service->readyToBePicked($req);
    }

    public function updateToPickedOrder(Request $req){
        return $this->service->updateToPickedOrder($req);
    }

    public function expressOrder(Request $req){
        return $this->service->expressOrder($req);
    }

    public function orderCount(Request $req){
        return $this->service->orderCount($req);
    }

    public function getTransferOrderFrom(Request $req){
        return $this->service->getTransferOrderFrom($req);
    }

    public function getTransferOrderTo(Request $req){
        return $this->service->getTransferOrderTo($req);
    }

    public function filterOrder(Request $req){
        
        // $this->checkWarehouseID($req);
        if(isset($req->warehouseID) == 0 && $req->warehouseID == ''){
            return $this->failWithMessage("Invalid Warehouse ID!");
        }

        return $this->service->filterOrder($req);
    }

}
