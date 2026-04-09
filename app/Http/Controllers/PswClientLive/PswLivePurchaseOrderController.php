<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\PswLiveProductService;
use App\Http\Controllers\PswClientLive\Services\PswLivePurchaseOrderService;
use App\Models\PswClientLive\Product;
use Illuminate\Support\Facades\File;

class PswLivePurchaseOrderController extends Controller
{
    //
    protected $service;

    public function __construct(PswLivePurchaseOrderService $ps){
      $this->service = $ps;
    }



    //Generating Product File
    public function makePurchaseOrderFile(){ 
        return $this->service->makePurchaseOrderFile(); 
    }

    public function readPurchaseOrdersFile(){ 
        return $this->service->readPurchaseOrdersFile(); 
    }

    public function syncPurchaseOrderToNewsystem(){ 
        return $this->service->syncPurchaseOrderToNewsystem(); 
    }

    public function syncPurchaseOrderByLastModified(){ 
        return $this->service->syncPurchaseOrderByLastModified(); 
    }

    
 
    

     
}
 