<?php

namespace App\Http\Controllers\Paei;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetCustomerService;
use App\Http\Controllers\Paei\Services\GetGiftCardService;
use App\Http\Controllers\Paei\Services\GetInventoryRegistrationService;
use App\Http\Controllers\Services\EAPIService;
use Illuminate\Http\Request;

class GetInventoryRegistrationController extends Controller
{
    //
    protected $service;
    protected $api;

    public function __construct(GetInventoryRegistrationService $service, EAPIService $api){
        $this->service = $service;
        $this->api = $api;
    }

    public function getInventoryRegistration(){
        //   [
                //   'take' => '200',
                //   'sort' => json_encode([
                //     "selector" => "changed",
                //     "desc" => false,
                //     ]),
                //   'match' => '>=',
                //     'changed' => $lastModified,
                //     'orderBy' => 'changed',
                //     'orderByDirection' => 'ASC',
                //    ]
        // $param = array(
        //     "take" => "100",
        //     "sort" => json_encode([
        //         "selector" => "changed",
        //         "desc" => false
        //     ]),
        //     "match" => ">=",
        //     "changed" => $this->service->getLastUpdateDate(),
        //     "orderBy" => 'changedSince',
        //     "orderByDirection" => 'ASC'
        //  );

        //  $res = $this->api->sendRequestBySwagger("https://api-crm-au.erply.com/v1/customers", $param);
        //  if(count($res) > 0){
        //     $this->service->saveUpdate($res);
        //  }

        $param = array(
            "orderBy" => "changedSince",
            "orderByDir" => "asc",
            "recordsOnPage" => "50",
            // "active" => 1,
            // "pageNo" => $this->page,
            "changedSince" => $this->service->getLastUpdateDate(), 
         );
         $res = $this->api->sendRequest("getInventoryRegistrations", $param);
        //  dd($res);
         if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            return $this->service->saveUpdate($res['records']);
         }
    }
}
