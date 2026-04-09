<?php

namespace App\Traits;

use App\Models\PAEI\Warehouse;
use App\Traits\ResponseTrait;

trait WmsValidationTrait{

    use ResponseTrait;
    public function __construct(){
        
    }
    
    public function checkWarehouseID($req){

        if(isset($req->warehouseID) == 0 && $req->warehouseID == ''){
            return $this->failWithMessage("Invalid Warehouse ID!");
        }
        
    }

    public function getCurrentWarehouse($locationID){
        $warehouseInfo = Warehouse::where("code", $locationID)->first();
        if($warehouseInfo){
            return ["status" => 1, "clientCode" => $warehouseInfo->clientCode, "warehouseID" => $warehouseInfo->warehouseID, "warehouseCode" => $locationID];
        }
        return ["status" => 0];
    }

    public function getCurrentUserEmail(){
        $details = auth('sanctum')->user()->email;
        return substr($details,0,16);
    }

    

}