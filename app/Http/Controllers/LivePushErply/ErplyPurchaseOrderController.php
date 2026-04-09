<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\LivePushErply\Services\ErplyBinBayService;
use App\Http\Controllers\LivePushErply\Services\ErplyPurchaseOrderService;
use Illuminate\Http\Request;

class ErplyPurchaseOrderController extends Controller
{
    //
    protected $service;

    public function __construct(ErplyPurchaseOrderService $service){
        $this->service = $service;
    }

    public function syncPurchaseOrder(Request $req){
        
        
        return $this->service->syncPurchaseOrder($req);
    }

    
 


}
