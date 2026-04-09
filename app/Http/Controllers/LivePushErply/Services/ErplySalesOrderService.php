<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveSalesOrder;
use App\Models\PswClientLive\Local\LiveSupplier;
use Illuminate\Http\Request;

class ErplySalesOrderService{
    //
    protected $api;
    protected $customer;

    public function __construct(EAPIService $api, LiveCustomer $customer){
        $this->api = $api;
        $this->customer = $customer;
       
    }

    public function pushSalesOrders($req){

        if($req->salesid){
            $salesOrders = LiveSalesOrder::join("newsystem_customer_business_relations","newsystem_customer_business_relations.PSW_SMMCUSTACCOUNT","live_sales_orders.CUSTACCOUNT")
            ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "live_sales_orders.")
            ->where("live_sales_orders.pendingProcess", 1)
            ->where("newsystem_customer_business_relations.pendingProcess", 0)
            ->where("live_sales_orders.SALESID", $req->salesid)
            ->whereHas("location")
            ->with("location")
            ->where("live_sales_orders.deliveryPending", 0)
            ->where("live_sales_orders.lineIncomplete", 0)
            ->where("live_sales_orders.created_at",'>', '2023-07-01')
            ->select(["newsystem_customer_business_relations.customerID","live_sales_orders.*"])
            ->groupBy("live_sales_orders.SALESID")->limit(1)->get();
        }else{
            $salesOrders = LiveSalesOrder::join("newsystem_customer_business_relations","newsystem_customer_business_relations.PSW_SMMCUSTACCOUNT","live_sales_orders.CUSTACCOUNT")
            ->whereHas("location")
            ->with("location")
            ->where("live_sales_orders.pendingProcess", 1)
            ->where("live_sales_orders.deliveryPending", 0)
            ->where("live_sales_orders.lineIncomplete", 0)
            ->where("live_sales_orders.created_at",'>', '2023-07-01')
            ->where("newsystem_customer_business_relations.pendingProcess", 0)
            ->select(["newsystem_customer_business_relations.customerID","newsystem_customer_business_relations.addressID as mailingAddress","live_sales_orders.*"])
            ->groupBy("live_sales_orders.SALESID")->limit(3)->get();
        }


        // dd($salesOrders);

        $BundleArray = array();
        foreach($salesOrders as $so){
            $reqArray = array(
                "requestName" => "saveSalesDocument",
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "type" => "ORDER",  
                "warehouseID" => $so->location->erplyID,
                "customerID" => $so->customerID,
                "shipToAddressID" => $so->deliveryAddressID,
                "shipToID" => $so->deliveryAddressID,

                "attributeName1" => "SALESID",
                "attributeType1" => "text",
                "attributeValue1" => $so->SALESID, 
                "attributeName2" => "CUSTACCOUNT",
                "attributeType2" => "text",
                "attributeValue2" => $so->CUSTACCOUNT, 

            ); 
            if($so->mailingAddress > 0){
                $reqArray["addressID"] = $so->mailingAddress;
            }

            //add sales lines

            $lines = LiveSalesOrder::where("SALESID", $so->SALESID)->get();
            // dd($lines);
            $totLines = count($lines); 
            $exactLines = 0;
            foreach($lines as $key => $l){

                //now getting product of sales line
                $product = LiveProductVariation::where('erplyID','>',0)->where("ERPLYSKU", $l->erplysku1)->first();
                
                if(!$product){
                    $product = LiveProductVariation::where('erplyID','>',0)->where("CONFIGID", $l->CONFIGID)->where("ITEMID", $l->ITEMID)->where("ColourID", $l->INVENTCOLORID)->where("SizeID", $l->INVENTSIZEID)->first();
                    // dd($product);

                }
                // dd($product);
                if($product){
                    $reqArray["productID".$key+1] = $product->erplyID;
                    $reqArray["amount".$key+1] = abs((int)$l->SALESQTY);
                    // $reqArray["price".$key+1] = $l->id;
                    // $reqArray["discount".$key+1] = $l->id;
                    // $reqArray["ZIPCode".$key+1] = $l->DELIVERYZIPCODE;
                    // $reqArray["State".$key+1] = $l->DELIVERYSTATE;
                    // $reqArray["City".$key+1] = $l->DELIVERYCITY;
                    // $reqArray["discount".$key+1] = $l->id;
                    // $reqArray["discount".$key+1] = $l->id;
                    // $reqArray["discount".$key+1] = $l->id;

                    $exactLines++;

                }

            }

            if($exactLines == $totLines){
                array_push($BundleArray,$reqArray );
            }else{
                info("AX Sales : Exact Sales Lines Not Found.");
                //set flags
                LiveSalesOrder::where("SALESID", $so->SALESID)->update(["lineIncomplete" => 1]);
            }
                
        }

        // dd($BundleArray);
        // die;
        
        $finalOrder = $BundleArray; 
        // dd($BundleArray);
        if(count($BundleArray) < 1){
            info("All Sales Order Synced.");
            return response("All Sales Order Synced.");
        }

