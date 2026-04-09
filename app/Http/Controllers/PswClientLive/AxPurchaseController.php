<?php


namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\AxCustomerService;
use App\Http\Controllers\PswClientLive\Services\AxPurchaseOrderService;
use Illuminate\Http\Request;

class AxPurchaseController extends Controller
{
    //

    protected $service;

    public function __construct(AxPurchaseOrderService $service){
        $this->service = $service;
    }

    public function syncPurchaseOrder(){
        
        return $this->service->syncPurchaseOrder();
    }
}
