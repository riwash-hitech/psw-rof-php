<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\LivePushErply\Services\ErplySupplierService; 
use Illuminate\Http\Request;

class ErplySupplierController extends Controller
{
    //
    protected $service;

    public function __construct(ErplySupplierService $service){
        $this->service = $service;
    }

    public function syncSupplier(){
        return $this->service->syncSupplier();
    }
 


}
