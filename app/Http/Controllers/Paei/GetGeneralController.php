<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Paei\Services\GetGeneralService; 
use App\Http\Controllers\Services\EAPIService; 

class GetGeneralController extends Controller
{
    //
    protected $service;
    protected $api;

    public function __construct(GetGeneralService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function syncServerInfo(){
        
         $param = array(
            "orderBy" => "changed",
            "orderByDir" => "asc",
            // "recordsOnPage" => "200", 
            // "changedSince" => $this->service->getLastUpdateDate(), 
         );
         $res = $this->api->sendRequest("getConfParameters", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->syncServerInfo($res['records']);
         }

        //  return response()->json(["status" => "success", "message" => "No Coupons found"]);
    }

     

}
