<?php
namespace App\Http\Controllers\Paei\Services;

use App\Classes\UserLogger;
use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\ProductGroup;
use App\Traits\UserOperationTrait;

class GetProductGroupService implements UserOperationInterface{

    protected $group;
    protected $api;
    protected $letsLog;
    use UserOperationTrait;

    public function __construct(ProductGroup $pg, EAPIService $api, UserLogger $logger){
        $this->group = $pg;
        $this->api = $api;
        $this->letsLog = $logger;
    }

    public function saveUpdateOldAPI($products){

        foreach($products as $p){
            $this->groupSaveUpdateOldAPI($p);
        }

        return response()->json(['status'=>200, 'message'=>"Product Group fetched Successfully."]);
    }

    protected function groupSaveUpdateOldAPI($product){
        //for log
        $old = $this->group->where('clientCode',  $this->api->client->clientCode)->where('productGroupID', $product['productGroupID'])->first();
        $change =ProductGroup::updateOrCreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "productGroupID"  =>  $product['productGroupID']
                ],
                [
                    "clientCode" => $this->api->client->clientCode,
                    "productGroupID" => $product['productGroupID'],
                    "name" => @$product['name'],
                    "showInWebshop" => @$product['showInWebshop'],
                    "nonDiscountable" => @$product['nonDiscountable'],
                    "positionNo"  => @$product['positionNo'],
                    "parentGroupID"  => @$product['parentGroupID'],
                    "images"  => '',//!empty($product['images']) ? json_encode($product['images'],1) : '',
                    "subGroups"  => !empty(@$product['subGroups']) ? json_encode(@$product['subGroups'],1) : '',
                    "attributes"  => '',//!empty($product['attributes']) ? json_encode($product['attributes'],1) : '',
                    "vatrates"  =>  '',//!empty($product['vatrates']) ? json_encode($product['vatrates'],1) : '',
                    "added"  =>  date('Y-m-d H:i:s', @$product['added']),
                    // "addedBy" => $product['addedby'],
                    "changed" => date('Y-m-d H:i:s', @$product['lastModified']),
                    // "changedBy" => $product['changedby'],

                ]
            );
            $this->letsLog->setChronLog($old ? json_encode($old, true) : '', json_encode($change, true), $old  ? "Product Group Updated" : "Product Group Created");        
    }

    public function saveUpdate($products){

        foreach($products as $p){
            $this->groupSaveUpdate($p);
        }

        return response()->json(['status'=>200, 'message'=>"Product Group fetched Successfully."]);
    }

    protected function groupSaveUpdate($product){

        $this->group->updateOrCreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "productGroupID"  =>  $product['id']
                ],
                [
                    "clientCode" => $this->api->client->clientCode,
                    "productGroupID" => $product['id'],
                    "name" => $product['name']['en'],
                    "showInWebshop" => $product['show_in_webshop'],
                    "nonDiscountable" => $product['non_discountable'],
                    "positionNo"  => @$product['positionNo'],
                    "parentGroupID"  => $product['parent_id'],
                    "images"  => '',//!empty($product['images']) ? json_encode($product['images'],1) : '',
                    "subGroups"  => '',//!empty($product['subGroups']) ? json_encode($product['subGroups'],1) : ''
                    "attributes"  => '',//!empty($product['attributes']) ? json_encode($product['attributes'],1) : '',
                    "vatrates"  =>  '',//!empty($product['vatrates']) ? json_encode($product['vatrates'],1) : '',
                    "added"  =>  date('Y-m-d H:i:s',$product['added']),
                    "addedBy" => $product['addedby'],
                    "changed" => date('Y-m-d H:i:s',$product['changed']),
                    "changedBy" => $product['changedby'],

                ]
            );
    }


    public function getLastUpdateDate(){
        // echo "im call";
         $latest = $this->group->where('clientCode',$this->api->client->clientCode )->orderBy('changed', 'desc')->first();
        if($latest){
            return strtotime($latest->changed);
        }
        return 0;// strtotime($latest);
    }

    //for  operation logs
    public function deleteRecords($res, $clientCode){
 
        foreach($res as $l){
            $this->handleOperationLog($l,$clientCode,  $l['itemID']);
            if($l['operation'] == 'delete'){
                ProductGroup::deleteRecords($clientCode,$l["itemID"]);
                // MatrixProduct::deleteProduct($clientCode,$l["itemID"]);
            }
        }
    }

}
