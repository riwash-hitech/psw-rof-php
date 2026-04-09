<?php

namespace App\Http\Controllers\Paei\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\API\APIServices\ProductAPIService;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;

class ProductAPIController extends Controller
{
    //
    use ResponseTrait;
    protected $service;
    protected $variation;

    public function __construct(ProductAPIService $service){
        $this->service = $service;
        // $this->variation = $vp;
    }

    public function getProduct(Request $req){

        // if($req->productID != ''){
        //     return $this->service->getByProductID($req->productID);
        // }

        if($req->productIDs){
            return $this->service->getByProductIDs($req);
        }
        // if($req->productCode){
        //     return $this->service->getByProductCode($req);
        // }

        return $this->service->getProductShort($req);

    }

    public function getInventory(Request  $req){
        if($req->productID){
            return $this->service->getInventoryRegistration($req);
        }
        return response()->json(['status'=>400, "records"=>"Invalid Product ID!"]);
    }
    
    public function getEntity(Request $req){

        $datas = LiveWarehouseLocation::select("ENTITY")->groupBy("ENTITY")->get();


        return $this->successWithData($datas);


    }

    public function getGroups(Request $req){
        return $this->service->getGroups($req);
    }
}
