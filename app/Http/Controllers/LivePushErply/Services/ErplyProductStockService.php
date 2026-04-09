<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\InventoryRegistration;
use App\Models\PswClientLive\Local\LiveItemByLocation;
use App\Models\PswClientLive\Local\LiveProductGenericVariation;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveTransferOrderLine;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\IsFinalTrait;

class ErplyProductStockService
{
    //
    use IsFinalTrait;
    protected $api;
    // protected $customer;

    public function __construct(EAPIService $api)
    {
        $this->api = $api;
    }

    public function syncStock($req)
    {
        info("Push Stock Old Cron Die................................................................");
        die;
        // $isOk = false;

        // if($req->isFinal){
        //     $isOk = true;
        // }

        // if($isOk == false){
        //     info("Anonymous Stock...");
        //     die;
        // }

        // info("Stock Continues...");
        // die;

        // die;
        // dd($req->warehouse);
        if ($req->warehouse) {
            $warehouses = LiveWarehouseLocation::where("LocationID", $req->warehouse)->limit(1)->first();
        } else {
            $warehouses = LiveWarehouseLocation::where("sohPending", 1)->limit(1)->first();
        }
        // dd($warehouses);

        if (!$warehouses) {
            LiveWarehouseLocation::where("pendingProcess", 0)->update(["sohPending" => 1]);
            info("Synccare to ERPLY : All Product Stock Synced");
            return response("All Warehouse Stock Synceed");
        }


        $bulkStockReq = array();
        // dd($warehouses);

        // foreach($warehouses as $w){
        // if($req->icsc){
        $stocks = LiveProductVariation::join("newsystem_item_by_locations", "newsystem_item_by_locations.ICSC", "newsystem_product_variation_live.ICSC")
            ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_item_by_locations.Warehouse")
            ->where("newsystem_item_by_locations.ICSC", '<>', '')
            // ->where("newsystem_item_by_locations.Configuration",'<>', '')
            ->where("newsystem_product_variation_live.erplyID", '>', 0)
            ->where("newsystem_item_by_locations.sohPending", 1)
            ->whereNull("newsystem_item_by_locations.invRegID")
            ->where("newsystem_item_by_locations.Warehouse", $warehouses->LocationID)
            // ->where("newsystem_item_by_locations.ICSC", $req->icsc)
            ->where("newsystem_product_variation_live.genericProduct", 0)
            ->where("newsystem_item_by_locations.AvailablePhysical", "<>", 0)
            // ->whereNull("newsystem_item_by_locations.invRegError")
            // ->where("newsystem_item_by_locations.ModifiedDateTime",'>','2023-06-01')
            ->select(["newsystem_product_variation_live.erplyID", "newsystem_product_variation_live.RetailSalesPrice as RetailSalesPrice", "newstystem_store_location_live.erplyID as warehouseID", "newsystem_item_by_locations.*"])
            ->groupBy(["newsystem_item_by_locations.Warehouse", "newsystem_item_by_locations.ICSC"])
            ->limit(300)
            // ->toSql();
            ->get();
        // }
        // dd($stocks);
        // else{
        //     $stocks = LiveProductVariation::join("newsystem_item_by_locations", "newsystem_item_by_locations.ICSC", "newsystem_product_variation_live.ICSC")
        //     ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_item_by_locations.Warehouse")
        //     ->where("newsystem_item_by_locations.ICSC",'<>', '')
        //     // ->where("newsystem_item_by_locations.Configuration",'<>', '')
        //     ->where("newsystem_product_variation_live.erplyID",'>', 0)
        //     ->where("newsystem_item_by_locations.sohPending", 1)
        //     ->whereNull("newsystem_item_by_locations.invRegID")
        //     ->where("newsystem_item_by_locations.Warehouse", $warehouses->LocationID) 
        //     ->where("newsystem_item_by_locations.AvailablePhysical", ">", 0)
        //     // ->whereNull("newsystem_item_by_locations.invRegError")
        //     // ->where("newsystem_item_by_locations.ModifiedDateTime",'>','2023-06-01')
        //     ->select(["newsystem_product_variation_live.erplyID","newsystem_product_variation_live.RetailSalesPrice as RetailSalesPrice","newstystem_store_location_live.erplyID as warehouseID","newsystem_item_by_locations.*"])
        //     ->groupBy(["newsystem_item_by_locations.Warehouse", "newsystem_item_by_locations.ICSC"])
        //     ->limit(300)
        //     // ->toSql();
        //     ->get();
        //     // dd($stocks);
        // }
        // dd($stocks);
        if ($stocks->isEmpty()) {
            // echo " hi sir";
            // die;
            $stocks = LiveProductGenericVariation::join("newsystem_item_by_locations", "newsystem_item_by_locations.ICSC", "newsystem_product_generic_variation_live.ICSC")
                ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_item_by_locations.Warehouse")
                ->where("newsystem_item_by_locations.Configuration", 0)
                // ->where("newsystem_product_variation_live.ERPLYFLAG",'<>', '')
                ->where("newsystem_product_generic_variation_live.erplyID", '>', 0)
                ->where("newsystem_item_by_locations.sohPending", 1)
                ->whereNull("newsystem_item_by_locations.invRegID")
                ->where("newsystem_item_by_locations.Warehouse", $warehouses->LocationID)
                ->where("newsystem_item_by_locations.AvailablePhysical", ">", 0)
                // ->where("newsystem_item_by_locations.ModifiedDateTime",'>','2023-06-01')
                // ->whereNull("newsystem_item_by_locations.invRegError")
                ->select(["newsystem_product_generic_variation_live.erplyID", "newsystem_product_generic_variation_live.RetailSalesPrice as RetailSalesPrice", "newstystem_store_location_live.erplyID as warehouseID", "newsystem_item_by_locations.*"])
                ->groupBy(["newsystem_item_by_locations.Warehouse", "newsystem_item_by_locations.ICSC"])
                ->limit(300)
                // ->toSql();
                ->get();
        }


        // die;
        if ($stocks->isEmpty()) {
            LiveWarehouseLocation::where("id", $warehouses->id)->update(["sohPending" => 0]);
            info("Synccare to Erply : " . $warehouses->LocationID . " All Stock Syncced");
            return response("All Stock Synced");
        }
        // dd($stocks);
        // dd($stocks);
        // $param["warehouseID"] = $warehouses->erplyID;
        $stockChunk = $stocks->chunk(100);
        foreach ($stockChunk as $sc) {

            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "saveInventoryRegistration",
                "warehouseID" => $warehouses->erplyID,
                // "productID1" => $stock->productID,
                // "amount1" => $stock->AvailablePhysical,
                // "price1" => $stock->RetailSalesPrice,
            );
            $count = 1;
            foreach ($sc as $key => $stock) {

                $param["productID" . $count] = $stock->erplyID;
                $param["amount" . $count] = $stock->AvailablePhysical;
                $param["price" . $count] = $stock->RetailSalesPrice;
                $count++;
                // $invRegID = $this->getInventoryRegistration($stock->warehouseID, $stock->productID, $stock->invRegID);
                // if($invRegID != ''){
                //     $param["inventoryRegistrationID"] = $invRegID;
                // } 
            }
            // $count = 1;
            $bulkStockReq[] = $param;
        }
        // $bulkStockReq[] = $param;
        // }
        // dd($bulkStockReq);

        if (count($bulkStockReq) < 1) {
            info("All Stock Synced");
            return response("All Stock Synced");
        }

