<?php

namespace App\Http\Controllers\Paei\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\API\APIServices\MagicApiService;
use App\Http\Controllers\Paei\API\APIServices\SchoolApiService;
use App\Models\PswClientLive\Local\LiveProductVariation;
use Illuminate\Http\Request;

class MagicApiController extends Controller
{
    //
    protected $service; 

    public function __construct(MagicApiService $service ){
        $this->service = $service;
       
    }

    //for generic product
    public function genericProduct(Request $req){

        return $this->service->genericProduct($req);
    }

    public function nonGenericProduct(Request $req){

        return $this->service->nonGenericProduct($req);
    }

    public function getProduct(Request $req){

        $skus = explode(",",$req->sku);

        $datas = LiveProductVariation::whereIn("ERPLYSKU", $skus)->where("erplyID",'>',0)->select("ERPLYSKU,erplyID")->get();

        return response()->json($datas);
    }

    public function getWarehouseList(Request $req){
        return $this->service->getWarehouseList($req);
    }
 



}
