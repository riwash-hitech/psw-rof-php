<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetCustomerService;
use App\Http\Controllers\Paei\Services\GetGiftCardService;
use App\Http\Controllers\Services\EAPIService;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;

class GetGiftCardController extends Controller
{
    //
    use ResponseTrait;
    protected $service;
    protected $api;

    public function __construct(GetGiftCardService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function getGiftCards(){
        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            // "active" => 1,
            // "pageNo" => $this->page,
            "addedSince" => $this->service->getLastUpdateDate(), 
         );
         $res = $this->api->sendRequest("getGiftCards", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0){
            return $this->service->saveUpdate($res['records']);
         }

    }
}

 