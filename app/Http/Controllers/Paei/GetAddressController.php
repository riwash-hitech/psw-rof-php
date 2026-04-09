<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetAddressService;
use App\Http\Controllers\Paei\Services\GetGeneralService; 
use App\Http\Controllers\Services\EAPIService; 

class GetAddressController extends Controller
{
    //
    protected $service;
    protected $api;

    public function __construct(GetAddressService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function getAddresses(){
        
         $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "100", 
            "addedSince" => $this->service->getLastUpdateDate(), 
         );
         $res = $this->api->sendRequest("getAddresses", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->getAddresses($res['records']);
         }

         return response()->json(["status" => "success", "message" => "No Records Found"]);
    }


    public function getAddressesBySwagger(){
        $param = array(
            "take" => "100",
            // "sort" => json_encode([
            //     "selector" => "added",
            //     "desc" => false
            // ]),
            "match" => ">=",
            "changed" => $this->service->getLastUpdateDate(),
            "orderBy" => 'added',
            // "getAddresses" => 1, 
            "orderByDirection" => 'ASC'
        );

        $res = $this->api->sendRequestBySwagger("https://api-crm-au.erply.com/v1/addresses", $param);
        if(count($res) > 0){
            return $this->service->getAddressesV2($res);
        }

        return response()->json(["status" => "success", "message" => "All Addresses Syncced to Synccare."]);

        // dd($res);

    }

     

}
