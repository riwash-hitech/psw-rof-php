<?php
namespace App\Http\Controllers\Paei\Services;

use App\Classes\UserLogger;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Assortment;
use App\Models\PAEI\Cashin;
use App\Models\PAEI\CSAddresses;
use App\Models\PAEI\ServerInfo;
use App\Models\PAEI\UserOperationLog;
use App\Traits\ResponseTrait;

class GetAddressService {

    use ResponseTrait; 
    // protected $assortment;
    protected $api;

    public function __construct(EAPIService $api){
        // $this->assortment = $assortment;
        $this->api = $api;
    }

    public function getAddresses($datas){

        foreach($datas as $data){
            $details = array(
                "clientCode" => $this->api->client->clientCode,
                "addressID" => $data["addressID"],
                "ownerID" => $data["ownerID"],
                "typeID" => $data["typeID"],
                "typeName" => $data["typeName"],
                "typeActivelyUsed" => $data["typeActivelyUsed"],
                "street" => $data["street"],
                "address2" => $data["address2"],
                "city" => $data["city"],
                "postalCode" => $data["postalCode"],
                "state" => $data["state"],
                "country" => $data["country"],
                "added" => date('Y-m-d H:i:s', @$data['added']),
                "lastModified" => $data["lastModified"] > 0 ? date('Y-m-d H:i:s', @$data['added']) : date('Y-m-d H:i:s', @$data['added']),
                "lastModifierUsername" => $data["lastModifierUsername"],
                "lastModifierEmployeeID" => $data["lastModifierEmployeeID"],
                "attributes" => !empty($data['attributes']) ? json_encode($data['attributes'], true) : '',
                
            );
            CSAddresses::updateOrcreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "addressID" => $data["addressID"],
                ],
                $details
            );
        }

        // info("Server Info Syncced");
        return response("Get Addresses Fetched Successfully.");

    }

    public function getAddressesV2($datas){

        foreach($datas as $data){
            $details = array(
                "clientCode" => $this->api->client->clientCode,
                "addressID" => $data["id"],
                "ownerID" => $data["customerId"],
                "typeID" => $data["typeId"],
                // "typeName" => $data["typeName"],
                // "typeActivelyUsed" => $data["typeActivelyUsed"],
                "street" => $data["street"],
                "address2" => $data["address2"],
                "city" => $data["city"],
                "postalCode" => $data["postCode"],
                "state" => $data["state"],
                "country" => $data["country"],
                "added" => date('Y-m-d H:i:s', @$data['added']),
                "lastModified" => $data["changed"] > 0 ? date('Y-m-d H:i:s', @$data['changed']) : '0000-00-00',
                "lastModifierUsername" => $data["changedBy"],
                // "lastModifierEmployeeID" => $data["lastModifierEmployeeID"],
                "attributes" => !empty(@$data['attributes']) ? json_encode(@$data['attributes'], true) : '',
                
            );
            CSAddresses::updateOrcreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "addressID" => $data["id"],
                ],
                $details
            );
        }

        // info("Server Info Syncced");
        return response("Get Addresses V2 Fetched Successfully.");

    }

    public function getLastUpdateDate(){
        // echo "im call";
         $latest = CSAddresses::orderBy('added', 'desc')->first();
        if($latest){
            return strtotime($latest->added);
        }
        return 0;// strtotime($latest);
    }


      
}


