<?php
namespace App\Http\Controllers\Paei\Services;

use App\Classes\UserLogger;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Payment;
use App\Models\PAEI\Pricelist;

class GetPricelistService{

    protected $pricelist;
    protected $letsLog;
    protected $api;

    public function __construct(Pricelist $c, UserLogger $logger, EAPIService $api){
        $this->pricelist = $c;
        $this->letsLog = $logger;
        $this->api = $api;
    }

    public function saveUpdate($pricelists){

        foreach($pricelists as $c){

            $this->saveUpdatePricelist($c);
        }

        return response()->json(['status'=>200, 'message'=>"Pricelist fetched Successfully."]);
    }

    protected function saveUpdatePricelist($product){
        $old = $this->pricelist->where('clientCode', $this->api->client->clientCode)->where('pricelistID', $product['pricelistID'])->first();
        $new = $this->pricelist->updateOrCreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "pricelistID"  =>  $product['pricelistID']
                ],
                [
                    "clientCode" => $this->api->client->clientCode,
                    'pricelistID' => @$product['pricelistID'],
                    'name' => @$product['name'],
                    'startDate' => @$product['startDate'],
                    'endDate' => @$product['endDate'],
                    'active' => @$product['active'],
                    'type' => @$product['type'],
                    'pricelistRules' => !empty($product['pricelistRules']) ? json_encode($product['pricelistRules'],1) : '',  
                    'addedByUserName' => @$product['addedByUserName'], 
                    'lastModifiedByUserName' => @$product['lastModifiedByUserName'], 
                    "attributes" => !empty($product['attributes']) ? json_encode($product['attributes'],1) : '', 
                    "added" =>  date('Y-m-d H:i:s',$product['added']), 
                    "lastModified" => date('Y-m-d H:i:s', $product['lastModified']),  
                ]
            );
            $this->letsLog->setChronLog($old ? json_encode($old, true) : '', json_encode($new, true), $old  ? "PriceList Updated" : "PriceList Created");    
    }


    public function getLastUpdateDate(){
        // echo "im call";
         $latest = $this->pricelist->orderBy('lastModified', 'desc')->first();
        if($latest){
            return strtotime($latest->lastModified);
        }
        return 0;// strtotime($latest);
    }
}
