<?php
namespace App\Http\Controllers\Paei\Services;

use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\ProductGroup;
use App\Models\PAEI\Warehouse;
use App\Traits\UserOperationTrait;

class GetWarehouseService implements UserOperationInterface{

    protected $warehouse;
    protected $api;
    use UserOperationTrait;

    public function __construct(Warehouse $w, EAPIService $api){
        $this->warehouse = $w;
        $this->api = $api;
    }

    public function saveUpdate($warehouses){

        foreach($warehouses as $p){
            $this->warehouseSaveUpdate($p);
        }
        return response()->json(['status'=>200, 'message'=>"Warehouse data fetched Successfully."]);
        // echo "Warehouse Fetched Successfully.";
    }

    protected function warehouseSaveUpdateOleApi($product){

        $this->warehouse->updateOrCreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "warehouseID"  =>  $product['warehouseID']
                ],
                [
                    "clientCode" => $this->api->client->clientCode,
                    "warehouseID" => $product['warehouseID'],
                    "name" => $product['name'],
                    "code" => $product['code'],
                    "storeRegionID" => $product['storeRegionID'],
                    "assortmentID"  => @$product['assortmentID'],
                    "priceListID"  => @$product['pricelistID'],
                    "priceListID2"  => @$product['pricelistID2'],
                    "priceListID3"  => @$product['pricelistID3'],
                    "address"  => @$product['address'],
                    "address2"  => @$product['address2'],
                    "street"  => @$product['street'],
                    "city"  => @$product['city'],
                    "state"  => @$product['state'],
                    "country"  => @$product['country'],
                    "ZIPcode"  => @$product['ZIPcode'],
                    "phone"  => @$product['phone'],
                    "fax"  => @$product['fax'],
                    "email"  => @$product['email'],
                    "website"  => @$product['website'],
                    "bankName"  => @$product['bankName'],
                    "bankAccountNumber"  => @$product['bankAccountNumber'],
                    "iban"  => @$product['iban'],
                    "swift"  => @$product['swift'],
                    "onlineAppointmentsEnabled"  => @$product['onlineAppointmentsEnabled'] == true ? 1 : 0,
                    "timeZone"  => @$product['timeZone'],
                    "storeGroups"  => @$product['storeGroups'],
                    "priceListID4"  => @$product['priceListID4'],
                    "priceListID5"  => @$product['priceListID5'],
                    "defaultCustomerGroupID"  => @$product['defaultCustomerGroupID'],
                    "receiptAddressID"  => @$product['receiptAddressID'],
                    "attributes"  => !empty($product['attributes']) ? json_encode($product['attributes'], true) : '',
                    // "added"  =>  date('Y-m-d H:i:s',$product['added']),
                    // "addedBy" => $product['addedBy'],
                    // "changed" => date('Y-m-d H:i:s',$product['changed']),
                    // "changedBy" => $product['changedBy'],

                ]
            );
    }

    protected function warehouseSaveUpdate($product){

        $this->warehouse->updateOrCreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "warehouseID"  =>  $product['id']
                ],
                [
                    "clientCode" => $this->api->client->clientCode,
                    "warehouseID" => $product['id'],
                    "name" => $product['name']['en'],
                    "code" => $product['code'],
                    "storeRegionID" => $product['storeRegionId'],
                    "assortmentID"  => @$product['assortmentID'],
                    "priceListID"  => @$product['priceListID'],
                    "priceListID2"  => @$product['priceListID2'],
                    "priceListID3"  => @$product['priceListID3'],
                    "order_sw"  => @$product['order'],
                    "phone"  => @$product['phone'],
                    "fax"  => @$product['fax'],
                    "email"  => @$product['email'],
                    "website"  => @$product['website'],
                    "bankName"  => @$product['bankName'],
                    "bankAccountNumber"  => @$product['bankAccountNumber'],
                    "iban"  => @$product['iban'],
                    "swift"  => @$product['swift'],
                    "onlineAppointmentsEnabled"  => @$product['onlineAppointmentsEnabled'] == true ? 1 : 0,
                    "timeZone"  => @$product['timeZone'],
                    "storeGroups"  => @$product['storeGroups'],
                    "priceListID4"  => @$product['priceListID4'],
                    "priceListID5"  => @$product['priceListID5'],
                    "defaultCustomerGroupID"  => @$product['defaultCustomerGroupID'],
                    "receiptAddressID"  => @$product['receiptAddressID'],
                    "added"  =>  date('Y-m-d H:i:s',$product['added']),
                    "addedBy" => $product['addedBy'],
                    "changed" => date('Y-m-d H:i:s',$product['changed']),
                    "changedBy" => $product['changedBy'],

                ]
            );
    }


    public function getLastUpdateDate(){
        // echo "im call";
         $latest = $this->warehouse->where('clientCode', $this->api->client->clientCode)->orderBy('added', 'desc')->first();
        if($latest){
            return strtotime($latest->added);
        }
        return 0;// strtotime($latest);
    }

    public function deleteRecords($res, $clientCode){
 
        foreach($res as $l){
            $this->handleOperationLog($l,$clientCode,  $l['itemID']);
            if($l['operation'] == 'delete'){
                Warehouse::deleteRecords($clientCode,$l["itemID"]);
                // MatrixProduct::deleteProduct($clientCode,$l["itemID"]);
            }
        }
    }

    public function getDefaultTimeZone(){
        
    }

}
