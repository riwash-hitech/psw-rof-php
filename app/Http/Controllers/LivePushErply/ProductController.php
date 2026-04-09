<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LivePushErply\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //
    protected $service;

    public function __construct(ProductService $service){
        $this->service = $service;
    }

    public function syncMatrixProduct(Request $req){
        return $this->service->syncMatrixProduct($req);
    }

    public function archiveMatrixProduct(Request $req){
        return $this->service->syncVariationProduct($req);
    }

    public function syncVariationProduct(Request $req){
        return $this->service->syncVariationProductV2($req);
    }

    public function updateErplySkuIcsc(){
        return $this->service->updateErplySkuIcsc();
    }

    public function checkProductExistInErply(){
        return $this->service->checkProductExistInErply();
    }
}
