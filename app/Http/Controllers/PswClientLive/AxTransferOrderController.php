<?php


namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\AxCashInOutService;
use App\Http\Controllers\PswClientLive\Services\AxCustomerService;
use App\Http\Controllers\PswClientLive\Services\AxTransferOrderService;
use Illuminate\Http\Request;

class AxTransferOrderController extends Controller
{
    //

    protected $service;

    public function __construct(AxTransferOrderService $service){
        $this->service = $service;
    }

    public function syncTransferOrder(){
        
        return $this->service->syncTransferOrder();
    }

    public function syncTOInventTransID(){
        return $this->service->syncTOInventTransID();
    }

     
}
