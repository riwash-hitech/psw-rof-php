<?php
namespace App\Http\Controllers\Paei\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\InventoryTransfer;
use App\Models\PAEI\InventoryTransferLine;

class GetInventoryTransferService{

    protected $inventory;
    protected $api;

    public function __construct(InventoryTransfer $c, EAPIService $api){
        $this->inventory = $c;
        $this->api = $api;
    }

    public function saveUpdate($inventories){

        foreach($inventories as $c){
            $this->saveUpdateInventoryTransfer($c, $this->api->client->clientCode);
        }

        return response()->json(['status'=>200, 'message'=>"Inventory Write Offs fetched Successfully."]);
    }

    public function saveUpdateInventoryTransfer($product, $clientCode){

        //checking if erply transfer order
        $isErplyTO = false;
        if($product["type"] == "TRANSFER_ORDER"){
            if(@$product['attributes'] != ""){
                foreach(@$product['attributes'] as $att){
                    if($att["attributeName"] == "delivery_modes"){
                        $isErplyTO = true;
                    }
                }
            }else{
                $isErplyTO = true;
            }
        }

        //now checking transfer is ready or not
        $readyForAX = false;
        if($product["type"] == "TRANSFER"){

            //now if this transfer is originated from erply then ready for ax = 0

            //now getting parent TO
            $parentTO = InventoryTransfer::where("clientCode", $clientCode)->where("inventoryTransferID", $product["inventoryTransferOrderID"])->first();
            if($parentTO){

                //checking using attributes value
                if($parentTO->attributes != ''){
                    $pAtt = json_decode($parentTO->attributes, true);
                    foreach($pAtt as $tt){
                        if($tt["attributeName"] == "TransferNumber"){
                            $readyForAX = true;
                        }
                    }
                }
            }
            
        }

        $this->inventory->updateOrCreate(
                [
                    "clientCode" => $clientCode,
                    "inventoryTransferID"  =>  $product['inventoryTransferID']
                ],
                [
                    "clientCode" => $clientCode,
                    "inventoryTransferID" => $product['inventoryTransferID'],
                    "inventoryTransferNo" => $product['inventoryTransferNo'],
                    "creatorID" => @$product['creatorID'],
                    "warehouseFromID" => @$product['warehouseFromID'],
                    "warehouseToID" => @$product['warehouseToID'],
                    "deliveryAddressID"  => @$product['deliveryAddressID'],
                    "currencyCode"  => @$product['currencyCode'],
                    "currencyRate"  =>  @$product['currencyRate'],
                    "type"  => $product['type'],
                    "inventoryTransferOrderID"  => @$product['inventoryTransferOrderID'],
                    "followupInventoryTransferID"  => @$product['followupInventoryTransferID'],
                    "date"  =>  @$product['date'],
                    "shippingDate"  =>  @$product['shippingDate'],
                    "shippingDateActual"  =>  @$product['shippingDateActual'],
                    "inventoryTransactionDate"  =>  @$product['inventoryTransactionDate'] ? @$product['inventoryTransactionDate'] : '0000-00-00',
                    "status"  =>  @$product['status'],
                    "notes"  =>  @$product['notes'],
                    "added"  => date('Y-m-d H:i:s',$product['added']),
                    "confirmed"  => @$product['confirmed'],
                    "lastModified"  => isset($product['lastModified']) == 1 && isset($product['lastModified']) != null ? date('Y-m-d H:i:s',$product['added']) : "0000-00-00 00:00:00",
                    "rows"  => !empty($product['rows']) ? json_encode($product['rows'],1) : '', 
                    "attributes"  => !empty($product['attributes']) ? json_encode($product['attributes'],1) : '', 
                    "isErplyTO" => $isErplyTO == true ? 1 : 0,
                    "readyForAX" => $readyForAX == true ? 1 : 0,
                    
                     
                ]
            );

            //now for transfers lines
            foreach($product['rows'] as $line){
                $details = array(
                    "clientCode" => $clientCode,
                    "transferID" => $product['inventoryTransferID'],
                    "stableRowID" => $line['stableRowID'],
                    "productID" => $line['productID'],
                    "price" => $line['price'],
                    "amount" => $line['amount'], 
                );
                InventoryTransferLine::updateOrcreate(
                    [
                        "clientCode" => $clientCode,
                        "transferID" => $product['inventoryTransferID'],
                        "stableRowID" => $line['stableRowID']
                    ],
                    $details
                );
            }
    }

    public function deleteInventoryTransfer(){

        $po = InventoryTransfer::where("clientCode", $this->api->client->clientCode)->where("deleted", 0)->limit(100)->get();

        if(count($po) < 1){
            info("All Transfer Order Deleted to Erply");
            return response("All Transfer Order Deleted to Erply");
        }

        $bulkReq = array();

        foreach($po as $p){
            $param = array(
                "clientCode" => $this->api->client->clientCode,
                "sessionKey" => $this->api->client->sessionKey,
                "requestName" => "deleteInventoryTransfer",
                "inventoryTransferID" => $p->inventoryTransferID
            );
            $bulkReq[] = $param;
        }

        $param = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );
        $bulkReq = json_encode($bulkReq, true);
        $res = $this->api->sendRequest($bulkReq, $param, 1);

        if($res['status']['errorCode'] == 0){
            foreach($po as $p){
                $p->deleted = 1;
                $p->save();
            }
        }
        info("Transfer Order Deleted Successfully");
        return response("Transfer Order Deleted Successfully");


    }


    public function getLastUpdateDate(){
        // echo "im call";
         $latest = $this->inventory->orderBy('added', 'desc')->first();
        if($latest){
            return strtotime($latest->added);
        }
        return 0;// strtotime($latest);
    }
}
