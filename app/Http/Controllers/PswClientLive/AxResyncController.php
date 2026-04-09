<?php


namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\AxCustomerService;
use App\Http\Controllers\PswClientLive\Services\AxPurchaseOrderService;
use App\Http\Controllers\PswClientLive\Services\AxResyncService;
use Illuminate\Http\Request;

class AxResyncController extends Controller
{
    //

    protected $service;

    public function __construct(AxResyncService $service){
        $this->service = $service;
    }

    public function resyncByWebSkuProduct(Request $req){
        // dd($req);
        return $this->service->resyncByWebSkuProduct($req);
    }

    public function resyncBySchool(Request $req){
        
        return $this->service->resyncBySchool($req);
    }

    public function resyncByWebSkuGenericProduct(Request $req){
        return $this->service->resyncByWebSkuGenericProduct($req);
    }

    public function getNotSynccedGenericProduct(){
        return $this->service->getNotSynccedGenericProduct();
    }

    public function detectDeletedProductAX(){
        return $this->service->detectDeletedProductAX();
    }

    public function detectGenericDeletedProductAX(){
        return $this->service->detectGenericDeletedProductAX();
    }

    //special cron
    public function resyncFromAx(){
        return $this->service->resyncFromAx();
    }
}
