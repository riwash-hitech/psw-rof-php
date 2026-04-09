<?php


namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\AxCashInOutService;
use App\Http\Controllers\PswClientLive\Services\AxCustomerService;
use Illuminate\Http\Request;

class AxCashInOutController extends Controller
{
    //

    protected $service;

    public function __construct(AxCashInOutService $service){
        $this->service = $service;
    }

    public function syncCashInOut(){
        
        return $this->service->syncCashInOut();
    }

     
}