        $BundleArray = json_encode($BundleArray, true);
        $param = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );
        $res = $this->api->sendRequest($BundleArray, $param, 1);

        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($finalOrder as $key => $c){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    LiveSalesOrder::where("SALESID", $c["attributeValue1"])->update(['pendingProcess' => 0,'erplyID' => $res['requests'][$key]['records'][0]['invoiceID']]);
                }
            }
            info("Sales ORder Created or Updated to Erply");
        }

        return response()->json(["status" => "success", "response" => $res]);
        

    }

 
    public function pushSalesDeliveryAddress(){

        $orderDelivery = LiveSalesOrder::join("newsystem_customer_business_relations","newsystem_customer_business_relations.PSW_SMMCUSTACCOUNT","live_sales_orders.CUSTACCOUNT")
            // ->whereHas("location")
            // ->with("location")
            ->where("live_sales_orders.pendingProcess", 1)
            ->where("live_sales_orders.deliveryPending", 1)
            ->where("live_sales_orders.created_at",'>', '2023-07-01')
            // ->where("live_sales_orders.lineIncomplete", 0)
            ->where("newsystem_customer_business_relations.pendingProcess", 0)
            ->select(["newsystem_customer_business_relations.customerID","live_sales_orders.*"])
            ->groupBy("live_sales_orders.SALESID")
            ->limit(3)
            ->get();
        
        if($orderDelivery->isEmpty()){
            info("All AX Sales Order Delivery Address Pushed.");
            return response("All AX Sales Order Delivery Address Pushed.");
        }

        // dd($orderDelivery);
        $bulkDel = array();

        //first get bulk addresses
        $getBulk = array();

        foreach($orderDelivery as $da){
            
            //now getting delivery from erply
            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "typeID" => 7,
                "ownerID" => $da->customerID,
                "recordsOnPage" => 100,
                "requestName" => "getAddresses",
                "clientCode" => $this->api->client->clientCode
            );
            
            $getBulk[] = $param;
             
            
        }
        
        // dd($bulkDel);

        if(count($getBulk) < 1){
            info("All Order Delivery Address Syned to Erply.");
            return response("All Order Delivery Address Syned to Erply.");
        }

        $getBulk = json_encode($getBulk, true);
        $paramB = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        $getBulkRes = $this->api->sendRequest($getBulk, $paramB, 1);

        $saveDlvAdd = array();
        if($getBulkRes["status"]["errorCode"] == 0 && !empty($getBulkRes["requests"])){
            foreach($orderDelivery as $key => $od){


                $param = array(
                    "requestName" => "saveAddress",
                    "sessionKey" => $this->api->client->sessionKey,
                    "clientCode" => $this->api->client->clientCode,
                    "ownerID" => $da->customerID,
                    "typeID" => 7,
                );
                

                if($getBulkRes["requests"][$key]['status']['errorCode'] == 0){
                    //now checking delivery address
                    // if(empty($getBulkRes["requests"][$key]['records'])){
                    //     //no delivery address found 
                        


                    // }else{
                    //now delivery address exist
                    $isDeliveryExist = false;
                    $aID = 0;
                    foreach($getBulkRes["requests"][$key]["records"] as $add){
                        if($add["postalCode"] == $od->DELIVERYZIPCODE){
                            $isDeliveryExist = true;
                            $aID = $add["addressID"];
                        }
                    }

                    $param["street"] = $od->DELIVERYSTREET ? $da->DELIVERYSTREET : '';
                    $param["city"] = $od->DELIVERYCITY ? $da->DELIVERYCITY : '';
                    $param["postalCode"] = $od->DELIVERYZIPCODE ? $da->DELIVERYZIPCODE : '';
                    $param["state"] = $od->DELIVERYSTATE ? $da->DELIVERYSTATE : '';
                    $param["country"] = 'Australia';

                    if($isDeliveryExist == true){
                        $param["addressID"] = $aID;
                    }

                    $saveDlvAdd[] = $param;

                    // }
                }
            }
        }
        
        if(count($saveDlvAdd) < 1){
            info("All Order Delivery Address Syned to Erply.");
            return response("All Order Delivery Address Syned to Erply.");
        }
        
        $saveDlvAdd = json_encode($saveDlvAdd, true);
        
        $paramB = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );
        
        $dlvBulkRes = $this->api->sendRequest($saveDlvAdd, $paramB, 1);


        if($dlvBulkRes['status']['errorCode'] == 0 && !empty($dlvBulkRes['requests'])){
            foreach($orderDelivery as $key => $c){
                if($dlvBulkRes['requests'][$key]['status']['errorCode'] == 0){

                    LiveSalesOrder::where("SALESID", $c["SALESID"])->update(['deliveryPending' => 0,'deliveryAddressID' => $dlvBulkRes['requests'][$key]['records'][0]['addressID']]);
                }
            }
            info("Ax Sales Order Delivery Address Created or Updated to Erply");
        }
        
        return response()->json($dlvBulkRes);

    }




 
 
 
}
