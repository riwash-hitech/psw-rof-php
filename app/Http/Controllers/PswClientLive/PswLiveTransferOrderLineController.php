<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\PswLiveProductService;
use App\Http\Controllers\PswClientLive\Services\PswLivePurchaseOrderService;
use App\Http\Controllers\PswClientLive\Services\PswLiveTransferOrderLineService;
use App\Models\PswClientLive\Product;
use Illuminate\Support\Facades\File;

class PswLiveTransferOrderLineController extends Controller
{
    //
    protected $service;

    public function __construct(PswLiveTransferOrderLineService $ps){
      $this->service = $ps;
    }



    //Generating Product File
    public function makeTransferOrderFile(){ 
        return $this->service->makeTransferOrderFile(); 
    }

    public function readTransferOrderFile(){ 
        return $this->service->readTransferOrderFile(); 
    }

    public function syncTransferOrderToNewsystem(){ 
        return $this->service->syncTransferOrderToNewsystem(); 
    }

    public function syncTransferOrderByLastModified(){
        return $this->service->syncTransferOrderByLastModified();
    }
 
    

     
}
 