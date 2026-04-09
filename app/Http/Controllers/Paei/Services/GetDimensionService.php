<?php
namespace App\Http\Controllers\Paei\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\MatrixDimension;
use App\Models\PAEI\MatrixDimensionVariation;

class GetDimensionService{

    protected $dimension;
    protected $variation;
    protected $api;

    public function __construct(MatrixDimension $c, MatrixDimensionVariation $variation, EAPIService $api){
        $this->dimension = $c;
        $this->variation = $variation;
        $this->api = $api;
    }

    public function saveUpdate($dimensions){

        foreach($dimensions as $c){
            $this->saveUpdateDimension($c);
            //saving variation value
            if(@$c['variations']){
                $this->saveUpdateDimensionValue(@$c['variations'], $c['dimensionID']);
            }
            
        }

        return response()->json(['status'=>200, 'message'=>"Dimension fetched Successfully."]);
    }

    protected function saveUpdateDimension($product){

        $this->dimension->updateOrCreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "dimensionID"  =>  $product['dimensionID']
                ],
                [
                    "clientCode" => $this->api->client->clientCode,
                    "dimensionID" => $product['dimensionID'],
                    "name" => $product['name'],
                    "active" => $product['active'],
                    "lastModified"  => isset($product['lastModified']) == 1 ? date('Y-m-d H:i:s',$product['lastModified']) : '0000-00-00 00:00',
                    "added"  => isset($product['added']) == 1 ? date('Y-m-d H:i:s',$product['added']) : '0000-00-00 00:00',
                     
                ]
            );
    }

    protected function saveUpdateDimensionValue($variation, $pid){
        foreach($variation as $dimVal){
            $this->variation->where('clientCode', $this->api->client->clientCode)->where('dimensionID', $pid)->updateOrCreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "variationID"  =>  $dimVal['variationID']
                ],
                [
                    "clientCode" => $this->api->client->clientCode,
                    "variationID" => $dimVal['variationID'],
                    "dimensionID" => $pid,
                    "name" => $dimVal['name'],
                    "code" => $dimVal['code'],
                    "order" => $dimVal['order'],
                    "active" => $dimVal['active'],
                    "lastModified"  => isset($dimVal['lastModified']) == 1 ? date('Y-m-d H:i:s',$dimVal['lastModified']) : '0000-00-00 00:00',
                    "added"  => isset($dimVal['added']) == 1 ? date('Y-m-d H:i:s',$dimVal['added']) : '0000-00-00 00:00',
                     
                ]
            );
        }
    }


    public function getLastUpdateDate(){
        // echo "im call";
         $latest = $this->dimension->orderBy('lastModified', 'desc')->first();
        if($latest){
            return strtotime($latest->lastModified);
        }
        return 0;// strtotime($latest);
    }
}