        $bulkP = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey
        );

        $bulkStockReq = json_encode($bulkStockReq, true);
        $res = $this->api->sendRequest($bulkStockReq, $bulkP, 1);

        // if($res["status"]["errorCode"] == 0){
        //     foreach($stocks as $s){
        //         if($res["requests"])
        //         LiveItemByLocation::where("id", $s->id)->update(["sohPending" => 0]);
        //     }
        // }
        if ($res['status']['errorCode'] == 0 && !empty($res['requests'])) {
            foreach ($stockChunk as $key => $sc) {
                if ($res['requests'][$key]['status']['errorCode'] == 0) {
                    foreach ($sc as $c) {
                        // if($res['requests'][$key]['status']['errorCode'] == 0){
                        LiveItemByLocation::where("id", $c->id)->update(["sohPending" => 0, "invRegID" => $res['requests'][$key]['records'][0]['inventoryRegistrationID']]);
                        // }else{
                        //     LiveItemByLocation::where("id", $c->id)->update(["invRegError" => $res['requests'][$key]['status']['errorCode']]);
                        // }
                    }
                }
            }
            info("Inventory Reg Created or Updated to Erply");
        }

        // return response()->json(["status" => "success", "response" => $bulkRes]);

        return response()->json($res);
        // dd($param); 
    }

    public function updateSOH($req)
    {
        return $this->updateSOHv5($req);
        die;

        //we are updating all stock at once
        $warehouses = LiveWarehouseLocation::where("erplyID", '>', 0)->where("pendingProcess", 0)->where("ENTITY", $this->api->client->ENTITY)->where('sohUpdate', 1)->limit(10)->get();
        // dd($warehouses);
        if ($warehouses->isEmpty()) {
            info("All Stock Updated to Erply. and Again sohUpdate flag updated to 1");
            LiveWarehouseLocation::where("erplyID", '>', 0)->where("ENTITY", $this->api->client->ENTITY)->update(["sohUpdate" => 1]);
            return response("All Stock Updated to Erply. and Again sohUpdate flag updated to 1");
            die;
        }
        // dd($warehouses);
        $bulkGetStock = array();
        $reqWarehouses = array();
        $reqProducts = array();

        $wkey = 0;
        foreach ($warehouses as $wh) {

            /*
             *  
             * FOR UPDATING OR CREATING PRODUCT STOCK OF NON-GENERIC PRODUCT  
             * 
             */

            $stocks =  LiveProductVariation::join("newsystem_item_by_locations", "newsystem_item_by_locations.ICSC", "newsystem_product_variation_live.ICSC")
                ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_item_by_locations.Warehouse")
                ->where("newsystem_item_by_locations.ICSC", '<>', '')
                ->where("newstystem_store_location_live.erplyID", '>', 0)
                ->whereNotIn("newsystem_item_by_locations.Configuration", ['', '0'])
                ->where("newsystem_product_variation_live.erplyID", '>', 0)
                ->where("newsystem_item_by_locations.sohPending", 1)
                // ->where("newsystem_item_by_locations.invRegID", '>', 0)
                ->where("newsystem_product_variation_live.genericProduct", 0)
                ->where("newsystem_item_by_locations.Warehouse", $wh->LocationID)
                ->where("newsystem_item_by_locations.AvailablePhysical", "<>", 0)
                // ->whereNull("newsystem_item_by_locations.invRegError")
                // ->where("newsystem_item_by_locations.ModifiedDateTime",'>','2023-06-01')
                ->select(["newsystem_product_variation_live.erplyID", "newsystem_product_variation_live.RetailSalesPrice as RetailSalesPrice", "newstystem_store_location_live.erplyID as warehouseID", "newsystem_item_by_locations.*"])
                ->groupBy(["newsystem_item_by_locations.Warehouse", "newsystem_item_by_locations.ICSC"])
                ->limit(10)
                // ->toSql();
                ->get();

            /*
             * IF EMPTY NON-GENERIC PRODUCT 
             * FOR UPDATING OR CREATING PRODUCT STOCK OF GENERIC PRODUCT  
             * 
             */
            if ($stocks->isEmpty()) {
                $stocks = LiveProductGenericVariation::join("newsystem_item_by_locations", "newsystem_item_by_locations.ICSC", "newsystem_product_generic_variation_live.ICSC")
                    ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_item_by_locations.Warehouse")
                    ->whereIn("newsystem_item_by_locations.Configuration", ['0', ''])
                    // ->where("newsystem_product_variation_live.ERPLYFLAG",'<>', '')
                    ->where("newsystem_product_generic_variation_live.erplyID", '>', 0)
                    ->where("newsystem_item_by_locations.sohPending", 1)
                    // ->where("newsystem_item_by_locations.invRegID",'>',0)
                    ->where("newsystem_item_by_locations.Warehouse", $wh->LocationID)
                    ->where("newsystem_item_by_locations.AvailablePhysical", "<>", 0)
                    // ->where("newsystem_item_by_locations.ModifiedDateTime",'>','2023-06-01')
                    // ->whereNull("newsystem_item_by_locations.invRegError")
                    ->select(["newsystem_product_generic_variation_live.erplyID", "newsystem_product_generic_variation_live.RetailSalesPrice as RetailSalesPrice", "newstystem_store_location_live.erplyID as warehouseID", "newsystem_item_by_locations.*"])
                    ->groupBy(["newsystem_item_by_locations.Warehouse", "newsystem_item_by_locations.ICSC"])
                    ->limit(10)
                    // ->toSql();
                    ->get();
            }

            // dd($stocks);

            if ($stocks->isEmpty()) {
                LiveWarehouseLocation::where("id", $wh->id)->update(["sohUpdate" => 0]);
                info("Synccare to Erply : " . $wh->LocationID . "All Stock Updated" . $this->api->client->ENTITY);
                // return response("All Stock Synced");
            }

            //if exist then make bulk get request
            if ($stocks->isNotEmpty()) {
                // dd($stocks);
                $getParam = array(
                    "requestName" => "getProductStock",
                    "clientCode" => $this->api->client->clientCode,
                    "sessionKey" => $this->api->client->sessionKey,
                    "warehouseID" => $wh->erplyID,
                    "status" => "ALL_EXCEPT_ARCHIVED"
                );

                $Pids = '';
                foreach ($stocks as $su) {

                    if ($stocks->last() == $su) {
                        $Pids .= $su->erplyID;
                    } else {
                        $Pids .= $su->erplyID . ',';
                    }
                }
                $getParam["productIDs"] = $Pids;
                $reqWarehouses[] = $wh;
                $reqProducts[$wkey] = $stocks;
                $bulkGetStock[] = $getParam;

                $wkey++;
            }
        }

        //now getting current stock of all products using bulk req
        // dd($bulkGetStock);
        // dd($reqProducts);


        if (count($bulkGetStock) < 1) {
            info("Synccare to Erply : All SOH Updated to Erply");
            return response("Synccare to Erply : All SOH Updated to Erply");
            // die;
        }

        $bulkGetStock = json_encode($bulkGetStock, true);

        $bulkParam  = array(
            "sessionKey" => $this->api->client->sessionKey
        );
        // dd($reqProducts);
        $bulkGetStockRes = $this->api->sendRequest($bulkGetStock, $bulkParam, 1);

        dd($bulkGetStockRes);
        $bulkSohUpdateReq = array();
        $bulkSohWarehouseReq = array();
        $bulkSohUpdateProducts = array();
        // $bulkSohInvRegProduct = array();
        // dd($reqWarehouses);

        $reasonID = 0;
        if (env("isLive") == true) {
            $reasonID = 5;
        }
        if (env("isLive") == false) {
            $reasonID = 58;
        }



        if ($bulkGetStockRes["status"]["errorCode"] == 0) {

            foreach ($reqWarehouses as $key => $wh) {
                // echo $key;
                // die;
                //synccare products 
                //for write off
                $writeOffReq = array(
                    "requestName" => "saveInventoryWriteOff",
                    "warehouseID" => $wh->erplyID,
                    "reasonID" => $reasonID, //env('isLive') == true ? 5 : 58,
                );

                //for save inventory registration
                $invRegReq = array(
                    "requestName" => "saveInventoryRegistration",
                    "warehouseID" => $wh->erplyID
                );

                $wFlag = false;
                $rFlag = false;

                // $warehouseWiseProduct = array();

                $countWriteOff = 1;
                $countSaveInv = 1;

                $warehouseWiseProducts = array();
                // $warehouseWiseSohInvReg = array();
                foreach ($reqProducts[$key] as $pkey => $synncStock) {
                    // dd($products);
                    //now comparing products
                    // dd($products);



                    // foreach($products as $pkey => $synncStock){
                    // dd($synncStock);
                    // die;
                    //  dd($bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock']);
                    //  die;
                    if ($bulkGetStockRes['requests'][$key]['status']['errorCode'] == 0) {

                        //if records empty then do inventory regisration
                        if (empty($bulkGetStockRes['requests'][$key]['records'])) {
                            $rFlag = true;
                            // echo (double)$synncStock->AvailablePhysical.' '. (double)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'];
                            // die;
                            $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                            $invRegReq["amount" . $countSaveInv] = (float)$synncStock->AvailablePhysical;
                            $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                            $countSaveInv++;
                            $warehouseWiseProducts[] = $synncStock;
                        } else {

                            if ((float)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] > (float)$synncStock->AvailablePhysical) {
                                $writeOffReq["productID" . $countWriteOff] = $synncStock->erplyID;
                                $writeOffReq["amount" . $countWriteOff] = (float)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] - (float)$synncStock->AvailablePhysical;
                                $writeOffReq["price" . $countWriteOff] = $synncStock->RetailSalesPrice;
                                $countWriteOff++;
                                $wFlag = true;
                                $warehouseWiseProducts[] = $synncStock;
                            }

                            if ((float)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] < (float)$synncStock->AvailablePhysical) {
                                $rFlag = true;
                                // echo (double)$synncStock->AvailablePhysical.' '. (double)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'];
                                // die;
                                $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                                $invRegReq["amount" . $countSaveInv] = (float)$synncStock->AvailablePhysical - (float)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'];
                                $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                                $countSaveInv++;
                                $warehouseWiseProducts[] = $synncStock;
                            } else {

                                LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                            }
                        }
                    }
                    // else{
                    //     info("Update SOH ****************".$bulkGetStockRes['requests'][$key]['status']['errorCode']."********************************");
                    // }



                    // }

                }

                if ($wFlag == true) {
                    $bulkSohUpdateReq[] = $writeOffReq;
                    $bulkSohWarehouseReq[] = $wh;
                    // $bulkSohWriteProduct[] = $warehouseWiseSohWriteOff;
                }

                if ($rFlag == true) {
                    $bulkSohUpdateReq[] = $invRegReq; // for erply
                    $bulkSohWarehouseReq[] = $wh;   //for local 
                    // $bulkSohWriteProduct[] = $warehouseWiseSohWriteOff; //for local

                }

                if ($rFlag == true || $wFlag == true) {
                    $bulkSohUpdateProducts[$wh->erplyID] = $warehouseWiseProducts;
                }
            }
        } else {

            info("Synccare to AX : Error while getting product stock");
            return response("Synccare to AX : Error while getting product stock");
            die;
        }


        // dd($bulkSohUpdateReq);
        $erplyReqCopy = $bulkSohUpdateReq;
        // dd($bulkSohWarehouseReq);

        //now sending soh registration and write off bulk request
        if (count($bulkSohUpdateReq) < 1) {
            info("Synccare to Erply : All SOH Updated");
            return response("Synccare to Erply : All SOH Updated");
        }


        $bulkSohUpdateReq = json_encode($bulkSohUpdateReq, true);

        $bulkParam  = array(
            "sessionKey" => $this->api->client->sessionKey
        );

        $bulkSohUpdateRes = $this->api->sendRequest($bulkSohUpdateReq, $bulkParam, 1);

        if ($bulkSohUpdateRes["status"]["errorCode"] == 0 && !empty($bulkSohUpdateRes["requests"])) {
            foreach ($erplyReqCopy as $key => $finalWH) {
                $productRequested = [];
                foreach ($finalWH as $rkey => $rd) {
                    if (str_contains($rkey, 'productID')) {
                        $productRequested[] = $rd;
                    }
                }
                if ($bulkSohUpdateRes["requests"][$key]["status"]["errorCode"] == 0) {

                    foreach ($productRequested as $rpp) {

                        foreach ($bulkSohUpdateProducts[$finalWH["warehouseID"]] as $products) {

                            //for write off and inv reg just update sohPending = 1
                            if ($rpp == $products["erplyID"]) {

                                //just update soh pedning
                                LiveItemByLocation::where("id", $products["id"])->update(["sohPending" => 0]); //, "invRegID" => $res['requests'][$key]['records'][0]['inventoryRegistrationID'] ]);

                            }
                        }
                    }
                }
            }

            info("Synccare to Erply : Product Stock Updated Successfully.");
        }

        return response()->json($bulkSohUpdateRes);
    }

    public function updateSOHv2()
    {
        // return $this->updateSOHv3();
        // die;
        // info("**********************************************************SOH update cron called*********************************************************************");

        //we are updating all stock at once
        $warehouses = LiveWarehouseLocation::where("erplyID", '>', 0)->where("pendingProcess", 0)->where("ENTITY", $this->api->client->ENTITY)->where('sohUpdate', 1)->limit(3)->get();
        // dd($warehouses);
        if ($warehouses->isEmpty()) {
            info("All Stock Updated to Erply. and Again sohUpdate flag updated to 1");
            LiveWarehouseLocation::where("erplyID", '>', 0)->where("ENTITY", $this->api->client->ENTITY)->update(["sohUpdate" => 1]);
            return response("All Stock Updated to Erply. and Again sohUpdate flag updated to 1");
            die;
        }
        // dd($warehouses);
        $bulkGetStock = array();
        $reqWarehouses = array();
        $reqProducts = array();

        $wkey = 0;
        foreach ($warehouses as $wh) {

            /*
             *  
             * FOR UPDATING OR CREATING PRODUCT STOCK OF NON-GENERIC PRODUCT  
             * 
             */
            // dd($wh);
            $stocks =  LiveProductVariation::join("newsystem_item_by_locations", "newsystem_item_by_locations.ICSC", "newsystem_product_variation_live.ICSC")
                ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_item_by_locations.Warehouse")
                ->where("newsystem_item_by_locations.ICSC", '<>', '')
                ->where("newstystem_store_location_live.erplyID", '>', 0)
                ->whereNotIn("newsystem_item_by_locations.Configuration", ['', '0'])
                ->where("newsystem_product_variation_live.erplyID", '>', 0)
                ->where("newsystem_item_by_locations.sohPending", 1)
                // ->where("newsystem_item_by_locations.invRegID", '>', 0)
                ->where("newsystem_product_variation_live.genericProduct", 0)
                ->where("newsystem_item_by_locations.Warehouse", $wh->LocationID)
                // ->where("newsystem_item_by_locations.AvailablePhysical", "<>", 0)
                // ->whereNull("newsystem_item_by_locations.invRegError")
                // ->where("newsystem_item_by_locations.ModifiedDateTime",'>','2023-06-01')
                ->select(["newsystem_product_variation_live.erplyID", "newsystem_product_variation_live.RetailSalesPrice as RetailSalesPrice", "newstystem_store_location_live.erplyID as warehouseID", "newsystem_item_by_locations.*"])
                ->groupBy(["newsystem_item_by_locations.Warehouse", "newsystem_item_by_locations.ICSC"])
                ->limit(80)
                // ->toSql();
                ->get();
            // dd($stocks);
            /*
             * IF EMPTY NON-GENERIC PRODUCT 
             * FOR UPDATING OR CREATING PRODUCT STOCK OF GENERIC PRODUCT  
             * 
             */
            if ($stocks->isEmpty()) {
                $stocks = LiveProductGenericVariation::join("newsystem_item_by_locations", "newsystem_item_by_locations.ICSC", "newsystem_product_generic_variation_live.ICSC")
                    ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_item_by_locations.Warehouse")
                    ->whereIn("newsystem_item_by_locations.Configuration", ['0', ''])
                    // ->where("newsystem_product_variation_live.ERPLYFLAG",'<>', '')
                    ->where("newsystem_product_generic_variation_live.erplyID", '>', 0)
                    ->where("newsystem_item_by_locations.sohPending", 1)
                    // ->where("newsystem_item_by_locations.invRegID",'>',0)
                    ->where("newsystem_item_by_locations.Warehouse", $wh->LocationID)
                    ->where("newsystem_item_by_locations.AvailablePhysical", "<>", 0)
                    // ->where("newsystem_item_by_locations.ModifiedDateTime",'>','2023-06-01')
                    // ->whereNull("newsystem_item_by_locations.invRegError")
                    ->select(["newsystem_product_generic_variation_live.erplyID", "newsystem_product_generic_variation_live.RetailSalesPrice as RetailSalesPrice", "newstystem_store_location_live.erplyID as warehouseID", "newsystem_item_by_locations.*"])
                    ->groupBy(["newsystem_item_by_locations.Warehouse", "newsystem_item_by_locations.ICSC"])
                    ->limit(80)
                    // ->toSql();
                    ->get();
            }

            // dd($stocks);

            if ($stocks->isEmpty()) {
                LiveWarehouseLocation::where("id", $wh->id)->update(["sohUpdate" => 0]);
                info("Synccare to Erply : " . $wh->LocationID . "All Stock Updated" . $this->api->client->ENTITY);
                // return response("All Stock Synced");
            }

            //if exist then make bulk get request
            if ($stocks->isNotEmpty()) {
                // dd($stocks);
                // dd($stocks);
                $getParam = array(
                    "requestName" => "getProductStock",
                    "clientCode" => $this->api->client->clientCode,
                    "sessionKey" => $this->api->client->sessionKey,
                    "warehouseID" => $wh->erplyID,
                    "status" => "ALL_EXCEPT_ARCHIVED"
                );

                $Pids = '';
                foreach ($stocks as $su) {

                    if ($stocks->last() == $su) {
                        $Pids .= $su->erplyID;
                    } else {
                        $Pids .= $su->erplyID . ',';
                    }
                }
                $getParam["productIDs"] = $Pids;
                $reqWarehouses[] = $wh;
                $reqProducts[$wkey] = $stocks;
                $bulkGetStock[] = $getParam;

                $wkey++;
            }
        }

        //now getting current stock of all products using bulk req
        // dd($bulkGetStock);
        // dd($reqProducts);


        if (count($bulkGetStock) < 1) {
            info("*************************************Synccare to Erply : All SOH Updated to Erply********************************************");
            return response("Synccare to Erply : All SOH Updated to Erply");
            // die;
        }

        $bulkGetStock = json_encode($bulkGetStock, true);

        $bulkParam  = array(
            "sessionKey" => $this->api->client->sessionKey
        );
        // dd($reqProducts);
        $bulkGetStockRes = $this->api->sendRequest($bulkGetStock, $bulkParam, 1);

        // dd($bulkGetStockRes);
        $bulkSohUpdateReq = array();
        $bulkSohWarehouseReq = array();
        $bulkSohUpdateProducts = array();
        // $bulkSohInvRegProduct = array();
        // dd($reqWarehouses);

        $reasonID = 0;
        if (env("isLive") == true) {
            $reasonID = 5;
        }
        if (env("isLive") == false) {
            $reasonID = 58;
        }



        if ($bulkGetStockRes["status"]["errorCode"] == 0) {

            foreach ($reqWarehouses as $key => $wh) {
                // echo $key;
                // die;
                //synccare products 
                //for write off
                $writeOffReq = array(
                    "requestName" => "saveInventoryWriteOff",
                    "warehouseID" => $wh->erplyID,
                    "reasonID" => $reasonID, //env('isLive') == true ? 5 : 58,
                );

                //for save inventory registration
                $invRegReq = array(
                    "requestName" => "saveInventoryRegistration",
                    "warehouseID" => $wh->erplyID
                );

                $wFlag = false;
                $rFlag = false;

                // $warehouseWiseProduct = array();

                $countWriteOff = 1;
                $countSaveInv = 1;

                $warehouseWiseProducts = array();
                // $warehouseWiseSohInvReg = array();
                foreach ($reqProducts[$key] as $pkey => $synncStock) {
                    // dd($synncStock);
                    // dd($products);
                    //now comparing products
                    // dd($products);

                    // foreach($products as $pkey => $synncStock){
                    // dd($synncStock);
                    // die;
                    //  dd($bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock']);
                    //  die;
                    if ($bulkGetStockRes['requests'][$key]['status']['errorCode'] == 0) {

                        //if records empty then do inventory regisration
                        if (empty($bulkGetStockRes['requests'][$key]['records'])) {

                            // echo (double)$synncStock->AvailablePhysical.' '. (double)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'];
                            // die;

                            //check if soh is 0 then no need to register inventory 
                            if ((float)$synncStock->AvailablePhysical != 0) {
                                $rFlag = true;
                                $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                                $invRegReq["amount" . $countSaveInv] = (float)$synncStock->AvailablePhysical;
                                $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                                $countSaveInv++;
                                $warehouseWiseProducts[] = $synncStock;
                            } else {
                                //soh not exist in erply and soh 0 in syn
                                LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                            }
                        } else {

                            // dd($bulkGetStockRes['requests'][$key]['records']);
                            if (count($bulkGetStockRes["requests"][$key]["records"]) == count($reqProducts[$key])) {
                                info("All product stock exist in erply");
                                // die;
                                //all product stock exist in erply 
                                if ((float)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] > (float)$synncStock->AvailablePhysical) {
                                    $writeOffReq["productID" . $countWriteOff] = $synncStock->erplyID;
                                    $writeOffReq["amount" . $countWriteOff] = (float)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] - (float)$synncStock->AvailablePhysical;
                                    $writeOffReq["price" . $countWriteOff] = $synncStock->RetailSalesPrice;
                                    $countWriteOff++;
                                    $wFlag = true;
                                    $warehouseWiseProducts[] = $synncStock;
                                }

                                if ((float)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] < (float)$synncStock->AvailablePhysical) {

                                    $rFlag = true;
                                    $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                                    $invRegReq["amount" . $countSaveInv] = (float)$synncStock->AvailablePhysical - (float)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'];
                                    $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                                    $countSaveInv++;
                                    $warehouseWiseProducts[] = $synncStock;
                                } else {

                                    LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                                }
                            } else {
                                //all product stock doesn't exist in erply so check product stock manually
                                info("All product stock doesn't exist in erply");
                                // $isStockExist = 0;
                                $checkStockExist = $this->checkStockExistInResponse($bulkGetStockRes['requests'][$key]['records'], $synncStock->erplyID);
                                // $isStockExist = $checkStockExist["isExist"];
                                if ($checkStockExist["isExist"] == 0) {

                                    if ((float)$synncStock->AvailablePhysical != 0) {
                                        $rFlag = true;
                                        $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                                        $invRegReq["amount" . $countSaveInv] = (float)$synncStock->AvailablePhysical;
                                        $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                                        $countSaveInv++;
                                        $warehouseWiseProducts[] = $synncStock;
                                    } else {
                                        //soh not exist in erply and soh 0 in syn
                                        LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                                    }
                                }

                                if ($checkStockExist["isExist"] == 1) {

                                    if ((float)$checkStockExist["erplyStock"] > (float)$synncStock->AvailablePhysical) {
                                        $writeOffReq["productID" . $countWriteOff] = $synncStock->erplyID;
                                        $writeOffReq["amount" . $countWriteOff] = (float)$checkStockExist["erplyStock"] - (float)$synncStock->AvailablePhysical;
                                        $writeOffReq["price" . $countWriteOff] = $synncStock->RetailSalesPrice;
                                        $countWriteOff++;
                                        $wFlag = true;
                                        $warehouseWiseProducts[] = $synncStock;
                                    }

                                    if ((float)$checkStockExist["erplyStock"] < (float)$synncStock->AvailablePhysical) {

                                        $rFlag = true;
                                        $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                                        $invRegReq["amount" . $countSaveInv] = (float)$synncStock->AvailablePhysical - (float)$checkStockExist["erplyStock"];
                                        $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                                        $countSaveInv++;
                                        $warehouseWiseProducts[] = $synncStock;
                                    } else {

                                        LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                                    }
                                }
                                // die;
                            }
                        }
                    }
                    // else{
                    //     info("Update SOH ****************".$bulkGetStockRes['requests'][$key]['status']['errorCode']."********************************");
                    // }



                    // }

                }

                if ($wFlag == true) {
                    $bulkSohUpdateReq[] = $writeOffReq;
                    $bulkSohWarehouseReq[] = $wh;
                    // $bulkSohWriteProduct[] = $warehouseWiseSohWriteOff;
                }

                if ($rFlag == true) {
                    $bulkSohUpdateReq[] = $invRegReq; // for erply
                    $bulkSohWarehouseReq[] = $wh;   //for local 
                    // $bulkSohWriteProduct[] = $warehouseWiseSohWriteOff; //for local

                }

                if ($rFlag == true || $wFlag == true) {
                    $bulkSohUpdateProducts[$wh->erplyID] = $warehouseWiseProducts;
                }
            }
        } else {

            info("Synccare to AX : Error while getting product stock");
            return response("Synccare to AX : Error while getting product stock");
            die;
        }


        // dd($bulkSohUpdateReq);
        $erplyReqCopy = $bulkSohUpdateReq;
        // dd($bulkSohWarehouseReq);

        //now sending soh registration and write off bulk request
        if (count($bulkSohUpdateReq) < 1) {
            info("Synccare to Erply : All SOH Updated");

            return response("Synccare to Erply : All SOH Updated");
        }


        $bulkSohUpdateReq = json_encode($bulkSohUpdateReq, true);

        $bulkParam  = array(
            "sessionKey" => $this->api->client->sessionKey
        );

        $bulkSohUpdateRes = $this->api->sendRequest($bulkSohUpdateReq, $bulkParam, 1);

        if ($bulkSohUpdateRes["status"]["errorCode"] == 0 && !empty($bulkSohUpdateRes["requests"])) {
            foreach ($erplyReqCopy as $key => $finalWH) {
                $productRequested = [];
                foreach ($finalWH as $rkey => $rd) {
                    if (str_contains($rkey, 'productID')) {
                        $productRequested[] = $rd;
                    }
                }
                if ($bulkSohUpdateRes["requests"][$key]["status"]["errorCode"] == 0) {

                    foreach ($productRequested as $rpp) {

                        foreach ($bulkSohUpdateProducts[$finalWH["warehouseID"]] as $products) {

                            //for write off and inv reg just update sohPending = 1
                            if ($rpp == $products["erplyID"]) {

                                //just update soh pedning
                                LiveItemByLocation::where("id", $products["id"])->update(["sohPending" => 0]); //, "invRegID" => $res['requests'][$key]['records'][0]['inventoryRegistrationID'] ]);

                            }
                        }
                    }
                }
            }

            info("*************************************************Synccare to Erply : Product Stock Updating..................................................................");
        }

        return response()->json($bulkSohUpdateRes);
    }
 
   
    public function updateSOHv5($req)
    {

        // $isFinal = 0;
        // if($req->isfinal){
        //     $isFinal = 1;
        // }

        // if($isFinal == 0){
        //     info("SOH Update Cron Die");
        //     die;
        // }


        $limit = $req->limit ? $req->limit : 95;
        $isDebug = '';
        if($req->debug){
            $isDebug = $req->debug;
        }
        /**
         * Simple Logic
         * first getting product whose sohPending is 1
         * second getting stock from erply 
         * third checking erply stock and ax stock
         * fourch make inventory reg and writeoff according to qty
         * last update soh to erply
         */
        // return $this->updateSOHv3();
        // die;
        // info("**********************************************************SOH update cron called*********************************************************************");
        // dd($this->api->client->ENTITY);
        //we are updating all stock at once
        $warehouses = LiveWarehouseLocation::where("erplyID", '>', 0)->where("pendingProcess", 0)->where("ENTITY", $this->api->client->ENTITY)->where('sohUpdate', 1)->limit(4)->get();
        // LiveWarehouseLocation::where("erplyID", '>', 0)->where("ENTITY", $this->api->client->ENTITY)->update(["sohUpdate" => 0]);
        // dd($warehouses);
        // dd($warehouses);
        if ($warehouses->isEmpty()) {
            // info("All Stock Updated to Erply. and Again sohUpdate flag updated to 1");
            LiveWarehouseLocation::where("erplyID", '>', 0)->where("ENTITY", $this->api->client->ENTITY)->update(["sohUpdate" => 1]);
            return response("All Stock Updated to Erply. and Again sohUpdate flag updated to 1");
            die;
        }
        
        $bulkGetStock = array();
        $reqWarehouses = array();
        $reqProducts = array();

        $wkey = 0;
        foreach ($warehouses as $wh) {

            /*
             *  
             * FOR UPDATING OR CREATING PRODUCT STOCK OF NON-GENERIC PRODUCT  
             * 
             */
            // dd($wh);
            //first getting pending soh // for non-generic products
            $pendingSoh = LiveItemByLocation::where("sohPending", 1)
                            ->where("Warehouse", $wh->LocationID)
                            ->whereNotIn("newsystem_item_by_locations.Configuration", ['', '0'])
                            // ->where("id", 278643)
                            ->limit($limit)
                            ->get();
            $isGeneric = 0;
            if($pendingSoh->isEmpty()){
                //if soh pending empty then we are pushing soh of generic product
                $isGeneric = 1;
                $pendingSoh = LiveItemByLocation::where("sohPending", 1)
                            ->where("Warehouse", $wh->LocationID)
                            ->whereIn("newsystem_item_by_locations.Configuration", ['', '0'])
                            // ->where("id", 278643)
                            ->limit($limit)
                            ->get();
                info("********************************************************** Generic SOH Updating *********************************************************************");
            }

            // if($isDebug == 12){
            // dd($pendingSoh);
            // }

            // dd($pendingSoh);
            // die;
            $stocks = [];
            $maxProduct = 95;
            $actualProduct = 0;
            foreach($pendingSoh as $soh){


                $products =  LiveProductVariation::
                // ->where("newsystem_item_by_locations.ICSC", '<>', '')
                // ->where("newstystem_store_location_live.erplyID", '>', 0)
                whereNotIn("newsystem_product_variation_live.CONFIGID", ['', '0'])
                ->where("newsystem_product_variation_live.erplyID", '>', 0)
                ->where("ICSC", $soh->ICSC)
                // ->where("newsystem_item_by_locations.sohPending", 1)
                // ->where("newsystem_item_by_locations.invRegID", '>', 0)
                ->where("newsystem_product_variation_live.genericProduct", 0)
                ->select(["erplyID","ICSC","RetailSalesPrice"])
                ->get();

                if($isGeneric == 1){

                    $products =  LiveProductGenericVariation::
                    where("erplyID", '>', 0)
                    ->where("ICSC", $soh->ICSC)
                    ->select(["erplyID","ICSC","RetailSalesPrice"])
                    ->get();
                }

                
                $actualProduct = $actualProduct + count($products);
                if($actualProduct <= $maxProduct){
                    foreach($products as $p){
                        // dd($soh);
                        $p["AvailablePhysical"] = $soh->AvailablePhysical;
                        $p["id"] = $soh->id;
                        $stocks[] = $p;
                    }
                } 
            }

            // dd($stocks);
            // dd($stockUpdateProduct);
             

            if (count($stocks) < 1) {
                LiveWarehouseLocation::where("id", $wh->id)->update(["sohUpdate" => 0]);
                // info("Synccare to Erply : " . $wh->LocationID . "All Stock Updated" . $this->api->client->ENTITY);
                // return response("All Stock Synced");
            }

            //if exist then make bulk get request
            if (count($stocks) > 0) {
                // dd($stocks);
                // dd($stocks);
                $getParam = array(
                    "requestName" => "getProductStock",
                    "clientCode" => $this->api->client->clientCode,
                    "sessionKey" => $this->api->client->sessionKey,
                    "warehouseID" => $wh->erplyID,
                    // "status" => "ALL_EXCEPT_ARCHIVED"
                );

                $Pids = '';
                foreach ($stocks as $su) {
                    // dd($su);
                    // if ($stocks->last() == $su) {
                    //     $Pids .= $su->erplyID;
                    // } else {
                    $Pids .= $su["erplyID"] . ',';
                    // }
                }
                $chk = substr($Pids, -1);
                if($chk == ","){
                    $Pids = substr($Pids, 0, -1);
                }
                // dd($Pids);

                $getParam["productIDs"] = $Pids;
                $reqWarehouses[] = $wh;
                $reqProducts[$wkey] = $stocks;
                $bulkGetStock[] = $getParam;

                $wkey++;
            }

            // dd("end");
        }

        //now getting current stock of all products using bulk req
        // dd($bulkGetStock);
        // dd($reqProducts);
        if($isDebug == 1){
            dd($bulkGetStock);
        }

        if (count($bulkGetStock) < 1) {
            // info("*************************************Synccare to Erply : All SOH Updated to Erply********************************************");
            return response("Synccare to Erply : All SOH Updated to Erply");
            // die;
        }

        $bulkGetStock = json_encode($bulkGetStock, true);

        $bulkParam  = array(
            "sessionKey" => $this->api->client->sessionKey
        );
        // dd($reqProducts);
        $bulkGetStockRes = $this->api->sendRequest($bulkGetStock, $bulkParam, 1);

        if($isDebug == 11){
            dd($bulkGetStockRes);
        }
        // dd($bulkGetStockRes);
        $bulkSohUpdateReq = array();
        $bulkSohWarehouseReq = array();
        $bulkSohUpdateProducts = array();
        // $bulkSohInvRegProduct = array();
        // dd($reqWarehouses);
        // info($bulkGetStockRes);
        // dd($this->api->client->ENTITY);
        $reasonID = 0;
        if (env("isLive") == true) {
            $reasonID = 5;
            
            if($this->api->client->ENTITY == "PSW"){
                $reasonID = 35;
            }
        }
        if (env("isLive") == false) {
            $reasonID = 58;
        }



        if ($bulkGetStockRes["status"]["errorCode"] != 0) {
            info("Synccare to AX : Error while getting product stock");
            return response("Synccare to AX : Error while getting product stock");
            die;
        }

        foreach ($reqWarehouses as $key => $wh) {
            // echo $key;
            // die;
            //synccare products 
            //for write off
            $writeOffReq = array(
                "requestName" => "saveInventoryWriteOff",
                "warehouseID" => $wh->erplyID,
                "reasonID" => $reasonID, //env('isLive') == true ? 5 : 58,
            );

            //for save inventory registration
            $invRegReq = array(
                "requestName" => "saveInventoryRegistration",
                "warehouseID" => $wh->erplyID
            );

            $wFlag = false;
            $rFlag = false;

            // $warehouseWiseProduct = array();

            $countWriteOff = 1;
            $countSaveInv = 1;

            $warehouseWiseProducts = array();
            // $warehouseWiseSohInvReg = array();
            foreach ($reqProducts[$key] as $pkey => $synncStock) {
                // dd($synncStock);
                // dd($products);
                //now comparing products
                // dd($products);

                // foreach($products as $pkey => $synncStock){
                // dd($synncStock);
                // die;
                //  dd($bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock']);
                //  die;
                if ($bulkGetStockRes['requests'][$key]['status']['errorCode'] == 0) {

                    //if records empty then do inventory regisration
                    if (empty($bulkGetStockRes['requests'][$key]['records'])) {

                        // echo (double)$synncStock->AvailablePhysical.' '. (double)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'];
                        // die;

                        //check if soh is 0 then no need to register inventory 
                        if ((float)$synncStock->AvailablePhysical != 0) {
                            $rFlag = true;
                            $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                            $invRegReq["amount" . $countSaveInv] = (float)$synncStock->AvailablePhysical;
                            $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                            $countSaveInv++;
                            $warehouseWiseProducts[] = $synncStock;
                        } else {
                            //soh not exist in erply and soh 0 in syn
                            LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                        }
                    } else {

                        // dd($bulkGetStockRes['requests'][$key]['records']);
                        // if (count($bulkGetStockRes["requests"][$key]["records"]) == count($reqProducts[$key])) {
                        //     info("All product stock exist in erply");
                        //     // dd("all product exist");
                        //     // die;
                        //     //all product stock exist in erply 
                        //     if ((double)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] > (double)$synncStock->AvailablePhysical) {
                        //         // dd("Erply Stock Greater than available");
                        //         $writeOffReq["productID" . $countWriteOff] = $synncStock->erplyID;
                        //         $writeOffReq["amount" . $countWriteOff] = (double)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] - (double)$synncStock->AvailablePhysical;
                        //         $writeOffReq["price" . $countWriteOff] = $synncStock->RetailSalesPrice;
                        //         // dd($writeOffReq);
                        //         $countWriteOff++;
                        //         $wFlag = true;
                        //         $warehouseWiseProducts[] = $synncStock;
                        //     }elseif ((double)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'] < (double)$synncStock->AvailablePhysical) {
                        //         // dd("Available physical greater then erplyStock");
                        //         $rFlag = true;
                        //         $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                        //         $invRegReq["amount" . $countSaveInv] = (double)$synncStock->AvailablePhysical - (double)$bulkGetStockRes['requests'][$key]['records'][$pkey]['amountInStock'];
                        //         $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                        //         $countSaveInv++;
                        //         // dd($invRegReq);
                        //         $warehouseWiseProducts[] = $synncStock;
                        //     } else {
                        //         // dd("equals");
                        //         LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                        //     }
                        // } else {
                            // dd("not all product exists");
                            //all product stock doesn't exist in erply so check product stock manually
                            // info("All product stock doesn't exist in erply");
                            // $isStockExist = 0;
                            $checkStockExist = $this->checkStockExistInResponse($bulkGetStockRes['requests'][$key]['records'], $synncStock->erplyID);
                            // $isStockExist = $checkStockExist["isExist"];
                            if ($checkStockExist["isExist"] == 0) {

                                if ((double)$synncStock->AvailablePhysical != 0) {
                                    $rFlag = true;
                                    $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                                    $invRegReq["amount" . $countSaveInv] = (double)$synncStock->AvailablePhysical;
                                    $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                                    $countSaveInv++;
                                    $warehouseWiseProducts[] = $synncStock;
                                } else {
                                    //soh not exist in erply and soh 0 in syn
                                    LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                                }
                            }
                            if ($checkStockExist["isExist"] == 1) {

                                if ((double)$checkStockExist["erplyStock"] > (double)$synncStock->AvailablePhysical) {
                                    $writeOffReq["productID" . $countWriteOff] = $synncStock->erplyID;
                                    $writeOffReq["amount" . $countWriteOff] = (double)$checkStockExist["erplyStock"] - (double)$synncStock->AvailablePhysical;
                                    $writeOffReq["price" . $countWriteOff] = $synncStock->RetailSalesPrice;
                                    $countWriteOff++;
                                    $wFlag = true;
                                    $warehouseWiseProducts[] = $synncStock;
                                }elseif ((double)$checkStockExist["erplyStock"] < (double)$synncStock->AvailablePhysical) {

                                    $rFlag = true;
                                    $invRegReq["productID" . $countSaveInv] = $synncStock->erplyID;
                                    $invRegReq["amount" . $countSaveInv] = (double)$synncStock->AvailablePhysical - (double)$checkStockExist["erplyStock"];
                                    $invRegReq["price" . $countSaveInv] = $synncStock->RetailSalesPrice;
                                    $countSaveInv++;
                                    $warehouseWiseProducts[] = $synncStock;
                                } else {

                                    LiveItemByLocation::where("id", $synncStock["id"])->update(["sohPending" => 0]);
                                }
                            }
                            // die;
                        // }
                    }
                }
                // else{
                //     info("Update SOH ****************".$bulkGetStockRes['requests'][$key]['status']['errorCode']."********************************");
                // }



                // }

            }
            // dd($writeOffReq);
            if ($wFlag == true) {
                $bulkSohUpdateReq[] = $writeOffReq;
                $bulkSohWarehouseReq[] = $wh;
                // $bulkSohWriteProduct[] = $warehouseWiseSohWriteOff;
            }

            if ($rFlag == true) {
                $bulkSohUpdateReq[] = $invRegReq; // for erply
                $bulkSohWarehouseReq[] = $wh;   //for local 
                // $bulkSohWriteProduct[] = $warehouseWiseSohWriteOff; //for local

            }

            if ($rFlag == true || $wFlag == true) {
                $bulkSohUpdateProducts[$wh->erplyID] = $warehouseWiseProducts;
            }
        }
       
        


        // dd($bulkSohUpdateReq);
        $erplyReqCopy = $bulkSohUpdateReq;
        // info($bulkSohUpdateReq);

        //now sending soh registration and write off bulk request
        if (count($bulkSohUpdateReq) < 1) {
            info("Synccare to Erply : All SOH Updated");

            return response("Synccare to Erply : All SOH Updated");
        }


        $bulkSohUpdateReq = json_encode($bulkSohUpdateReq, true);

        $bulkParam  = array(
            "sessionKey" => $this->api->client->sessionKey
        );

        $bulkSohUpdateRes = $this->api->sendRequest($bulkSohUpdateReq, $bulkParam, 1);

        if ($bulkSohUpdateRes["status"]["errorCode"] == 0 && !empty($bulkSohUpdateRes["requests"])) {
            foreach ($erplyReqCopy as $key => $finalWH) {
                $productRequested = [];
                foreach ($finalWH as $rkey => $rd) {
                    if (str_contains($rkey, 'productID')) {
                        $productRequested[] = $rd;
                    }
                }
                if ($bulkSohUpdateRes["requests"][$key]["status"]["errorCode"] == 0) {

                    foreach ($productRequested as $rpp) {

                        foreach ($bulkSohUpdateProducts[$finalWH["warehouseID"]] as $products) {

                            //for write off and inv reg just update sohPending = 1
                            if ($rpp == $products["erplyID"]) {

                                //just update soh pedning
                                LiveItemByLocation::where("id", $products["id"])->update(["sohPending" => 0]); //, "invRegID" => $res['requests'][$key]['records'][0]['inventoryRegistrationID'] ]);

                            }
                        }
                    }
                }
            }

            info("*************************************************Synccare to Erply : Product Stock Updating..................................................................");
        }

        return response()->json($bulkSohUpdateRes);
    }

    private function checkStockExistInResponse($response, $productID)
    {
        // dd($response);
        foreach ($response as $stock) {
            if ($stock["productID"] == $productID) {
                return ["isExist" => 1, "erplyStock" => $stock["amountInStock"]];
            }
        }
        return ["isExist" => 0];
    }


    public function getInventoryRegistration($wid, $pid, $invid)
    {
        // echo "im called";
        //first checking from direct Inventory Registration ID if exist
        // $inventory_id = InventoryRegistration::where('warehouseID', $wid)->where('productSKU', $psku)->first();
        // dd($stock);
        // die;
        // echo $stock["invRegID"];
        // echo $stock->sohPending
        // die;
        // echo $invid;
        // // echo $stock['InvRegID'];
        // die;
        // dd($pid);
        if ($invid) {
            info("im exist in local db");

            // die;
            $param = array(
                'inventoryRegistrationID' => $invid,
                'sessionKey' => $this->api->client->sessionKey,
            );

            $res = $this->api->sendRequest("getInventoryRegistrations", $param, 0, 0, 0);
            // dd($res);
            if ($res['status']['errorCode'] == 0 && !empty($res['records'])) {
                // dd($res['records']);
                foreach ($res['records'] as $val) {
                    // echo $val['warehouseID'];
                    foreach ($val['rows'] as $pro) {
                        if ($pro['productID'] ==  $pid) {
                            $this->deleteInventoryRegistration($val['inventoryRegistrationID']);
                            // return $val['inventoryRegistrationID'];
                            // echo "Product Inventory Registered";
                            info("Inventory Exist direct RID " . $val['inventoryRegistrationID']);
                            //now deleting from erply
                            // $this->deleteInventoryRegistration($val['inventoryRegistrationID']);
                            // return $val['inventoryRegistrationID'];
                        }
                    }
                }

                // return '';
            }
        }

        // dd("im not exist in local db");
        // dd("not found");
        $param = array(
            'warehouseID' => $wid,
            'sessionKey' => $this->api->client->sessionKey,
        );
        $res = $this->api->sendRequest("getInventoryRegistrations", $param, 0, 0, 0);
        // dd($res);
        if ($res['status']['errorCode'] == 0 && !empty($res['records'])) {
            // info($res['records']);
            foreach ($res['records'] as $val) {

                // echo $val['warehouseID'];
                foreach ($val['rows'] as $pro) {
                    if ($pro['productID'] == $pid) {
                        // echo "Product Inventory Registered";
                        info("Inventory Exist" . $val['inventoryRegistrationID']);
                        // dd($val['inventoryRegistrationID']);
                        $this->deleteInventoryRegistration($val['inventoryRegistrationID']);
                        // $this->deleteInventoryRegistration($val['inventoryRegistrationID']);
                        // return $val['inventoryRegistrationID'];
                    }
                }
            }
        }

        return '';
    }

    protected function deleteInventoryRegistration($id)
    {
        $param = array(
            'inventoryRegistrationID' => $id,
            'sessionKey' => $this->api->client->sessionKey,
        );
        info("Deleting Inventory...");
        $this->api->sendRequest("deleteInventoryRegistration", $param, 0, 0, 0);
    }

    public function syncTransferOrder($req)
    {

        

        $transfers = LiveTransferOrderLine::
            // whereHas([
            //         "fromWarehouse" => function($q){
            //             $q->whereIn("LocationID", $this->api->getLocationID());
            //         }
            //     ]
            // )
            // ->whereHas([
            //         "toWarehouse" => function($q){
            //             $q->whereIn("LocationID", $this->api->getLocationID());
            //         }
            //     ]
            // ) 
            with("fromDetails")
            ->with("toDetails")
            ->whereIn("FromWarehouse", $this->api->getLocationID())
            ->whereIn("ToWarehouse", $this->api->getLocationID())
            ->where("pendingProcess", 1)
            // ->where("transError", '')
            ->where("LineModifiedDateTime", '>', '2023-08-20')
            ->where("isErplyTO", 0)
            ->groupBy("newsystem_transfer_order_lines.TransferNumber")
            ->limit(1)
            ->get();
        // dd($transfers);

        if ($transfers->isEmpty()) {
            info("Synccare to Erply : All Transfer Order Synced to Erply.");
            return response("All Transfer Order Synced to Erply.");
            // die;
        }

        // dd($transfers);

        $bulkReq = array();
        $newTransfer = array();

        foreach ($transfers as $tf) {
            // dd($tf);
            if (count($newTransfer) > 50) {
                break;
            }
            // dd($tf);
            $param = array(
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "requestName" => "saveInventoryTransfer",
                "warehouseFromID" => $tf->fromDetails->erplyID,
                "warehouseToID" => $tf->toDetails->erplyID,
                "type" => "TRANSFER_ORDER",
                "attributeName1" => "TransferNumber",
                "attributeType1" => "text",
                "attributeValue1" => $tf->TransferNumber,
            );

            $toid = $this->getTransferOrder($tf->TransferNumber);

            if ($toid != '') {
                $param["inventoryTransferID"] = $toid;
            }

            $lines = LiveTransferOrderLine::
                //     join("newsystem_product_variation_live", function($join){
                //     $join->on("newsystem_product_variation_live.ITEMID", "newsystem_transfer_order_lines.ItemNumber")
                //     ->on("newsystem_product_variation_live.CONFIGID", "newsystem_transfer_order_lines.Configuration")
                //     ->on("newsystem_product_variation_live.ColourID", "newsystem_transfer_order_lines.Colour")
                //     ->on("newsystem_product_variation_live.SizeID", "newsystem_transfer_order_lines.Size");
                // })
                where("newsystem_transfer_order_lines.TransferNumber", $tf->TransferNumber)
                ->where("LineModifiedDateTime", '>', '2023-06-01')
                // ->where("transError", '')
                // ->select(["newsystem_product_variation_live.erplyID as productID","newsystem_transfer_order_lines.*"])
                // ->limit(50)
                ->get();
            // dd($lines);
            if (count($lines) > 250) {
                LiveTransferOrderLine::where("TransferNumber", $tf->TransferNumber)->update(["transError" => "To Many Lines"]);
                break;
            }
            $flag = 0;
            $totTransferLines = count($lines);
            foreach ($lines as $key => $l) {

                if ($l->Configuration == '0' || $l->Configuration == '' || $l->Configuration == 0) {
                    $product = LiveProductGenericVariation::
                        where("ICSC", $l->ERPLYSKU)
                        // where("ITEMID", $l->ItemNumber)
                        // ->where("ColourID", $l->Colour)
                        // ->where("SizeID", $l->Size)
                        ->where("erplyID",'>', 0)
                        // ->where("erplyPending", 0)
                        ->first();
                } else {

                    $product = LiveProductVariation::
                        where("ERPLYSKU", $l->ERPLYSKU)
                        // where("CONFIGID", $l->Configuration)
                        // ->where("ITEMID", $l->ItemNumber)
                        // ->where("ColourID", $l->Colour)
                        // ->where("SizeID", $l->Size)
                        ->where("erplyID",'>', 0)
                        ->first();
                }

                
                if ($product) {
                    dd($product);
                    $flag = $flag + 1;
                    $param["productID" . $flag] = $product->erplyID;
                    $param["amount" . $flag] = $l->ShippedQty;
                    $param["price" . $flag] = $product->RetailSalesPrice;
                }
            }

            // dd($param);
            if ($flag > 0 && $totTransferLines == $flag) {
                $bulkReq[] = $param;
                $newTransfer[] = $tf;

                //if ok than update pending status
                // foreach($lines as $ll){
                //     $ll->pendingProcess = 0;
                //     $ll->save();
                // }
            } else {
                LiveTransferOrderLine::where("TransferNumber", $tf->TransferNumber)->update(["pendingProcess" => 2]);
            }
        }

        dd($bulkReq);
        // die;


        if (count($bulkReq) < 1) {
            info("Transfer Order Not Found");
            return response("Transfer Order Not Found.");
        }

        $bulkParam = array(
            "lang" => 'eng',
            "responseType" => "json",
            "sessionKey" => $this->api->client->sessionKey
        );

        $bulkReq = json_encode($bulkReq, true);
        $res = $this->api->sendRequest($bulkReq, $bulkParam, 1);


        if ($res['status']['errorCode'] == 0 && !empty($res['requests'])) {
            foreach ($newTransfer as $key => $c) {
                if ($res['requests'][$key]['status']['errorCode'] == 0) {
                    LiveTransferOrderLine::where("TransferNumber", $c['TransferNumber'])->update(["pendingProcess" => 0, "erplyID" => $res['requests'][$key]['records'][0]['inventoryTransferID']]);
                } else {
                    LiveTransferOrderLine::where("TransferNumber", $c['TransferNumber'])->update(["transError" => $res['requests'][$key]['status']['errorCode']]);
                }
            }
            info("Inventory Transfer Created or Updated to Erply");
        }

        return response()->json($res);
    }

    protected function getTransferOrder($tn)
    {
        $param = array(
            'searchAttributeName' => "TransferNumber",
            'searchAttributeValue' => $tn,
            'sessionKey' => $this->api->client->sessionKey,
        );
        // info("Deleting Inventory...");
        $res = $this->api->sendRequest("getInventoryTransfers", $param);

        if ($res['status']['errorCode'] == 0 && !empty($res['records'])) {
            return $res['records'][0]['inventoryTransferID'];
        }
        return '';
    }

    //synccing TO INVENTTRANSID to ERPLY DB

    public function syncInventTransID()
    {

        //first getting erply TOs

    }
}
