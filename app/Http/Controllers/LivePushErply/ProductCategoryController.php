<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LivePushErply\Services\ProductCategoryService;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    //

    protected $service;

    public function __construct(ProductCategoryService $service){
        $this->service = $service;
    }

    public function syncProductCategory(){
        return $this->service->syncProductCategory();
    }
}
