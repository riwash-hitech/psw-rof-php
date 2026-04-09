<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetSalesDocumentService;
use App\Http\Controllers\Services\EAPIService;
use Illuminate\Http\Request;

class GetSalesDocumentController extends Controller
{
    //
    protected $service;
    protected $api;

    public function __construct(GetSalesDocumentService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function getSalesDocuments(Request $req){

        if(env("isLive") == true){
            $dateNow = date('Y-m-d');
            if($dateNow >= '2023-08-14'){
                // info("Cron");
            }else{
                info("Get Sales Documents Cron Dismissed...Date");
                return response("Get Sales Documents Cron Date Constrations");
            }
        }
        // echo "hello from staging";
        // die;
        info("Test Cron Sales Document Called");

        $param = array(
            "orderBy" => "changedSince",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "getRowsForAllInvoices" => 1,
            // "deliveryTypeID" => 1,
            // "active" => 1,
            // "pageNo" => $this->page,
            "changedSince" => $this->service->getLastUpdateDate(),
            "getAddedTimestamp" => 1
        );

        // if($req->salesDocumentID){
        //     $param["ids"]
        // }

        // dd($param);
        // die;
        $res = $this->api->sendRequest("getSalesDocuments", $param);
        // dd($res);
        if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            // print_r($res['records']);
            return $this->service->saveUpdate($res['records']);
        }
    }
}
