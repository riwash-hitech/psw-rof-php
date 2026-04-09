<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller;  
use App\Http\Controllers\Paei\Services\GetPricelistService;
use App\Http\Controllers\Services\EAPIService; 

class GetPricelistController extends Controller
{
    //
    protected $service;
    protected $api;

    public function __construct(GetPricelistService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function getPricelist(){
        
        $param = array(
            "orderBy" => "changed",
            "orderByDir" => "asc",
            "recordsOnPage" => "200", 
            // "pageNo" => $this->page,
            "changedSince" => $this->service->getLastUpdateDate(), 
         );

         $res = $this->api->sendRequest("getPriceLists", $param);
          
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->saveUpdate($res['records']);
         }
    }
}
