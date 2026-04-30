<?php
namespace App\Http\Controllers\Paei\Services;

use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\{ProductGroup, Warehouse};
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use App\Traits\UserOperationTrait;

class GetWarehouseService implements UserOperationInterface{

    protected $warehouse;
    protected $api;
    use UserOperationTrait;

    public function __construct(LiveWarehouseLocation $w, EAPIService $api){
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


    protected function warehouseSaveUpdate($product)
    {
// dd($product);

        $this->warehouse->updateOrCreate(
            [
                "entity" =>'PSW',
                "erplyID"    => $product['id']
            ],
            [
                "entity" => 'PSW',

                "erplyID" => $product['id'],
                "erplyAssortmentID" => $product['assortmentID'] ?? null,

                "LocationName" => $product['name']['en'] ?? null,
                "LocationType"  => $product['type'] ?? null,

                "StoreID" => $product['code'] ?? null,
                "LocationID" => !empty($product['code']) ? $product['code'] : null,

                "ADDRESS" => $product['address'] ?? null,
                "STREET"  => $product['street'] ?? null,
                "CITY"    => $product['city'] ?? null,
                "STATE"   => $product['state'] ?? null,
                "Postcode" => $product['postcode'] ?? null,

                "EMAIL" => $product['email'] ?? null,
                "PHONE" => $product['phone'] ?? null,
                // "website" => $product['website'] ?? null,

                "LONGITUDE" => $product['longitude'] ?? null,
                "LATITUDE"  => $product['latitude'] ?? null,

                "StoreHours" => isset($product['storeHours'])
                    ? json_encode($product['storeHours'])
                    : null,

                // "erplyPending" => $product['erplyPending'] ?? 0,
                "productAssortment" => $product['productAssortment'] ?? 0,
                "removePA" => $product['removePA'] ?? 0,

                "sohPending" => $product['sohPending'] ?? 0,
                "sohUpdate" => $product['sohUpdate'] ?? 0,

                "binbayPending" => $product['binbayPending'] ?? 0,
                "binbaySOHPending" => $product['binbaySOHPending'] ?? 0,

                "parentGroupID" => $product['parentGroupID'] ?? null,

                "created_at" => isset($product['added'])
                    ? date('Y-m-d H:i:s', $product['added'])
                    : null,

                "updated_at" => isset($product['changed'])
                    ? date('Y-m-d H:i:s', $product['changed'])
                    : null,

                "isIgnored" => $product['isIgnored'] ?? 0,
            ]
        );
    }

    // protected function warehouseSaveUpdate($product){

    //     $this->warehouse->updateOrCreate(
    //             [
    //                 "clientCode" => $this->api->client->clientCode,
    //                 "warehouseID"  =>  $product['id']
    //             ],
    //             [
    //                 "clientCode" => $this->api->client->clientCode,
    //                 "warehouseID" => $product['id'],
    //                 "name" => $product['name']['en'],
    //                 "code" => $product['code'],
    //                 "storeRegionID" => $product['storeRegionId'],
    //                 "assortmentID"  => @$product['assortmentID'],
    //                 "priceListID"  => @$product['priceListID'],
    //                 "priceListID2"  => @$product['priceListID2'],
    //                 "priceListID3"  => @$product['priceListID3'],
    //                 "order_sw"  => @$product['order'],
    //                 "phone"  => @$product['phone'],
    //                 "fax"  => @$product['fax'],
    //                 "email"  => @$product['email'],
    //                 "website"  => @$product['website'],
    //                 "bankName"  => @$product['bankName'],
    //                 "bankAccountNumber"  => @$product['bankAccountNumber'],
    //                 "iban"  => @$product['iban'],
    //                 "swift"  => @$product['swift'],
    //                 "onlineAppointmentsEnabled"  => @$product['onlineAppointmentsEnabled'] == true ? 1 : 0,
    //                 "timeZone"  => @$product['timeZone'],
    //                 "storeGroups"  => @$product['storeGroups'],
    //                 "priceListID4"  => @$product['priceListID4'],
    //                 "priceListID5"  => @$product['priceListID5'],
    //                 "defaultCustomerGroupID"  => @$product['defaultCustomerGroupID'],
    //                 "receiptAddressID"  => @$product['receiptAddressID'],
    //                 "added"  =>  date('Y-m-d H:i:s',$product['added']),
    //                 "addedBy" => $product['addedBy'],
    //                 "changed" => date('Y-m-d H:i:s',$product['changed']),
    //                 "changedBy" => $product['changedBy'],

    //             ]
    //         );
    // }


    public function getLastUpdateDate(){
        // echo "im call";
        //  $latest = $this->warehouse->where('clientCode', $this->api->client->clientCode)->orderBy('added', 'desc')->first();
        // if($latest){
        //     return strtotime($latest->added);
        // }
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
