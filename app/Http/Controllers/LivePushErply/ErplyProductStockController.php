<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LivePushErply\Services\CustomerService;
use App\Http\Controllers\LivePushErply\Services\ErplyProductStockService;
use App\Http\Controllers\LivePushErply\Services\ProductDimensionService;
use Illuminate\Http\Request;

class ErplyProductStockController extends Controller
{
    //
    protected $service;

    public function __construct(ErplyProductStockService $service){
        $this->service = $service;
    }

    public function syncStock(Request $req){
        
        return $this->service->syncStock($req);
    }

    public function updateSOH(Request $req){
        return $this->service->updateSOH($req);
    }

    public function syncTransferOrder(Request $req){
        $this->service->syncTransferOrder($req);
    }
 


}
