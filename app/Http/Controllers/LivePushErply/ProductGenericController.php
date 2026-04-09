<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LivePushErply\Services\ProductGenericService; 
use Illuminate\Http\Request;

class ProductGenericController extends Controller
{
    //
    protected $service;

    public function __construct(ProductGenericService $service){
        $this->service = $service;
    }

    public function syncMatrixProduct(Request $req){
        return $this->service->syncMatrixProduct($req);
    }

    public function syncVariationProduct(Request $req){
        return $this->service->syncVariationProduct($req);
    }

    // public function updateErplySkuIcsc(){
    //     return $this->service->updateErplySkuIcsc();
    // }
}
