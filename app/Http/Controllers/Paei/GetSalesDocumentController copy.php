<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Paei\Services\GetSalesDocumentService;
use App\Http\Controllers\Services\EAPIService; 

class GetSalesDocumentController extends Controller
{
    //
    protected $service;
    protected $api;

    public function __construct(GetSalesDocumentService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function getSalesDocuments(){
        $param = array(
            "orderBy" => "changedSince",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "getRowsForAllInvoices" => 1,
            "deliveryTypeID" => 1,
            // "active" => 1,
            // "pageNo" => $this->page,
            "changedSince" => $this->service->getLastUpdateDate(), 
        );

        $res = $this->api->sendRequest("getSalesDocuments", $param);
        // dd($res);
        if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            // print_r($res['records']);
            return $this->service->saveUpdate($res['records']);
        }
    }
}
