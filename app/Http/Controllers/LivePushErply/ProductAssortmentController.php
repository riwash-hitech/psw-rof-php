<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LivePushErply\Services\ProductAssortmentService; 
use Illuminate\Http\Request;

class ProductAssortmentController extends Controller
{
    //
    protected $service;

    public function __construct(ProductAssortmentService $service){
        $this->service = $service;
    }

    public function syncProductAssortment(Request $req){
        // if($req->env == "TEST"){
        //     die;
        // }
        return $this->service->productAssortmentV2($req);
    }

    public function removeProductAssortment(Request $req){
        return $this->service->removeProductAssortment($req);
    }

    public function genericAssortment(Request $req){
         
        return $this->service->genericAssortment($req);
    }
 
}
