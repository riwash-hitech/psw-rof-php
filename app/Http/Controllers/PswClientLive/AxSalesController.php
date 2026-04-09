<?php


namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\AxCustomerService;
use App\Http\Controllers\PswClientLive\Services\AxPurchaseOrderService;
use App\Http\Controllers\PswClientLive\Services\AxSalesOrderService;
use Illuminate\Http\Request;

class AxSalesController extends Controller
{
    //

    protected $service;

    public function __construct(AxSalesOrderService $service){
        $this->service = $service;
    }

    public function syncMiddlewareToAx(Request $req){
        if(env("isLive") == true){
            $dateNow = date('Y-m-d');
            if($dateNow >= '2023-08-14'){
                // info("Date Validation Failed");
            }else{
                
                info("Synccare to AX : Sales Document cron dismissed...Date");
                return response("Synccare to AX : cron dismissed...Date");
                die;
            }
        }

        return $this->service->syncMiddlewareToAx($req);
    }

    public function checkPaymentFlag(){
        return $this->service->checkPaymentFlag();
    }

    public function handleNoLineFlagDocuments(){
        return $this->service->handleNoLineFlagDocuments();
    }
}
