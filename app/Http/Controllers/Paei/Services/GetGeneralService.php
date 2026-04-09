<?php
namespace App\Http\Controllers\Paei\Services;

use App\Classes\UserLogger;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Assortment;
use App\Models\PAEI\Cashin;
use App\Models\PAEI\ServerInfo;
use App\Models\PAEI\UserOperationLog;
use App\Traits\ResponseTrait;
class GetGeneralService {

    use ResponseTrait; 
    // protected $assortment;
    protected $api;

    public function __construct(EAPIService $api){
        // $this->assortment = $assortment;
        $this->api = $api;
    }

    public function syncServerInfo($datas){

        foreach($datas as $data){

            ServerInfo::updateOrcreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                ],
                [
                    "all" => json_encode($data, true),
                    "clientCode" => $this->api->client->clientCode,
                    "timezone" => $data["timezone"]
                ]
            );
        }

        info("Server Info Syncced");
        return response("Server Info Syncced");

    }


      
}


