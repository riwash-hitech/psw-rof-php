<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Paei\Services\GetPaymentService;
use App\Http\Controllers\Services\EAPIService; 

class GetPaymentController extends Controller
{
    //
    protected $service;
    protected $api;

    public function __construct(GetPaymentService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function getPayments(){
        
        if(env("isLive") == true){
            $dateNow = date('Y-m-d');
            if($dateNow >= '2023-08-14'){
                info("Date Validation Failed");
            }else{
                
                info("Get Payment Cron Dismissed...Date");
                return response("Get Sales Documents Cron Date");
            }
        }
        info(" Get Payment Cron Called");
        //  $param = array(
        //     "take" => "200",
        //     "sort" => json_encode([
        //         "selector" => "added",
        //         "desc" => false
        //     ]),
        //     "match" => ">=",
        //     "added" => $this->service->getLastUpdateDate(),
        //     "orderBy" => 'added',
        //     "orderByDirection" => 'ASC'
        //  );
         $param = array(
            "orderBy" => "changed",
            "orderByDir" => "asc",
            "recordsOnPage" => "100",
            // "getRowsForAllInvoices" => 1,
            // "deliveryTypeID" => 1,
            // "active" => 1,
            // "pageNo" => $this->page,
            "changedSince" => $this->service->getLastUpdateDate(), 
        );
         $res = $this->api->sendRequest("getPayments", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->saveUpdate($res['records']);
         }
    }
}
