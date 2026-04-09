<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LivePushErply\Services\ProductDimensionService;
use Illuminate\Http\Request;

class ProductDimensionController extends Controller
{
    //
    protected $service;

    public function __construct(ProductDimensionService $service){
        $this->service = $service;
    }

    public function syncDimensionColor(){
        return $this->service->syncDimColor();
    }

    public function syncDimensionSize(){
        return $this->service->syncDimSize();
    }


}
