<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\InventoryRegistration;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveOnHandInventory;
use App\Models\PswClientLive\Local\LiveProductGenericVariation;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LivePurchaseOrder;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;

class ErplyPurchaseOrderService{
    //
    protected $api;
    // protected $customer;

    public function __construct(EAPIService $api){
        $this->api = $api;
       
    }

    public function syncPurchaseOrder($req){
        // info("PO......");
        $limit = $req->limit ? $req->limit : 3;
         $purchaseOrders = LivePurchaseOrder::
                            join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_purchase_orders.INVENTLOCATIONID")
                            ->join("newsystem_suppliers","newsystem_suppliers.Name","newsystem_purchase_orders.PURCHNAME")
                            // ->join("newsystem_product_variation_live","newsystem_product_variation_live.ERPLYSKU","newsystem_purchase_orders.ERPLYSKU")
                            ->where("newsystem_suppliers.supplierID",">", 0)
                            ->where("newsystem_purchase_orders.pendingProcess",1)
                            ->where("newsystem_purchase_orders.LastModifiedDateTime",'>','2023-06-01')
                            ->where("errorFlag", 0)
                            // ->select(["newstystem_store_location_live.erplyID as warehouseID", "newsystem_purchase_order.*","newsystem_product_variation_live.erplyID as productID", "newsystem_product_variation_live.CostPrice as purchasePrice"])
                            ->select(["newstystem_store_location_live.erplyID as warehouseID", "newsystem_purchase_orders.*","newsystem_suppliers.supplierID"])
                            // ->where("PURCHID", 'P059740')
                            ->groupBy("newsystem_purchase_orders.PURCHID")
                            // ->orderBy("created_at","desc")
                            ->limit($limit)
                            ->get();
        // dd($purchaseOrders);
        if(count($purchaseOrders) < 1){
            info("Synccare to Erply : All PO Synced");
            return response("All Purchase Order Synced to Erply");
        }
        // dd($purchaseOrders);
        // dd($purchaseOrders);
        $bulkReq = array();
        $newOrders = array();
        foreach($purchaseOrders as $po){
            $param = array(
                "clientCode" => $this->api->client->clientCode,
                "sessionKey" => $this->api->client->sessionKey,
                "requestName" => "savePurchaseDocument",
                "currencyCode" => "AUD",
                "warehouseID" => $po->warehouseID,
                "no" => $po->PURCHID,
                "type" => "PRCORDER",
                "supplierID" => $po->supplierID, 
                "attributeName1" => "AXPURCHASEID",
                "attributeType1" => "text",
                "attributeValue1" => $po->PURCHID
                // "paid" => 1, 
            );
            // dd($param);
            
            $purchaseDocumentID = $this->getPurchaseOrder($po->PURCHID);
            if($purchaseDocumentID != ''){
                $param["id"] = $purchaseDocumentID;
            }
            

            // echo $isGeneric == true ? "OK" : "ERROR";
            // die;


            //Order Details
            $check = LivePurchaseOrder::where("PURCHID", $po->PURCHID)->where("pendingProcess", 1)->get();
            $countGeneric = 0;
            $countNormal = 0;
            foreach($check as $key => $pline){

                //now checking generic  products
                $isGeneric = false;
                if($pline->CONFIGID == '' || $pline->CONFIGID == '0' || $pline->CONFIGID == 0){
                    $isGeneric = true;
                }

                //if generic than getting data from generic table else variation table

                if($isGeneric == true){
                    $genericTable = LiveProductGenericVariation::where("ITEMID", $pline->ITEMID)->where("ColourID", $pline->INVENTCOLORID)->where("SizeID", $pline->INVENTSIZEID)->first();
                    // dd($genericTable);
                    if($genericTable){
                        $countGeneric = $countGeneric +1;
                        $param["productID".$key+1] = $genericTable->erplyID;
                        $param["vatrateID".$key+1] = 3;
                        $param["amount".$key+1] = (int)$pline->PURCHQTY;
                        $param["price".$key+1] = $genericTable->CostPrice;
                    }
                }

                if($isGeneric == false){
                    $normalTable = LiveProductVariation::where("ERPLYSKU", $pline->ERPLYSKU)->first();
                    if(!$normalTable){
                        $normalTable = LiveProductVariation::where("CONFIGID", $pline->CONFIGID)->where("ITEMID", $pline->ITEMID)->where("ColourID", $pline->INVENTCOLORID)->where("SizeID", $pline->INVENTSIZEID)->first();
                    }
                    if($normalTable){
                        $countNormal = $countNormal + 1;
                        $param["productID".$key+1] = $normalTable->erplyID;
                        $param["vatrateID".$key+1] = 3;
                        $param["amount".$key+1] = (int)$pline->PURCHQTY;
                        $param["price".$key+1] = round($normalTable->CostPrice,2);
                    }
                }

            }

            if(count($check) == ($countGeneric + $countNormal)){
                $bulkReq[] = $param;
                $newOrders[] = $po;
            }else{
                LivePurchaseOrder::where("PURCHID", $po->PURCHID)->update(["errorFlag" => 1]);
            }

            // if($isGeneric == false){
            //     $details = LivePurchaseOrder::join("newsystem_product_variation_live","newsystem_product_variation_live.ERPLYSKU","newsystem_purchase_orders.ERPLYSKU")
            //         	                ->where("newsystem_purchase_orders.PURCHID", $po->PURCHID)
            //                             // ->where("newsystem_purchase_orders.CONFIGID", '<>', '')
            //                             ->where("newsystem_purchase_orders.pendingProcess", 1)
            //                             ->select(["newsystem_purchase_orders.*","newsystem_product_variation_live.erplyID as productID", "newsystem_product_variation_live.CostPrice as purchasePrice"])
            //                             ->get();
            // } 

            // $isGenericFinal = true;

            // if($isGeneric == false){
            //  foreach($details as $key => $line){
            //     $param["productID".$key+1] = $line->productID;
            //     $param["vatrateID".$key+1] = 3;
            //     $param["amount".$key+1] = round($line->PURCHQTY,2);
            //     $param["price".$key+1] = round($line->purchasePrice,2);
            //     // $param["discount".$key+1] = 0
            //  }
            // }else{
            //     foreach($check as $key => $gp){
            //         $genericTable = LiveProductGenericVariation::where("ITEMID", $gp->ITEMID)->where("ColourID", $gp->INVENTCOLORID)->where("SizeID", $gp->INVENTSIZEID)->first();
            //         // dd($genericTable);
            //         if($genericTable){
            //            $param["productID".$key+1] = $genericTable->erplyID;
            //            $param["vatrateID".$key+1] = 3;
            //            $param["amount".$key+1] = (int)$gp->PURCHQTY;
            //            $param["price".$key+1] = $genericTable->CostPrice;
            //         }else{
            //             $isGenericFinal = false;
            //         }
            //     }
            // }

            // if($isGeneric == false){
            //     if(count($check) == count($details)){
            //         $bulkReq[] = $param;
            //         $newOrders[] = $po;
            //     }else{
            //         LivePurchaseOrder::where("PURCHID", $po->PURCHID)->update(["errorFlag" => 1]);
            //     }
            // }

            // if($isGeneric == true){
            //    if($isGenericFinal == true){ 
            //         $bulkReq[] = $param;
            //         $newOrders[] = $po; 
            //    }else{
            //         LivePurchaseOrder::where("PURCHID", $po->PURCHID)->update(["errorFlag" => 1]);
            //    } 
            // }

            
            
            // dd($param);

         }

        if(count($bulkReq) < 1){
            info("All Purchase Order Synced to Erply");
            return response("All Purchase Order Synced to Erply");
        }
        // dd($bulkReq);
        // die;
        $finalBulkReq = $bulkReq;
        $bulkReq = json_encode($bulkReq, true);

        $bulkParam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        $res = $this->api->sendRequest($bulkReq, $bulkParam, 1);

        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($finalBulkReq as $key => $c){
                if($res['requests'][$key]['status']['errorCode'] == 0){

                    LivePurchaseOrder::where("PURCHID", $c["no"])
                    ->update(["purchaseDocumentID" => $res['requests'][$key]['records'][0]['invoiceID'], "pendingProcess" => 0]);

                }else{
                    LivePurchaseOrder::where("PURCHID", $c["no"])
                    ->update(["errorFlag" => 1]);
                    info("ERROR on ".$res['requests'][$key]['status']['errorCode']);
                }

            }
            info("Purchase Order Created or Updated to Erply");
        }

         return response()->json($res);
         
    }


    public function getPurchaseOrder($number){

        $param = array( 
            "sessionKey" => $this->api->client->sessionKey,
            "number" => $number
        );

        $res = $this->api->sendRequest("getPurchaseDocuments", $param);

        if($res["status"]["errorCode"] == 0 && !empty($res['records'])){
            return $res['records'][0]['id'];
        }
        return '';
    }

 
 
}
