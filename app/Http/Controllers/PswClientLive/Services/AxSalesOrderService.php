<?php

namespace App\Http\Controllers\PswClientLive\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\SalesDocument;
use App\Models\PAEI\SalesDocumentDetail;
use App\Models\PswClientLive\AxSalesLine;
use App\Models\PswClientLive\AxSalesOrder;
use App\Models\PswClientLive\Local\LiveItemLocation;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Traits\AxTrait;
use App\Classes\UserLogger;
use App\Http\Controllers\Paei\Services\GetPaymentService;
use App\Http\Controllers\Paei\Services\GetSalesDocumentService;
use App\Http\Controllers\Services\AxUpDownService;
use App\Models\Client;
use App\Models\DuplicateRecid;
use App\Models\Kudos\SalesPeriod;
use App\Models\PAEI\Customer;
use App\Models\PAEI\Payment;
use App\Models\PAEI\Warehouse;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveProductGenericVariation;
use App\Models\PswClientLive\Local\LiveSalesOrder;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/*
    *@PROCESS
    *GETTING SALES ORDER FROM SYNCCARE DB
    *CHECK IF CUSTOMER  OR DEFAULT CUSTOMER
    *IF TYPE ORDER, PREPAYMENT THEN SYNC CUSTOMER AND FOR CASHINVOICE IF NOT FULLY PAID THEN SYNC CUSTOMER OTHERWISE SYNC DEFAULT CUSTOMER
    *PREFIX FOR DOCUMENT ID FOR ACADEMY ORDER PA AND FOR PSW PP
    *THERE IS EXTRA ITEM LIKE MISC, FRIGHT , CC_PICKUP AND GIFTCARD 
    *FOR EXTRA ITEM THERE IS NO COLOR SIZE AND SIZE
    * IF LINE ITEMS CONTAINS CC_SCHOOL THEN DELIVERY MODE SHOULD BE CC_SCHOOL
    * TENDER TYPE SHOULD BE ACCORDING TO AX DOCUMENTATION EG. CASH : 1 , CARD : 5 ...
    * MULTIPAYMENT SHOULD HAVE MULTIPLE SALES ORDER LINE AND INVOICE ID SHOULD BE UNIQUE BY ADDING INVOICEID_1, _2...
    * IF SINGLE PAYMENT AND TYPE IS ORDER THEN PREPAYMENT = 1 AND FINALPAYMENT = 1 
    * FOR VOUCHER PAYMENT LIKE ACCOUNT AND SSR , DETAILS WILL BE INSIDE NOTES FILED 
    * THERE IS EXTRA FIELD VOUCHER FLAG SHOULD BE 1 
    * AND VOUCHER REF AND VOUCHERACCOUNT SHOULD BE SENT
    * FOR TYPE ACCOUNT VOUCHER, VOUCHERREF = SCHOOL PO FIELD VALUE AND VOUCHER ACCOUNT = VOUCHER ACCOUNT FIELD VALUE
    * FOR TYPE SSR VOUCHERACCOUNT = 20062 AND VOUCHERREF = INVOICEID _ 1, _1 BUT INVOICEID AND VOUCHERREF SHOULD NOT BE SAME
    * FOR EVERY RECORD WE NEED RECID WHICH IS UNIQUE VALUE FOR THE FIRST TIME AX WILL GENERATE THIS VALUE AND RECID FROM SEQUENCE TABLE OF AX
    * GENERATING RECID 
    * FIRST THERE IS SINGLE RECID ACCRODING TO TABLEID 
    * FIRST WE INSERT USING THIS RECID AND AFTER THAT COUNT TOTAL RECORD OF TABLE + PREVIOUS RECID AND STORE INTO SEQUENCE TABLE 
    * AND FOR SENCOND RECORD WE USE THIS RECID AND CONTINUE GENERATING AND STORING RECID INTO SEQUENCE TABLE 
    *  
*/

class AxSalesOrderService
{

    use AxTrait;
    protected $ax_order;
    protected $mi_order;
    protected $ax_order_line;
    protected $mi_order_line;
    protected $api;
    protected $customer_service;
    protected $paymentService;
    protected $salesDocumentService;
    protected $shoeGroupProductsSku = [
        'LX10140BLALE060',
        'LX10140BLALE070',
        'LX10140BLALE080',
        'LX10140BLALE090',
        'LX10140BLALE100',
        'LX10140BLALE110',
        'LX10140BLALE120',
        'LX10140BLALE130',
        'LX10053BLALE060',
        'LX10053BLALE065',
        'LX10053BLALE070',
        'LX10053BLALE075',
        'LX10053BLALE080',
        'LX10053BLALE085',
        'LX10053BLALE090',
        'LX10053BLALE095',
        'LX10053BLALE100',
        'LX10053BLALE110',
        'LX10141BLALE900',
        'LX10141BLALE100',
        'LX10141BLALE110',
        'LX10141BLALE120',
        'LX10141BLALE130',
        'LX10141BLALE010',
        'LX10141BLALE020',
        'LX10141BLALE030',
        'LX10141BLALE040',
        'LX10141BLALE050',
        'LX10066BLALE900',
        'LX10066BLALE100',
        'LX10066BLALE110',
        'LX10066BLALE120',
        'LX10066BLALE130',
        'LX10066BLALE010',
        'LX10066BLALE020',
        'LX10066BLALE030',
        'LX10066BLALE040',
        'LX10066BLALE050',
        'LX10101BLALE900',
        'LX10101BLALE100',
        'LX10101BLALE110',
        'LX10101BLALE120',
        'LX10101BLALE130',
        'LX10101BLALE010',
        'LX10101BLALE020',
        'LX10101BLALE030',
        'LX10101BLALE040',
        'LX10101BLALE050',
        'LX10240BLALE900',
        'LX10240BLALE100',
        'LX10240BLALE110',
        'LX10240BLALE120',
        'LX10240BLALE130',
        'LX10240BLALE010',
        'LX10240BLALE020',
        'LX10240BLALE030',
        'LX10240BLALE040',
        'LX10240BLALE050',
        'LX10240BLALE060',
        'LX10241BLALE900',
        'LX10241BLALE100',
        'LX10241BLALE110',
        'LX10241BLALE120',
        'LX10241BLALE130',
        'LX10241BLALE010',
        'LX10241BLALE020',
        'LX10241BLALE030',
        'LX10241BLALE040',
        'LX10241BLALE050',
        'LX10241BLALE060',
        'CF10616BLALE060',
        'CF10616BLALE070',
        'CF10616BLALE080',
        'CF10616BLALE090',
        'CF10616BLALE100',
        'CF10616BLALE110',
        'CF10616BLALE120',
        'CF10616BLALE130',
        'CF10616BLALE140',
        'CF10671BLALE060',
        'CF10671BLALE065',
        'CF10671BLALE070',
        'CF10671BLALE075',
        'CF10671BLALE080',
        'CF10671BLALE085',
        'CF10671BLALE090',
        'CF10671BLALE095',
        'CF10671BLALE100',
        'CF10671BLALE105',
        'CF10671BLALE110',
        'CF10671BLALE120',
        'CF10671BLALE130'
    ];

    public function __construct(AxSalesOrder $ax_order, SalesDocument $mi_order, AxSalesLine $ax_order_line, SalesDocumentDetail $lines, EAPIService $api, AxCustomerService $ax_customer_service, GetPaymentService $paymentService, GetSalesDocumentService $salesDocumentService)
    {

        if (AxUpDownService::isAxWriteDown() == 1) {
            die("AX Write Service Down!!!");
        }

        $this->ax_order = $ax_order;
        //middleserver sales orders
        $this->mi_order = $mi_order;
        //ax order lines
        $this->ax_order_line = $ax_order_line;
        //middleserver order details
        $this->mi_order_line = $lines;

        $this->api =  $api;
        $this->customer_service = $ax_customer_service;
        $this->paymentService = $paymentService;
        $this->salesDocumentService = $salesDocumentService;
    }

    public function checkIsAcademySalesOrder($data)
    {

        $checkIsLive = Client::where("clientCode", $data->clientCode)->first()->ENV;

        if ($checkIsLive == 0) {
            // if(env("isLive") == false){
            return 1;
        }
        if ($checkIsLive == 1) {
            // if(env("isLive") == true){
            if ($data->clientCode == 607655) {
                return 1;
            } else {
                return 0;
            }
        }
    }



    //get document prefix
    private function getDocumentPreFix($data)
    {
        $checkIsLive = Client::where("clientCode", $data->clientCode)->first()->ENV;
        $preFix = '';
        if ($checkIsLive == 0) {
            $preFix = $data->clientCode == 605325 ? "PP" : "PP";
        }

        if ($checkIsLive == 1) {
            $pswPatch = "PP";
            if ($data->salesDocumentID >= 2581 && $data->salesDocumentID <= 3113) {
                $pswPatch = "PP_";
            }
            $preFix = $data->clientCode == 607655 ? "PA" : $pswPatch;
        }

        return $preFix;
    }
    public function syncMiddlewareToAx($req)
    {

        // $checkIsLive = Client::where("clientCode", $data->clientCode)->first()->ENV;
        // return $this->syncMiddlewareToAxWithCancelledOrder($req);
        // die;
        if ($req->version == "v2") {
            return $this->syncMiddlewareToAxWithCancelledOrder($req);
            die;
        }
        die("Version 1 Cron Disabled");

        $isDebug = $req->debug ? $req->debug : false;
        $customSalesDocumentID = $req->id ? $req->id : 0;

        info("Sync Sales Document To AX Cron");

        $fiveMinutesAgo = Carbon::now()->subMinutes(5);
        info($fiveMinutesAgo);

        $mi_orders = SalesDocument:: //paymentsWithClientCode()
            join("newsystem_warehouse_locations", function ($join) {
                $join->on("newsystem_warehouse_locations.warehouseID", "=", "newsystem_sales_documents.warehouseID");
                $join->on("newsystem_warehouse_locations.clientCode", "=", "newsystem_sales_documents.clientCode");
            })
            // ->where("newsystem_sales_documents.clientCode", $this->api->client->clientCode)
            // ->where("newsystem_warehouse_locations.clientCode", $this->api->client->clientCode)
            ->whereIn("newsystem_sales_documents.type", ['WAYBILL', 'ORDER', 'PREPAYMENT', 'CREDITINVOICE', 'CASHINVOICE'])
            ->whereNotIn("newsystem_sales_documents.invoiceState", ['CANCELLED'])
            // ->where("newsystem_customers.clientCode", $this->api->client->clientCode)
            ->where("newsystem_sales_documents.salesDocumentID", '>', 0)
            ->where("newsystem_sales_documents.axPending", 1)
            ->where("newsystem_sales_documents.noLineFlag", 0)
            ->where("newsystem_sales_documents.paymentFlag", 0)
            ->where("newsystem_sales_documents.errorFlag", 0)
            ->where("newsystem_sales_documents.erplyDeleted", 0)
            ->where("newsystem_sales_documents.number", "<>", "0")
            ->whereNotIn("newsystem_sales_documents.invoiceState", ["CANCELLED", "PENDING"])
            // ->where("newsystem_sales_documents.salesDocumentID", 221)
            ->select([
                "newsystem_sales_documents.*",
                "newsystem_sales_documents.notes as orderNotes",
                "newsystem_warehouse_locations.code as warehouseCode"
            ])
            ->where("newsystem_sales_documents.created_at", '<', $fiveMinutesAgo)
            ->limit(7)
            // ->toSql();
            ->get();


        if ($customSalesDocumentID > 0) {
            // dd("hi");

            $mi_orders = SalesDocument::join("newsystem_warehouse_locations", function ($join) {
                    $join->on("newsystem_warehouse_locations.warehouseID", "=", "newsystem_sales_documents.warehouseID");
                    $join->on("newsystem_warehouse_locations.clientCode", "=", "newsystem_sales_documents.clientCode");
                })
                ->whereIn("newsystem_sales_documents.type", ['WAYBILL', 'ORDER', 'PREPAYMENT', 'CREDITINVOICE', 'CASHINVOICE'])
                ->whereNotIn("newsystem_sales_documents.invoiceState", ['CANCELLED'])
                // ->where("newsystem_sales_documents.clientCode", $req->clientcode)
                ->where("newsystem_sales_documents.salesDocumentID", '>', 0)
                ->where("newsystem_sales_documents.erplyDeleted", 0)
                ->where("newsystem_sales_documents.id", $customSalesDocumentID)
                // ->where("newsystem_sales_documents.clientCode", "newsystem_warehouse_locations.clientCode")
                // ->select(["newsystem_sales_documents.*","newsystem_sales_documents.notes as orderNotes"
                // ])  
                ->select([
                    "newsystem_sales_documents.*",
                    "newsystem_sales_documents.notes as orderNotes",
                    "newsystem_warehouse_locations.code as warehouseCode"
                ])
                // ->limit(1)
                // ->toSql();
                ->get();
            // dd($mi_orders);
        }


        if ($mi_orders->isEmpty()) {
            info("All Sales Document Synced to AX");
            return response()->json(["message" => "All Sales Document Synced"]);
        }



        foreach ($mi_orders as $data) {

            try {

                //first getting order Warehouse info of ax
                //first getting erply warehouse details
                $erply_wh_details = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();
                //second getting ax warehouse details
                $ax_wh_details = LiveWarehouseLocation::where("LocationID", $erply_wh_details->code)->first();

                $isAcademySalesOrder = $this->checkIsAcademySalesOrder($data);

                $isAxSalesOrder = false;
                $pswAxSalesID = '';

                $isPswAxCustomer = false;
                $isPswAxCustomerID = 1;
                $checkIsPswAxCustomer = LiveCustomerRelation::where($isAcademySalesOrder == 1 ? "customerID" : "pswCustomerID", $data->clientID)->first();
                // dd($checkIsPswAxCustomer);
                if ($checkIsPswAxCustomer) {
                    $isPswAxCustomer = true;
                    $isPswAxCustomerID = $checkIsPswAxCustomer->PSW_SMMCUSTACCOUNT;
                }

                $isParent = false;
                $parentOrder = [];
                if ($data->baseDocuments != "") {
                    $baseDoc = json_decode($data->baseDocuments, true);
                    // dd($baseDoc);
                    $parentOrder = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $baseDoc[0]["id"])->first();
                    if ($parentOrder) {
                        $isParent = true;
                    }
                }

                //getting school id using first order line erplysku
                $linesOrder = SalesDocumentDetail::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->where("isDeleted", 0)->get();
                // dd($linesOrder);

                if ($data->attributes != '') {
                    $checkOrder = json_decode($data->attributes, true);
                    foreach ($checkOrder as $key => $co) {
                        if (@$co['attributeName'] == "SALESID") {
                            $isAxSalesOrder = true;
                            $pswAxSalesID = $co["attributeValue"];
                        }
                    }
                }

                //getting document prefix according to client code
                $preFix = $this->getDocumentPreFix($data);

                if ($this->checkPaymentExists($data) == 1) {
                    $this->saveUpdateSalesOrders($data, $linesOrder, $isPswAxCustomer, $isPswAxCustomerID, $preFix, $isParent, $parentOrder, $isDebug, $ax_wh_details);
                }
            } catch (Exception $e) {
                info($e->getMessage());
                SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->update(["errorFlag" => 1, "errorMsg" => $e->getMessage()]);
            }
        }

        //now checking payment flag = 1 order
        // $this->checkPaymentFlag();
        return response()->json(["status" => "success"]);
    }

    public function syncMiddlewareToAxWithCancelledOrder($req)
    {

        // $dev = $req->dev ?? 0;
        // if($dev == 0){
        //     die("Hold");
        // }

        $isDebug = $req->debug ? $req->debug : 0;
        $customSalesDocumentID = $req->id ? $req->id : 0;
        $fiveMinutesAgo = Carbon::now()->subMinutes(5);

        info("Sync Sales Document To AX Cron");
        $isCancelled = 1;
        //scenario usually cancelled order are limited so first process calcelled if exsit otherwise processed normal order
        //only processed cancelled order + layby where all synced to ax 
        $mi_orders = SalesDocument:: //paymentsWithClientCode()
            join("newsystem_warehouse_locations", function ($join) {
                $join->on("newsystem_warehouse_locations.warehouseID", "=", "newsystem_sales_documents.warehouseID");
                $join->on("newsystem_warehouse_locations.clientCode", "=", "newsystem_sales_documents.clientCode");
            })
            ->where("newsystem_sales_documents.salesAxID", '>', 0)  //only synced and cancelled should be updated to ax as of now
            // ->where("newsystem_warehouse_locations.clientCode", $this->api->client->clientCode)
            ->whereIn("newsystem_sales_documents.type", ['ORDER', 'PREPAYMENT'])
            ->whereIn("newsystem_sales_documents.invoiceState", ['CANCELLED'])
            ->where("lastModified", '>=', '2024-03-07')
            // ->where("newsystem_customers.clientCode", $this->api->client->clientCode)
            ->where("newsystem_sales_documents.axPending", 1)
            ->where("newsystem_sales_documents.salesDocumentID", '>', 0)
            ->where("newsystem_sales_documents.noLineFlag", 0)
            // ->where("newsystem_sales_documents.clientID",'>', 0)
            ->where("newsystem_sales_documents.paymentFlag", 0)
            ->where("newsystem_sales_documents.errorFlag", 0)
            ->where("newsystem_sales_documents.erplyDeleted", 0)
            ->where("newsystem_sales_documents.number", "<>", "0")
            ->where("newsystem_sales_documents.created_at", '<', $fiveMinutesAgo)
            // ->whereNotIn("newsystem_sales_documents.invoiceState", ["CANCELLED", "PENDING"])
            // ->where("newsystem_sales_documents.salesDocumentID", 221)
            ->select([
                "newsystem_sales_documents.*",
                "newsystem_sales_documents.notes as orderNotes",
                "newsystem_warehouse_locations.code as warehouseCode"
            ])
            // ->orderBy("newsystem_sales_documents.updated_at")
            ->limit(7)
            ->get();



        if ($mi_orders->isEmpty()) {
            $isCancelled = 0;
            $mi_orders = SalesDocument:: //paymentsWithClientCode()
                join("newsystem_warehouse_locations", function ($join) {
                    $join->on("newsystem_warehouse_locations.warehouseID", "=", "newsystem_sales_documents.warehouseID");
                    $join->on("newsystem_warehouse_locations.clientCode", "=", "newsystem_sales_documents.clientCode");
                })
                // ->where("newsystem_sales_documents.clientCode", $this->api->client->clientCode)
                // ->where("newsystem_warehouse_locations.clientCode", $this->api->client->clientCode)
                ->whereIn("newsystem_sales_documents.type", ['WAYBILL', 'ORDER', 'PREPAYMENT', 'CREDITINVOICE', 'CASHINVOICE'])
                ->whereNotIn("newsystem_sales_documents.invoiceState", ['CANCELLED'])
                // ->where("newsystem_customers.clientCode", $this->api->client->clientCode)
                ->where("newsystem_sales_documents.axPending", 1)
                ->where("newsystem_sales_documents.salesDocumentID", '>', 0)
                ->where("newsystem_sales_documents.noLineFlag", 0)
                // ->where("newsystem_sales_documents.clientID",'>', 0)
                ->where("newsystem_sales_documents.paymentFlag", 0)
                ->where("newsystem_sales_documents.errorFlag", 0)
                ->where("newsystem_sales_documents.erplyDeleted", 0)
                ->where("newsystem_sales_documents.number", "<>", "0")
                ->whereNotIn("newsystem_sales_documents.invoiceState", ["CANCELLED", "PENDING"])
                ->where("newsystem_sales_documents.created_at", '<', $fiveMinutesAgo)
                // ->where("newsystem_sales_documents.salesDocumentID", 221)
                ->select([
                    "newsystem_sales_documents.*",
                    "newsystem_sales_documents.notes as orderNotes",
                    "newsystem_warehouse_locations.code as warehouseCode"
                ])
                ->limit(7)
                // ->toSql();
                ->get();
        }

        if ($customSalesDocumentID > 0) {

            $mi_orders = SalesDocument::join("newsystem_warehouse_locations", function ($join) {
                    $join->on("newsystem_warehouse_locations.warehouseID", "=", "newsystem_sales_documents.warehouseID");
                    $join->on("newsystem_warehouse_locations.clientCode", "=", "newsystem_sales_documents.clientCode");
                })
                ->whereIn("newsystem_sales_documents.type", ['WAYBILL', 'ORDER', 'PREPAYMENT', 'CREDITINVOICE', 'CASHINVOICE'])
                ->whereNotIn("newsystem_sales_documents.invoiceState", ['CANCELLED'])
                ->where("newsystem_sales_documents.salesDocumentID", '>', 0)
                // ->where("newsystem_sales_documents.clientCode", $req->clientcode)
                ->where("newsystem_sales_documents.erplyDeleted", 0)
                ->where("newsystem_sales_documents.id", $customSalesDocumentID)
                // ->where("newsystem_sales_documents.clientCode", "newsystem_warehouse_locations.clientCode")
                // ->select(["newsystem_sales_documents.*","newsystem_sales_documents.notes as orderNotes"
                // ])  
                ->select([
                    "newsystem_sales_documents.*",
                    "newsystem_sales_documents.notes as orderNotes",
                    "newsystem_warehouse_locations.code as warehouseCode"
                ])
                ->get();
        }

        if ($isDebug == 1) {
            dd($mi_orders);
        }


        if ($mi_orders->isEmpty()) {
            info("All Sales Document Synced to AX");
            return response()->json(["message" => "All Sales Document Synced"]);
        }



        foreach ($mi_orders as $data) {

            try {
                $data->updated_at = date("Y-m-d H:i:s");
                $data->save();
                //first getting order Warehouse info of ax
                //first getting erply warehouse details
                $erply_wh_details = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();
                //second getting ax warehouse details
                $ax_wh_details = LiveWarehouseLocation::where("LocationID", $erply_wh_details->code)->first();
                // dd($erply_wh_details, $ax_wh_details, $data);


                $isAcademySalesOrder = $this->checkIsAcademySalesOrder($data);


                $isAxSalesOrder = false;
                $pswAxSalesID = '';

                $isPswAxCustomer = false;
                $isPswAxCustomerID = 1;
                $checkIsPswAxCustomer = LiveCustomerRelation::where($isAcademySalesOrder == 1 ? "customerID" : "pswCustomerID", $data->clientID)->first();
                // dd($checkIsPswAxCustomer);
                if ($checkIsPswAxCustomer) {
                    $isPswAxCustomer = true;
                    $isPswAxCustomerID = $checkIsPswAxCustomer->PSW_SMMCUSTACCOUNT;
                }

                $isParent = false;
                $parentOrder = [];
                if ($data->baseDocuments != "") {
                    $baseDoc = json_decode($data->baseDocuments, true);

                    $parentOrder = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $baseDoc[0]["id"])->first();
                    if ($parentOrder) {
                        $isParent = true;
                    }
                }

                //getting school id using first order line erplysku
                $linesOrder = SalesDocumentDetail::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->where("isDeleted", 0)->get();


                if ($data->attributes != '') {
                    $checkOrder = json_decode($data->attributes, true);
                    foreach ($checkOrder as $key => $co) {
                        if (@$co['attributeName'] == "SALESID") {
                            $isAxSalesOrder = true;
                            $pswAxSalesID = $co["attributeValue"];
                        }
                    }
                }

                $preFix = $this->getDocumentPreFix($data);

                DB::connection('sqlsrv_psw_live')->beginTransaction();
                DB::beginTransaction();
                if ($this->checkPaymentExists($data) == 1) {
                    $this->saveUpdateSalesOrdersWithCancelled($data, $linesOrder, $isPswAxCustomer, $isPswAxCustomerID, $preFix, $isParent, $parentOrder, $isDebug, $ax_wh_details, $isCancelled);
                }

                DB::connection('sqlsrv_psw_live')->commit();
                DB::commit();
            } catch (Exception $e) {
                DB::connection('sqlsrv_psw_live')->rollBack();
                DB::rollBack();
                info($e);
                SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->update(["errorFlag" => 1, "errorMsg" => $e->getMessage()]);
            }
        }

        return response()->json(["status" => "success"]);
    }

    private function getPreFix($clientCode)
    {
        $preFix = '';
        if (env("isLive") == false) {
            $preFix = $clientCode == 605325 ? "PA" : "PP";
        }
        if (env("isLive") == true) {
            $preFix = $clientCode == 607655 ? "PA" : "PP";
        }

        return $preFix;
    }

    private function getRecursiveSalesDocument($data)
    {

        $newParent = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->baseDocumentID)->first();
        if ($newParent) {
            return $newParent;
        }
        return [];
    }

    private function getReturnCountForSalesDocument($data, $parent, $count = 0)
    {


        $finalCount = $count;
        $returnCount = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", "<=", $data->salesDocumentID)->where("baseDocumentID",  $data->baseDocumentID ? $data->baseDocumentID : 0)->count();
        $finalCount += $returnCount;


        if (@$parent->type == "CREDITINVOICE") {
            $newParent = $this->getRecursiveSalesDocument($parent);

            if ($newParent) {
                return $this->getReturnCountForSalesDocument($parent, $newParent, $finalCount);
            }
        }


        return ["count" => $finalCount, "newParent" => $parent];
    }

    private function getReturnCountForSalesDocumentV2($data, $parent, $count = 0)
    {


        $finalCount = $count;
        $returnCount = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", "<=", $data->salesDocumentID)->where("baseDocumentID",  $data->baseDocumentID ? $data->baseDocumentID : 0)->count();
        $finalCount += $returnCount;

        $baseParent = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->baseDocumentID)->first();

        return ["count" => $finalCount, "newParent" => $baseParent];
    }


    private function makeAxDocumentID($data, $preFix, $isParent, $parent, $isDebug = false)
    {
        switch ($data->type) {
            case "ORDER":
                return "RP_$preFix" . $data->salesDocumentID;
                // break;
            case "WAYBILL":
                return "RP_$preFix" . $data->salesDocumentID;
            case "CREDITINVOICE":
                if ($isParent == true) {

                    $newDoc = $this->getReturnCountForSalesDocumentV2($data, $parent);
                    $returnCount = $newDoc["count"];
                    $parent = $newDoc["newParent"];
                    if ($parent->type == "PREPAYMENT") {
                        return "C_LB_$preFix" . $parent->salesDocumentID . "_$returnCount";
                    } elseif ($parent->type == "CASHINVOICE") {
                        return "C_$preFix" . $parent->salesDocumentID . "_$returnCount";
                    } else {
                        return "RP_$preFix" . $parent->salesDocumentID . "_$returnCount";
                    }
                } else {
                    return "C_$preFix" . $data->salesDocumentID;
                }

            case "CASHINVOICE":
                if ($isParent == true && $parent->type == "PREPAYMENT") {
                    return "LB_$preFix" . $parent->salesDocumentID;
                } else {
                    return "$preFix" . $data->salesDocumentID;
                }
            case "PREPAYMENT":
                return "LB_$preFix" . $data->salesDocumentID;
            default:
                return '';
        }
    }



    private function saveLineItems($data, $linesOrder, $preFix, $isParent, $parentOrder, $axWHDetails)
    {

        info("*********************READY TO PROCESS LINE ITEMS OF SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
        $isAxSalesOrder = false;
        $pswAxSalesID = '';

        if ($data->attributes != '') {
            $checkOrder = json_decode($data->attributes, true);
            foreach ($checkOrder as $key => $co) {
                if (@$co['attributeName'] == "SALESID") {
                    $isAxSalesOrder = true;
                    $pswAxSalesID = $co["attributeValue"];
                }
            }
        }

        $isStandard = false;
        if ($data->type == "CASHINVOICE") {
            $isStandard = true;
        }

        $isReturnRefund = 0;
        if ($data->type == "CREDITINVOICE") {
            $isReturnRefund = 1;
        }


        $newDocID = '';

        $newDocID = $this->makeAxDocumentID($data, $preFix, $isParent, $parentOrder);


        //due to all line itme not syncing issue now checking all line item synced or not

        $lineProcessed = 0;

        foreach ($linesOrder as $l) {
            // die;
            $isExtraItem = false;
            // $isMisc = false;
            $axItemID = '';
            if ($l->code == "CC_School" || $l->code == "MISC" || $l->code == "Freight1" || $l->code == "Freight3" || $l->code == "CCPickup" ||  str_contains($l->code, "Giftcard") == true ||  str_contains($l->code, "PKTBRAID") == true ||  str_contains($l->code, "BLZPKTEMB") == true ||  str_contains($l->code, "UNSTITCH") == true ||  str_contains($l->code, "BLZPKT") == true ||  str_contains($l->code, "AIRFREIGHT") == true ||  str_contains($l->code, "SPLORDER") == true) {
                $isExtraItem = true;
                if (str_contains($l->code, "Giftcard") == true) {
                    $axItemID = "9999999";
                } else {
                    $axItemID = $l->code;
                }
            }

            if (in_array($l->code, $this->shoeGroupProductsSku)) {
                $isExtraItem = true;
                $axItemID = 'SHOES';
            } 


            $item_details = LiveProductVariation::where("ERPLYSKU", $l->code)->first();
            $isLineGeneric = 0;
            if (!$item_details) {
                $item_details = LiveProductGenericVariation::where("ICSC", $l->code)->first();
                $isLineGeneric = 1;
            }

            if ($isAxSalesOrder == false) {
                if ($item_details) {
                    if ($isLineGeneric == 0) {

                        //first getting info about which store is used to sell this items
                        $warehouseInfo = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();

                        $location = LiveItemLocation::where("warehouse", $warehouseInfo->code)->where("ERPLYSKU", $l->code)->first();
                        // if(!$location){
                        //     $location = LiveItemLocation::where("warehouse", $item_details->SecondaryStore)->where("ERPLYSKU", $l->code)->first();
                        // }

                        if (!$location) {
                            $location = LiveItemLocation::where("warehouse", $warehouseInfo->code)
                                ->where("item", $item_details->ITEMID)
                                ->where("size", $item_details->SizeID)
                                ->where("colour", $item_details->ColourID)
                                ->first();
                            // if(!$location){
                            //     $location = //LiveItemLocation::where("warehouse", $item_details->SecondaryStore)->where("ERPLYSKU", $l->code)->first();
                            //             LiveItemLocation::where("warehouse", $item_details->DefaultStore)
                            //                     ->where("item", $item_details->ITEMID)
                            //                     ->where("size", $item_details->SizeID)
                            //                     ->where("colour", $item_details->ColourID)
                            //                     ->first();
                            // }
                        }
                    } else {
                        $location = LiveItemLocation::where("ERPLYSKU", $l->code)->first();
                    }
                }
            }

            if ($isAxSalesOrder == true) {
                if (!$item_details) {
                    $location = LiveSalesOrder::where("SALESID", $pswAxSalesID)->where("ITEMID", $item_details->ITEMID)->where("CONFIGID", $item_details->CONFIGID)->where("INVENTCOLORID", $item_details->ColourID)->where("INVENTSIZEID", $item_details->SizeID)->first();
                }
            }

            $wmsLocation = '';
            if ($isAxSalesOrder == true) {
                $wmsLocation = $location->WMSLOCATIONID;
            } else {
                $wmsLocation = @$location->issueLocation ? @$location->issueLocation : 'DEF';
                //if qty < 1 then it is return itmes so, push receiptLocation
                if ($l->amount < 0) {
                    $wmsLocation = @$location->receiptLocation ? @$location->receiptLocation : 'DEF';
                }
            }
            $actualSalesPriceWithGst = $l->finalPriceWithVAT;
            if (abs($l->discount) > 0) {
                $actualSalesPriceWithGst = round($l->price * 1.1, 2);
            }

            $lineDetails = array(
                "DOCUMENTID" => $newDocID, // "ERPLY".$data->salesDocumentID, 
                "ITEMID" => $isExtraItem == true ? $axItemID : $item_details->ITEMID,
                "INVENTCOLORID" =>  $isExtraItem == true ? '' : $item_details->ColourID,
                "CONFIGID" =>  $isExtraItem == true ? '' : $item_details->CONFIGID,
                "INVENTSIZEID" =>  $isExtraItem == true ? '' : $item_details->SizeID,
                "SALESQTY" => $l->amount,
                "SALESPRICE" => $actualSalesPriceWithGst, //$l->finalPriceWithVAT,
                "LINEAMOUNT" => $l->rowTotal,
                "MODIFIEDDATETIME" => $data->lastModified,
                "MODIFIEDBY" => "ERPLY",
                "CREATEDDATETIME" => $data->added,
                "CREATEDBY" => "ERPLY",
                "DATAAREAID" => "psw",
                // "RECVERSION" => $l->data, 
                "STOREID" => @$axWHDetails->StoreID, // $storeID,
                "ENTITY" => @$axWHDetails->Return_ENTITY,
                // "INVENTTRANSID" => '',  
                "WMSLOCATIONID" => $wmsLocation, //$isAxSalesOrder == true ?  $location->WMSLOCATIONID : (@$location->issueLocation ? @$location->issueLocation : 'DEF'),
                // "SALESDELIVERNOW" => 1,
                "WAREHOUSE" => $axWHDetails->LocationID, //$isAxSalesOrder == true ? $wh->LocationID :  $location->warehouse,
                "LINEPERCENT" => $l->discount,
                // "LINEDISC" => $l->discount,
                "TRANSACTIONID" => $l->stableRowID,
                "TERMINALID" => $axWHDetails->StoreID,
                "INVOICEID" => $data->number
            );


            if ($isStandard == true) {
                $lineDetails["SALESDELIVERNOW"] = $l->amount;
            }

            // dd($lineDetails);
            if ($l->axRowID > 0) {

                $isExist = AxSalesLine::where("RECID", $l->axRowID)->first();
                if ($isExist) {
                    AxSalesLine::where("RECID", $l->axRowID)->update($lineDetails);
                    UserLogger::setChronLogNew($isExist ? json_encode($isExist, true) : '', json_encode($lineDetails, true),  "Ax Sales Lines Updated");
                }
            } else {

                $recid = $this->getRecID(50268);
                $lineDetails["RECID"] = $recid["NEXTVAL"];

                AxSalesLine::create(
                    $lineDetails
                );

                $verifyLine = AxSalesLine::where("RECID", $recid["NEXTVAL"])->first();
                if ($verifyLine) {
                    $rowCount = AxSalesLine::count();
                    $nextVal = $rowCount + $recid["NEXTVAL"];
                    $updateNextval = $this->updateRecID(50268, $nextVal);
                    if ($updateNextval == true) {
                        info("SystemSequence Table Updated");
                        $l->axRowID = $recid["NEXTVAL"];
                        $l->save();
                        UserLogger::setChronLogNew('', json_encode($verifyLine, true),  "Ax Sales Lines Created");
                    } else {

                        info("SystemSequence Table Update Failed");
                    }
                }
            }
        }

        info("*********************ALL LINE ITEMS PROCESSED, SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
    }

    //requirement
    //if layby update then new insert line in ax then added should be current date which is lastmodifed 
    //this function is specially for layby document type
    //first check this layby docuemnt is inserting first time
    private function checkDocumentTypeAndGetAddedDate($data, $isExist)
    {

        if ($data->type == "PREPAYMENT" || $data->type == "ORDER") {
            // now this is layby document 
            //now check is document inserting first time ?
            if ($isExist == true) {
                return $data->lastModified;
            }
        }

        return $data->added;
    }

    //check document exist in AX
    private function isDocumentExistInAx($data, $extra)
    {
        $isExist = false;
        if ($data->salesAxID > 0) {
            $isExistSalesOrder = AxSalesOrder::where("RECID", $data->salesAxID)->first();
            if ($isExistSalesOrder) {
                info("*********************ORDER EXIST IN AX, SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
                return ["isExist" => true, "existData" => $isExistSalesOrder];
            }
        }
        return ["isExist" => false];
        // return $isExist;
    }

    private function isDocumentExistInAxV2($data, $finalAxDocumentID)
    {
        $isExist = false;
        if ($data->salesAxID > 0) {
            $isExistSalesOrder = AxSalesOrder::where("RECID", $data->salesAxID)->first();
            if ($isExistSalesOrder) {
                info("*********************ORDER EXIST IN AX, SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
                $isExist = true;
                return ["isExist" => true, "existData" => $isExistSalesOrder];
            }

            $isDocIDExist = AxSalesOrder::where("DOCUMENTID", $finalAxDocumentID)->first();
            if ($isDocIDExist) {
                return ["isExist" => true, "existData" => $isDocIDExist];
            }
        } else {
            #check order by document id 
            $isDocIDExist = AxSalesOrder::where("DOCUMENTID", $finalAxDocumentID)->first();
            if ($isDocIDExist) {
                return ["isExist" => true, "existData" => $isDocIDExist];
            }
        }
        return ["isExist" => false];
        // return $isExist;
    }


    private function getParentDocumentTypeForSalesDocument($data, $parent, $type = "0")
    {

        $type = "0";

        // $finalCount = $count; 
        // $returnCount = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", "<=", $data->salesDocumentID)->where("baseDocumentID",  $data->baseDocumentID ? $data->baseDocumentID : 0)->count();
        // $finalCount += $returnCount;
        if ($parent->type == "PREPAYMENT") {
            $type = "1";
        }
        if ($parent->type == "CASHINVOICE") {
            $type = "0";
        }

        if (@$parent->type == "CREDITINVOICE") {
            $newParent = $this->getRecursiveSalesDocument($parent);

            if ($newParent) {
                return $this->getParentDocumentTypeForSalesDocument($parent, $newParent, $type);
            }
        }


        return $type;
    }

    //get sales order type

    private function getSalesOrderType($data, $isParent, $parentOrder)
    {
        switch ($data->type) {
            case "PREPAYMENT":
                return "1";
                // break;
            case "ORDER":
                return "2";
            case "WAYBILL":
                return "2";
            case "CASHINVOICE":
                if ($isParent == true && @$parentOrder->type == "PREPAYMENT") {
                    return "1";
                } else {
                    return "0";
                }
            case "CREDITINVOICE":
                if ($isParent == true) {
                    return $this->getParentDocumentTypeForSalesDocument($data, $parentOrder);
                    // if($parentOrder->type == "PREPAYMENT"){
                    //     return "1"; 
                    // }else if($parentOrder->type == "CASHINVOICE"){
                    //     return "0"; 
                    // }else{
                    //     return "2"; 
                    // }
                } else {
                    return "0";
                }
            default:
                return "0";
        }
    }

    private function checkCreditInvoiceContainPositiveQuantity($linesOrder)
    {
        $isCreditContainsPositiveQty = 0;
        foreach ($linesOrder as $lo) {
            if ((float)$lo->amount > 0) {
                $isCreditContainsPositiveQty = 1;
            }
        }

        return $isCreditContainsPositiveQty;
    }

    private function checkOrderContainNegativeQuantity($linesOrder)
    {
        $isOrderContainsPositiveQty = 0;
        foreach ($linesOrder as $lo) {
            if ((float)$lo->amount < 0) {
                $isOrderContainsPositiveQty = 1;
            }
        }

        return $isOrderContainsPositiveQty;
    }

    private function checkPaymentExists($data)
    {
        $isTotalZero = false;
        if ($data->total == 0 || $data->total == 0.00) {
            $isTotalZero = true;
        }

        if ($isTotalZero == false) {
            // echo "hi amount is not zero";
            // die;
            $paymentDetails = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->where("axPending", 1)->get();
            if ($paymentDetails->isEmpty()) {
                $data->paymentFlag = 1;
                $data->save();
                info("Paymnet Flag Updated to 1.");
                return 0;
            }
            return 1;
        }
        return 1;
    }

    protected function saveUpdateSalesOrders($data, $linesOrder, $isPswAxCustomer, $pswAxCustomerAccount = 0, $preFix, $isParent, $parentOrder, $isDebug, $axWHDetails)
    {

        //PSW/ACADEMY DEFAULT CUSTOMER

        $academyDC = '90010';
        $pswDC = '90011';
        $academySSRA = '20062';
        $pswSSRA = '17640';


        info("*********************READY TO PROCESS SALES DOC ID : " . $data->salesDocumentID . " ************************************************");


        //first checking lines exist or not
        if (count($linesOrder) < 1) {
            info("No sales order Lines ");
            //set flag to this
            $data->noLineFlag = 1;
            $data->save();

            info("No Sales Order Lines Found");
            die;
        }

        //first check is total amount > 0
        $isTotalZero = false;
        if ($data->total == 0 || $data->total == 0.00) {
            $isTotalZero = true;
        }

        $isCardPay = false;
        $isCashPay = false;
        $isAccountPay = false;
        $totalPaymentSum = 0;

        if ($isTotalZero == false) {
            // echo "hi amount is not zero";
            // die;
            $paymentDetails = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->where("axPending", 1)->get();
            // if($isDebug == true){
            //     $paymentDetails = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->get();
            // }


            if ($paymentDetails->isEmpty()) {
                //break the loop and wait for next 1min
                $data->paymentFlag = 1;
                $data->save();
                info("Paymnet Flag Updated to 1.");
                // echo "Payment Flag updated";
                // return response("No Payment Details Found");
                die;
            }
            // die;
            foreach ($paymentDetails as $pd) {
                $totalPaymentSum = $totalPaymentSum + $pd->sum;
                if ($pd->type == "CASH") {
                    $isCashPay = true;
                }
                if ($pd->type == "CARD") {
                    $isCardPay = true;
                }
                if ($pd->type == "TRANSFER") {
                    $isAccountPay = true;
                }
            }
        }

        $currentSynccareLocation = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();
        $axStoreDetails = LiveWarehouseLocation::where("LocationID", @$currentSynccareLocation->code)->first();

        $finalAxDocumentID = $this->makeAxDocumentID($data, $preFix, $isParent, $parentOrder, $isDebug);
        //Checking Order exist in AX
        $checkExist = $this->isDocumentExistInAx($data, $finalAxDocumentID);
        $isExist = $checkExist["isExist"];

        //here getting document created date that should be lastmodified when layby order update
        $documentAddedDate = $this->checkDocumentTypeAndGetAddedDate($data, $isExist);

        //Checking Default Customer or Other
        $POSFLAG = true;
        if ($isExist == true) {
            if ($isParent == true) {
                //now checking parent sales order isPOS  
                if ($parentOrder) {
                    $POSFLAG = $parentOrder->isPOS == 1 ? true : false;
                } else {
                    $POSFLAG = $data->isPOS == 1 ? true : false;
                }
            } else {
                $POSFLAG = $data->isPOS == 1 ? true : false;
            }
        } else {
            if ($isParent == true) {
                //now checking parent sales order isPOS  
                if ($parentOrder) {
                    $POSFLAG = $parentOrder->isPOS == 1 ? true : false;
                } else {
                    $POSFLAG = $data->isPOS == 1 ? true : false;
                }
            } else {

                if ($data->paymentStatus == "PAID" && $data->type != "ORDER") {
                    $POSFLAG = true;
                } else {
                    $POSFLAG = false;
                }
            }
        }

        $posCustomerID = 3;

        $customerSync = false;
        //first check is Payment Pending 

        if ($POSFLAG == false) {
            //now lets create user
            $isCustomerPushed = $this->customer_service->syncSingleCustomerMiddleServerToAX($data->clientID, $data, $preFix);
            if ($isCustomerPushed == true) {
                // break;
                $customerSync = true;
            }
        } else {
            //create POS Customer
            $customerSync = $this->customer_service->syncSingleCustomerMiddleServerToAX($posCustomerID, $data, $preFix);
            // if($posRes == fal)
            // dd($posRes);
        }

        if ($POSFLAG == false && $customerSync == false) {

            echo "Customer Must Sync for this Order";
            info("Synccare to Erply : Customer Must Sync for this Order");
            //update as error flag 1 so 
            $data->errorFlag = 1;
            $data->save();
            die;
            return response("Synccare to Erply : Customer Must Sync for this Order.");
            die;
        }

        //getting customer information
        $customer = Customer::where("clientCode", $data->clientCode)->where("customerID", $POSFLAG == true ? $posCustomerID : $data->clientID)->first();

        //getting school id
        $schoolID = 0;
        if (count($linesOrder) > 0) {

            foreach ($linesOrder as $schoolLine) {
                $getSchoolID = LiveProductVariation::where("ERPLYSKU", $schoolLine->code)->first();
                if ($getSchoolID) {
                    $schoolID = $getSchoolID->SchoolID;
                    break;
                }
            }
        }

        //for multiple payments
        //Now if multiple payment is used then Pushed sales order multiple times bot not Lines
        $multiPayments = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->where("axPending", 1)->orderBy('paymentID', 'asc')->get();
        if ($isDebug == true) {
            $multiPayments = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->orderBy('paymentID', 'asc')->get();
        }

        $multiPaymentFlag = $this->checkMultipaymentConditions($data, $multiPayments);

        //sales order details
        $details = array(
            // "DOCUMENTID" => "ERPLY".$data->salesDocumentID,
            // "CUSTOMERID" => "ERPLY".$data->customerID,
            "INVOICEID" => $data->number,
            "STATUS" => 1,
            "SCHOOLID" => $schoolID == 0 ? ($axStoreDetails->DefaultSchoolID ? $axStoreDetails->DefaultSchoolID : 0) : $schoolID,
            "WAREHOUSE" => $data->warehouseCode,
            "STOREID" => @$axWHDetails->StoreID,
            // "DBACTION" => 1,
            "DELIVERYNAME" => $customer->fullName,
            "MODIFIEDDATETIME" => $data->lastModified,
            "MODIFIEDBY" => "ERPLY",
            "CREATEDDATETIME" => $documentAddedDate, //$data->added, script updated
            "CREATEDBY" => "ERPLY",
            "DATAAREAID" => "psw",
            // "RECVERSION" => "",
            // "POSTINVOICE" => 1,
            "ENTITY" => @$axWHDetails->Return_ENTITY,
            "DELIVERYSTREET" => $customer->street ? $customer->street : "",
            "DELIVERYCITY" => $customer->city ? $customer->city : "",
            "DELIVERYSTATE" => $customer->state ? $customer->state : "",
            "DELIVERYZIPCODE" => $customer->postalCode ? $customer->postalCode : "",
            "DELIVERYCOUNTRYREGIONID" => "AU",
            "COMMENT_" => $data->internalNotes ? $data->internalNotes : "",
            "PHONE" => $customer->mobile ? $customer->mobile : ($customer->phone ? $customer->phone : ""),
            "EMAIL" => $customer->email ? $customer->email : '',
            // "DLVMODE" => "CC_Pickup",
            // "PAYMENTAMOUNT" => $data->paid ? $data->paid : 0,
            "TERMINALID" => $data->pointOfSaleID,
            "TRANSACTIONID" => $data->salesDocumentID,
            "DELIVERYADDRESS" => $data->shipToAddress ? $data->shipToAddress : ($customer->address ? $customer->address : ''),
            "PREPAYMENT" => 0,
            "TRANSDATE" => $data->date,
            // "TENDERTYPE" => 5,
            // "CARDTYPEID" => 1,
            "CURRENCY" => $data->currencyCode,
            // "GROSSAMOUNT" =>  $data->type == "CREDITINVOICE" ? abs($data->total) :  (0 - abs($data->total)), 
        );


        if ($multiPaymentFlag == false && $isTotalZero == false) {
            $details["PAYMENTAMOUNT"] = $multiPayments[0]["sum"];
        }

        $details["DLVMODE"] = $this->getDeliveryMode($data, $linesOrder);

        $custID = '';

        if ($preFix == "PA") {
            $custID .= "ERPLY" . $customer->customerID;
        } else {
            $custID .= "UG" . $customer->customerID;
        }

        if ($POSFLAG == true) {

            if ($isAccountPay == true) {
                // $details["CUSTOMERID"] =  $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? $axStoreDetails->DefaultCustomer  :"ERPLY".$customer->customerID);
                // $details["INVOICEACCOUNT"] = $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? $axStoreDetails->DefaultCustomer  :"ERPLY".$customer->customerID);
                $details["CUSTOMERID"] =  $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? ($preFix == "PA" ? '90010' : '90011') : $custID);
                $details["INVOICEACCOUNT"] = $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? ($preFix == "PA" ? '90010' : '90011')  : $custID);
            } else {
                // $details["CUSTOMERID"] =   $axStoreDetails->DefaultCustomer;
                // $details["INVOICEACCOUNT"] = $axStoreDetails->DefaultCustomer;
                $details["CUSTOMERID"] =   ($preFix == "PA" ? '90010' : '90011');
                $details["INVOICEACCOUNT"] = ($preFix == "PA" ? '90010' : '90011');
            }
        } else {
            // $details["CUSTOMERID"] =  $isPswAxCustomer == true ? $pswAxCustomerAccount  :  ($customer->customerID == 3 ? $axStoreDetails->DefaultCustomer  :"ERPLY".$customer->customerID);
            // $details["INVOICEACCOUNT"] = $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? $axStoreDetails->DefaultCustomer  :"ERPLY".$customer->customerID);
            $details["CUSTOMERID"] =  $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? ($preFix == "PA" ? '90010' : '90011')  : $custID);
            $details["INVOICEACCOUNT"] = $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? ($preFix == "PA" ? '90010' : '90011')  : $custID);
        }

        //setting paid amount
        $paid = 0;
        if ($data->paid > 0) {
            $paid = (float)$data->paid;
            if ($data->type == "PREPAYMENT") {
                if ($paid == 0) {
                    $paid = $paid + $data->advancePayment;
                }
            }
        }

        //for final payment, post invoice and balance
        if (abs($data->total) == abs($totalPaymentSum)) {
            $details["FINALPAYMENT"] = 1;
            $details["BALANCE"] = 0;
            $details["POSTINVOICE"] = 1;
        } else {

            if ($multiPaymentFlag == false) {

                $paidAmt = 0;
                if ($multiPayments->isNotEmpty()) {
                    $paidAmt = $multiPayments[0]["sum"];
                }

                if ($data->type == "CREDITINVOICE") {
                    $details["FINALPAYMENT"] = 0;

                    $details["BALANCE"] = abs((float)$data->total) - abs((float)$paidAmt);
                    $details["POSTINVOICE"] = 0;
                } else {
                    $details["FINALPAYMENT"] = 0;
                    $details["BALANCE"] = (float)$data->total - (float)$paidAmt;
                    $details["POSTINVOICE"] = 0;
                }
            }
        }


        //FOR SALES ORDER TYPE
        if ($data->type == "PREPAYMENT") {
            // $details["SALESORDERTYPE"] = 1;
            // $details["DOCUMENTID"] = "LB_$preFix".$data->salesDocumentID;
            $details["PREPAYMENT"] = 1;
            $details["POSTINVOICE"] = 0;
            $details["INVOICEID"] = $data->number;
        }

        $isLayByFinalPayment = 0;

        if ($data->type == "CASHINVOICE") {
            if ($isParent == true) {
                if ($parentOrder->type == "PREPAYMENT") {
                    // $details["SALESORDERTYPE"] = 1; 
                    $isLayByFinalPayment = 1;
                }
            } else {
                // $details["SALESORDERTYPE"] = 0;
                // $details["DOCUMENTID"] = "$preFix".$data->salesDocumentID;
                $details["POSTINVOICE"] = 1;
                $details["INVOICEID"] = $data->number;
            }
        }

        $isCreditContainsPositiveQty = 0;

        // $isOrderContainsNegativeQ
        if ($data->type == "CREDITINVOICE") {

            $details["POSTINVOICE"] = 1;
            $details["RETURNED"] = 1;
            $details["INVOICEID"] = $data->number;
            $details["FINALPAYMENT"] = 1;

            $isCreditContainsPositiveQty = $this->checkCreditInvoiceContainPositiveQuantity($linesOrder);


            //counting return orders
            /**
             * logic
             * @if multiple sales returns
             * @the document id shouldn't be same 
             * @so count returns orders 
             * @eg.
             * @ C_PREFIX_BasedocumentID_1 , _2 , _3 ,
             * 
             * if return order then inserting base doc id to baseDocumentID columns 
             * so next time count according to base document to get _# value
             */

            // code updated to seperate function
            // if($isParent == true){
            //     if($parentOrder->type == "PREPAYMENT"){
            //         $details["SALESORDERTYPE"] = 1; 
            //     }else if($parentOrder->type == "CASHINVOICE"){
            //         $details["SALESORDERTYPE"] = 0; 
            //     }else{
            //         $details["SALESORDERTYPE"] = 2; 
            //     }
            // }else{
            //     $details["SALESORDERTYPE"] = 0; 
            // } 

            //checking if return contains positive qty 

            //Code shift to seperate function
            // foreach($linesOrder as $lo){
            //     if($lo->amount > 0){
            //         $isCreditContainsPositiveQty = 1;
            //     }
            // }


        }

        //if order contains negative qty set returned flag value to 1
        $isOrderContainsNegativeQty = $this->checkOrderContainNegativeQuantity($linesOrder);
        if ($isOrderContainsNegativeQty == 1) {
            $details["RETURNED"] = 1;
        }


        if ($data->type == "ORDER" || $data->type == "WAYBILL") {
            $details["POSTINVOICE"] = 0;
        }

        //getting sales order type for ax
        $details["SALESORDERTYPE"] = $this->getSalesOrderType($data, $isParent, $parentOrder);

        //preparing document id for ax
        $details["DOCUMENTID"] = $finalAxDocumentID;


        //for db action
        if ($isExist == false) $details["DBACTION"] = 1;
        if ($isExist == true) $details["DBACTION"] = 2;

        //scenario if layby final payment then dbaction should be 2 only for prepaymen
        if ($data->type == "CASHINVOICE" && $isParent == true && $parentOrder->type == "PREPAYMENT") {
            $details["DBACTION"] = 2;
            $isExist = true;
        }

        if ($isLayByFinalPayment == 1) {
            $details["FINALPAYMENT"] = 1;
        }

        $grossAmt = 0;

        if ($data->type == "CREDITINVOICE" && (float)$data->total < 0) {
            $grossAmt = abs($data->total);
        } else {
            $grossAmt = (0 - abs($data->total));
            //if layby final payment then grossamt should be remaining amount 
            // if($isLayByFinalPayment){
            //     $grossAmt = $parentOrder->total - $parentOrder->paid;
            // }
        }
        $details["GROSSAMOUNT"] = $grossAmt;
        $details["POSTINVOICE"] = $this->getPostInvoice($data);

        if ($multiPaymentFlag == true) {
            info("*********************MULTI PAYMENT PROCESS SALES DOC ID : " . $data->salesDocumentID . " ************************************************");

            $voucherCount = 1;

            $isMultilineDone = 1;
            $lastRecID = 0;
            $balanceAmt = $grossAmt;
            foreach ($multiPayments as $key => $mp) {
                //need to change paymentAmount according to payment types and tender types
                $details["PAYMENTAMOUNT"] = $mp->sum;
                // $details["SALESORDERTYPE"] = 1;
                $details["POSTINVOICE"] = 0;

                $details["PREPAYMENT"] = 1;

                $details["FINALPAYMENT"] = 0;
                if ($multiPayments->last() == $mp) {

                    //at last set final payment 1
                    // $details["SALESORDERTYPE"] = 1;  
                    $details["PREPAYMENT"] = 1;

                    //“Final Payment” and “PostInvoice” have been ticked – these two options should only be ticked when the order is finalised, and there is no outstanding balance on the transactions

                    // if($data->type != "ORDER" && $data->type != "PREPAYMENT"){
                    //     //Parent Orders cannot have PostInvoice ticked – as they are backorders that are awaiting fulfilment.  
                    //     $details["POSTINVOICE"] = 1;
                    // }
                    //getting Postinvoice
                    $details["POSTINVOICE"] = $this->getPostInvoice($data);


                    if ($data->type != "PREPAYMENT") {
                        $details["FINALPAYMENT"] = 1;
                    }
                }

                if ($isCreditContainsPositiveQty == 1) {
                    //only for credit invoice multipayments
                    $details["PREPAYMENT"] = 0;
                }

                $details["TENDERTYPE"] = $this->getTenderType($mp->type);
                if ($this->getTenderType($mp->type) == "5") {
                    $details["CARDTYPEID"] = "1";
                } else {
                    if (@$details["CARDTYPEID"]) {
                        unset($details["CARDTYPEID"]);
                    }
                }

                $details["GROSSAMOUNT"] = $balanceAmt;

                if ($data->type == "CREDITINVOICE" && (float)$data->total < 0) {

                    $balanceAmt = $balanceAmt - abs((float)$mp->sum);
                    $details["BALANCE"] = round(abs($balanceAmt), 7);
                } else {
                    $balanceAmt = $balanceAmt + $mp->sum;
                    $details["BALANCE"] = round(abs($balanceAmt), 7);
                }

                //for handling vourcher payments 
                if ($mp->type == "ACCOUNT" || $mp->type == "ACCOUNTS" || $mp->type == "SSR") {

                    $details["VOUCHER"] = 1;

                    if ($mp->type == "ACCOUNT" || $mp->type == "ACCOUNTS") {
                        //if($isPswAxCustomer == true)
                        $details["VOUCHACCOUNT"] = $this->getVoucherRef($data->notes, "ACCOUNT"); //strval($pswAxCustomerAccount);
                        $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, "ACCOUNTS");
                    } else {
                        if (@$details["VOUCHACCOUNT"]) {
                            unset($details["VOUCHACCOUNT"]);
                        }
                        $schoolAc = $preFix == "PA" ? '20062' : '17640';
                        $details["VOUCHACCOUNT"] =  $schoolAc;
                        $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, $mp->type);
                    }
                    $details["VOUCHERINVOICEID"] = 'V' . $data->number . "_" . $voucherCount;


                    //for voucher references


                } else {
                    if (@$details["VOUCHERREF"]) {
                        unset($details["VOUCHERREF"]);
                    }
                    if (@$details["VOUCHERINVOICEID"]) {
                        unset($details["VOUCHERINVOICEID"]);
                    }
                    if (@$details["VOUCHACCOUNT"]) {
                        unset($details["VOUCHACCOUNT"]);
                    }
                    if (@$details["VOUCHER"]) {
                        unset($details["VOUCHER"]);
                    }
                }
                $voucherCount++;

                //now handing invoice id
                $details["INVOICEID"] = $data->number;
                $details["DBACTION"] = $isExist == true ? 2 : 1;
                if ($key > 0) {
                    $details["INVOICEID"] = $data->number . "_" . $key;
                    $details["DBACTION"] = 2;
                }
                //for RECID
                $recid = $this->confirmRecID($data);
                $details["RECID"] = $recid["NEXTVAL"];

                try {

                    if ($isDebug == true) {
                        dd($details);
                    }

                    AxSalesOrder::create(
                        $details
                    );

                    //now checking sales order created or not
                    $verifyOrder = AxSalesOrder::where("RECID", $recid["NEXTVAL"])->first();
                    if ($verifyOrder) {
                        $rowCount = AxSalesOrder::count();
                        $nextVal = $rowCount + $recid["NEXTVAL"];
                        $updateNextval = $this->updateRecID(50267, $nextVal);
                        if ($updateNextval == true) {
                            info("SystemSequence Table Updated");
                            $lastRecID = $recid["NEXTVAL"];

                            UserLogger::setChronLogNew($isExist == true ? json_encode($checkExist["existData"], true) : '', json_encode($verifyOrder, true), $isExist == true ? "Ax Sales Document Updated" :  "Ax Sales Document Created");
                            info("*********************SALES ORDER CREATED TO AX, MODE : MULTIPAYMENT, ID : " . $data->salesDocumentID . " ************************************************");
                        } else {

                            info("SystemSequence Table Update Failed");
                        }
                    }
                } catch (Exception $e) {
                    info("*********************ERROR WHILE SAVING MULTIPAYMENT ORDER OR LINES, SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
                    info($details);
                    $isMultilineDone = 0;
                    //if errror set this as error
                    SalesDocument::where("id", $data->id)->update(["errorFlag" => 1, "errorMsg" => $e->getMessage()]);
                    info($e);
                }
            }

            if ($isMultilineDone == 1) {
                $data->salesAxID = $lastRecID;
                $data->axPending = 0;
                if ($isExist == false) {
                    $data->isPOS = $POSFLAG == true ? 1 : 0;
                }
                $data->save();
                //updating flag of payment
                Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->update(["axPending" => 0]);

                //saving order lines
                $this->saveLineItems($data, $linesOrder, $preFix, $isParent, $parentOrder, $axWHDetails);
            }
        } else {
            info("*********************SNGLE PAYMENT PROCESS SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
            if ($isTotalZero == false) {
                $singlePayment = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->where("axPending", 1)->first();
                if ($isDebug == true) {
                    $singlePayment = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->first();
                }
                $details["TENDERTYPE"] = $this->getTenderType($singlePayment->type);
                if ($this->getTenderType($singlePayment->type) == "5") {
                    $details["CARDTYPEID"] = "1";
                }

                if ($singlePayment->type == "ACCOUNT" || $singlePayment->type == "ACCOUNTS" || $singlePayment->type == "SSR") {

                    $details["VOUCHER"] = 1;
                    if ($singlePayment->type == "ACCOUNT" || $singlePayment->type == "ACCOUNTS") {
                        //if($isPswAxCustomer == true)
                        $details["VOUCHACCOUNT"] = $this->getVoucherRef($data->notes, "ACCOUNT"); //strval($pswAxCustomerAccount);
                        $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, "ACCOUNTS");
                    } else {
                        if (@$details["VOUCHACCOUNT"]) {
                            unset($details["VOUCHACCOUNT"]);
                        }
                        // $schoolAc = '20062';
                        $schoolAc = $preFix == "PA" ? '20062' : '17640';
                        $details["VOUCHACCOUNT"] =  $schoolAc;
                        $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, $singlePayment->type);
                    }
                    $details["VOUCHERINVOICEID"] = 'V' . $data->number . "_1";
                }
            }


            /**
             * @according to Sarah : AX DEV
             * for all single payment and order type : ORDER
             * we are sending prepayment as 1
             */

            if ($data->type == "ORDER") {
                $details["PREPAYMENT"] = 1;
            }

            //if sales order amt = 0 than default tender type will be 1
            if ($isTotalZero == true) {
                $details["TENDERTYPE"] = "01";
                $details["PAYMENTAMOUNT"] = 0;
                $details["BALANCE"] = 0;
            }

            $recid = $this->confirmRecID($data);
            $details["RECID"] = $recid["NEXTVAL"];

            try {

                if ($isDebug == true) {
                    dd($details);
                }

                AxSalesOrder::create(
                    $details
                );

                //now checking sales order created or not
                $verifyOrder = AxSalesOrder::where("RECID", $recid["NEXTVAL"])->first();
                if ($verifyOrder) {
                    $rowCount = AxSalesOrder::count();
                    $nextVal = $rowCount + $recid["NEXTVAL"];
                    $updateNextval = $this->updateRecID(50267, $nextVal);
                    if ($updateNextval == true) {
                        info("SystemSequence Table Updated");
                        $data->salesAxID = $recid["NEXTVAL"];
                        $data->axPending = 0;
                        if ($isExist == false) {
                            $data->isPOS = $POSFLAG == true ? 1 : 0;
                        }
                        $data->save();

                        //updating flag of payment
                        Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->update(["axPending" => 0]);

                        info("*********************SALES ORDER CREATED TO AX, SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
                        //now saving line items
                        $this->saveLineItems($data, $linesOrder, $preFix, $isParent, $parentOrder, $axWHDetails);
                        UserLogger::setChronLogNew($isExist == true ? json_encode(@$checkExist["existData"], true) : '', json_encode($verifyOrder, true), $isExist == true ? "Ax Sales Document Updated" :  "Ax Sales Document Created");
                    } else {

                        info("SystemSequence Table Update Failed");
                    }
                }
            } catch (Exception $e) {
                info("*********************ERROR WHILE CREATING SALES DOC OR LINE ITEMS, ID: " . $data->salesDocumentID . " ************************************************");
                //if errror set this as error
                SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->update(["errorFlag" => 1]);
                info($details);
                info($e);
            }
        }
    }


    //check POS Flag for customer sync
    private function checkPOSFlag($data, $isExist, $isParent, $parentOrder)
    {

        if ($isExist == true) {
            if ($isParent == true) {
                //now checking parent sales order isPOS  
                if ($parentOrder) {
                    $POSFLAG = $parentOrder->isPOS == 1 ? true : false;
                } else {
                    $POSFLAG = $data->isPOS == 1 ? true : false;
                }
            } else {
                $POSFLAG = $data->isPOS == 1 ? true : false;
            }
        } else {
            if ($isParent == true) {
                //now checking parent sales order isPOS  
                if ($parentOrder) {
                    $POSFLAG = $parentOrder->isPOS == 1 ? true : false;
                } else {
                    $POSFLAG = $data->isPOS == 1 ? true : false;
                }
            } else {

                if ($data->paymentStatus == "PAID" && $data->type != "ORDER") {
                    $POSFLAG = true;
                } else {
                    $POSFLAG = false;
                }
            }
        }

        return $POSFLAG;
    }

    //get ax school id of order 
    private function getAxSchoolID($linesOrder)
    {
        $schoolID = 0;
        if (count($linesOrder) > 0) {

            foreach ($linesOrder as $schoolLine) {
                $getSchoolID = LiveProductVariation::where("ERPLYSKU", $schoolLine->code)->first();
                if ($getSchoolID) {
                    $schoolID = $getSchoolID->SchoolID;
                    break;
                }
            }
        }

        return $schoolID;
    }

    private function getAxSchoolIDV3($order)
    {
        $schoolID = 0;
        $lineOrders = SalesDocumentDetail::where("clientCode", $order->clientCode)->where("salesDocumentID", $order->salesDocumentID)->where("code", 'like', '%SOFT%')->get();
        if (count($lineOrders) > 0) {
            foreach ($lineOrders as $schoolLine) {
                $getSchoolID = LiveProductVariation::where("ERPLYSKU", $schoolLine->code)->first();
                if ($getSchoolID) {
                    $schoolID = $getSchoolID->SchoolID;
                    break;
                }
            }
        }

        return $schoolID;
    }

    private function getAxSchoolIDV2($linesOrders, $order)
    {
        $schoolID = 0;
        $linesOrder = SalesDocumentDetail::where("clientCode", $order->clientCode)->where("salesDocumentID", $order->salesDocumentID)->where("code", 'like', '%SOFT%')->get();
        if (count($linesOrder) > 0) {
            $softList = [];
            foreach ($linesOrder as $schoolLine) {
                $pattern = '/SOFT\d+/';
                preg_match($pattern, $schoolLine->code, $matches);
                if (@$matches[0]) {
                    $softList[] = $matches[0];
                }
            }

            if (count($softList) > 0) {
                $softList = array_unique($softList);
                $amtList = [];
                foreach ($softList as $sl) {
                    $lineInfo = SalesDocumentDetail::where("clientCode", $order->clientCode)
                        ->where("salesDocumentID", $order->salesDocumentID)
                        ->where("code", 'like', '%' . $sl)
                        ->first();
                    $totalAmts = SalesDocumentDetail::where("clientCode", $order->clientCode)
                        ->where("salesDocumentID", $order->salesDocumentID)
                        ->where("code", 'like', '%' . $sl)
                        ->get();
                    $totalAmt = 0;
                    foreach ($totalAmts as $tamt) {
                        $totalAmt += abs($tamt->rowTotal);
                    }
                    $amtList[] = ["code" => $lineInfo->code, "totalAmt" => $totalAmt];
                }

                #Getting Maximum Amt Line 
                $maxAmt = 0;
                $finalLineCode = '';
                foreach ($amtList as $al) {
                    if ($al["totalAmt"] > $maxAmt) {
                        $maxAmt = $al["totalAmt"];
                        $finalLineCode = $al["code"];
                    }
                }

                #Now Getting 
                $getSchoolID = LiveProductVariation::where("ERPLYSKU", $finalLineCode)->first();
                // dd($softList, $amtList, $finalLineCode, $getSchoolID);
                if ($getSchoolID) {
                    $schoolID = $getSchoolID->SchoolID;
                    return $schoolID;
                }
            }
        }
        // dd($schoolID);
        return $schoolID;
    }

    //check multipayment conditions
    private function checkMultipaymentConditions($data, $multiPayments)
    {

        $paymentLine = count($multiPayments);
        if ($paymentLine < 2) {
            return false;
        }
        if ($data->type == "CASHINVOICE" || $data->type == "PREPAYMENT" || $data->type == "CREDITINVOICE" || $data->type == "ORDER") {
            return true;
        }

        // if($data->type == "PREPAYMENT" && count($multiPayments) > 1){
        //     return true; 
        // }
        // if($data->type == "CREDITINVOICE" && count($multiPayments) > 1){
        //     return true; 
        // }
        // if($data->type == "ORDER" && count($multiPayments) > 1){
        //     return true; 
        // }
        return false;
    }

    protected function saveUpdateSalesOrdersWithCancelled($data, $linesOrder, $isPswAxCustomer, $pswAxCustomerAccount = 0, $preFix, $isParent, $parentOrder, $isDebug, $axWHDetails, $isCancelled)
    {
        info("*********************READY TO PROCESS SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
        //first checking lines exist or not
        if (count($linesOrder) < 1) {
            info("No sales order Lines ");
            //set flag to this
            $data->noLineFlag = 1;
            $data->save();

            info("No Sales Order Lines Found");
            die;
            return response("No Sales Order Lines Found");
            die;
        }

        //first check is total amount > 0
        $isTotalZero = false;
        if ($data->total == 0 || $data->total == 0.00) {
            $isTotalZero = true;
        }

        $isCardPay = false;
        $isCashPay = false;
        $isAccountPay = false;
        $totalPaymentSum = 0;
        // dd($data);
        // die;
        if ($isTotalZero == false) {
            // echo "hi amount is not zero";
            // die;
            $paymentDetails = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->where("axPending", 1)->get();
            // if($isDebug == true){
            //     $paymentDetails = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->get();
            // } 
            if ($paymentDetails->isEmpty()) {
                //break the loop and wait for next 1min
                $data->paymentFlag = 1;
                $data->save();
                info("Paymnet Flag Updated to 1.");
                // echo "Payment Flag updated";
                // return response("No Payment Details Found");
                die;
            }

            foreach ($paymentDetails as $pd) {
                $totalPaymentSum = $totalPaymentSum + $pd->sum;
                if ($pd->type == "CASH") {
                    $isCashPay = true;
                }
                if ($pd->type == "CARD") {
                    $isCardPay = true;
                }
                if ($pd->type == "TRANSFER") {
                    $isAccountPay = true;
                }
            }
        }

        $currentSynccareLocation = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();
        $axStoreDetails = LiveWarehouseLocation::where("LocationID", @$currentSynccareLocation->code)->first();

        $finalAxDocumentID = $this->makeAxDocumentID($data, $preFix, $isParent, $parentOrder, $isDebug);;
        //Checking Order exist in AX
        $checkExist = $this->isDocumentExistInAx($data, $finalAxDocumentID);
        // if($isDebug == true){
        //     dd($checkExist);
        // }
        $isExist = $checkExist["isExist"];

        //here getting document created date that should be lastmodified when layby order update
        $documentAddedDate = $this->checkDocumentTypeAndGetAddedDate($data, $isExist, $isCancelled);

        //Checking Default Customer or Other
        // $POSFLAG = true;
        $POSFLAG = $this->checkPOSFlag($data, $isExist, $isParent, $parentOrder);

        $posCustomerID = 3;
        $customerSync = false;

        if ($data->clientID == 0) {
            $POSFLAG = true;
        }
        //first check is Payment Pending 
        if ($POSFLAG == false) {
            //now lets create user
            $isCustomerPushed = $this->customer_service->syncSingleCustomerMiddleServerToAX($data->clientID, $data, $preFix);
            if ($isCustomerPushed == true) {
                $customerSync = true;
            }
        } else {
            //create POS Customer
            $customerSync = $this->customer_service->syncSingleCustomerMiddleServerToAX($posCustomerID, $data, $preFix);
        }

        if ($POSFLAG == false && $customerSync == false) {

            echo "Customer Must Sync for this Order";
            //update as error flag 1 so 
            SalesDocument::where("id", $data->id)->update(["errorFlag" => 1]);
            // $data->errorFlag = 1;
            // $data->save();
            die;
            return response("Synccare to Erply : Customer Must Sync for this Order.");
            die;
        }



        //getting customer information
        $customer = Customer::where("clientCode", $data->clientCode)->where("customerID", $POSFLAG == true ? $posCustomerID : $data->clientID)->first();

        //getting school id of order
        // $schoolID = 0;
        $schoolID = $this->getAxSchoolIDV2($linesOrder, $data);
        // echo $schoolID == 0 ? ($axStoreDetails->DefaultSchoolID ? $axStoreDetails->DefaultSchoolID : 0) : $schoolID;
        // echo (double)$schoolID > 0 ? $schoolID : ($axStoreDetails->DefaultSchoolID ? $axStoreDetails->DefaultSchoolID : 0);
        // die;

        // $schoolID = $this->getAxSchoolID($linesOrder);
        // $schoolID = $this->getAxSchoolIDV3($data);

        //for multiple payments
        //Now if multiple payment is used then Pushed sales order multiple times bot not Lines
        $multiPayments = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->where("axPending", 1)->orderBy('paymentID', 'asc')->get();
        // if($isDebug == true){
        //     $multiPayments = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->orderBy('paymentID', 'asc')->get();
        // }

        // $multiPaymentFlag = false;
        //checking multiple payment conditions
        $multiPaymentFlag = $this->checkMultipaymentConditions($data, $multiPayments);

        //sales order details
        $details = array(
            // "DOCUMENTID" => "ERPLY".$data->salesDocumentID,
            // "CUSTOMERID" => "ERPLY".$data->customerID,
            "INVOICEID" => $data->number,
            "STATUS" => 1,
            "SCHOOLID" => (float)$schoolID > 0 ? $schoolID : ($axStoreDetails->DefaultSchoolID ? $axStoreDetails->DefaultSchoolID : 0),
            "WAREHOUSE" => $data->warehouseCode,
            "STOREID" => @$axWHDetails->StoreID,
            // "DBACTION" => 1,
            "DELIVERYNAME" => $customer->fullName,
            "MODIFIEDDATETIME" => $data->lastModified,
            "MODIFIEDBY" => "ERPLY",
            "CREATEDDATETIME" => $documentAddedDate, //$data->added, script updated
            "CREATEDBY" => "ERPLY",
            "DATAAREAID" => "psw",
            // "RECVERSION" => "",
            // "POSTINVOICE" => 1,
            "ENTITY" => @$axWHDetails->Return_ENTITY,
            "DELIVERYSTREET" => $customer->street ? $customer->street : "",
            "DELIVERYCITY" => $customer->city ? $customer->city : "",
            "DELIVERYSTATE" => $customer->state ? $customer->state : "",
            "DELIVERYZIPCODE" => $customer->postalCode ? $customer->postalCode : "",
            "DELIVERYCOUNTRYREGIONID" => "AU",
            "COMMENT_" => $data->internalNotes ? $data->internalNotes : "",
            "PHONE" => $customer->mobile ? $customer->mobile : ($customer->phone ? $customer->phone : ""),
            "EMAIL" => $customer->email ? $customer->email : '',
            // "DLVMODE" => "CC_Pickup",
            // "PAYMENTAMOUNT" => $data->paid ? $data->paid : 0,
            "TERMINALID" => $data->pointOfSaleID,
            "TRANSACTIONID" => $data->salesDocumentID,
            "DELIVERYADDRESS" => $data->shipToAddress ? $data->shipToAddress : ($customer->address ? $customer->address : ''),
            "PREPAYMENT" => 0,
            "TRANSDATE" => $isCancelled == 1 ? $documentAddedDate : $data->date,
            // "TENDERTYPE" => 5,
            // "CARDTYPEID" => 1,
            "CURRENCY" => $data->currencyCode,
            // "GROSSAMOUNT" =>  $data->type == "CREDITINVOICE" ? abs($data->total) :  (0 - abs($data->total)), 
        );

        // if($isTotalZero )
        if ($multiPaymentFlag == false && $isTotalZero == false) {
            $details["PAYMENTAMOUNT"] = $multiPayments[0]["sum"];
        }

        //for delivery mode
        $details["DLVMODE"] = $this->getDeliveryMode($data, $linesOrder);


        //check customer default or other 
        $custID = '';
        if ($preFix == "PA") {
            $custID .= "ERPLY" . $customer->customerID;
        } else {
            $custID .= "UG" . $customer->customerID;
        }

        if ($POSFLAG == true) {
            if ($isAccountPay == true) {
                // $details["CUSTOMERID"] =  $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? $axStoreDetails->DefaultCustomer  :"ERPLY".$customer->customerID);
                // $details["INVOICEACCOUNT"] = $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? $axStoreDetails->DefaultCustomer  :"ERPLY".$customer->customerID);
                $details["CUSTOMERID"] =  $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? ($preFix == "PA" ? '90010' : '90011') : $custID);
                $details["INVOICEACCOUNT"] = $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? ($preFix == "PA" ? '90010' : '90011')  : $custID);
            } else {
                // $details["CUSTOMERID"] =   $axStoreDetails->DefaultCustomer;
                // $details["INVOICEACCOUNT"] = $axStoreDetails->DefaultCustomer;
                $details["CUSTOMERID"] =   ($preFix == "PA" ? '90010' : '90011');
                $details["INVOICEACCOUNT"] = ($preFix == "PA" ? '90010' : '90011');
            }
        } else {
            // $details["CUSTOMERID"] =  $isPswAxCustomer == true ? $pswAxCustomerAccount  :  ($customer->customerID == 3 ? $axStoreDetails->DefaultCustomer  :"ERPLY".$customer->customerID);
            // $details["INVOICEACCOUNT"] = $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? $axStoreDetails->DefaultCustomer  :"ERPLY".$customer->customerID);
            $details["CUSTOMERID"] =  $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? ($preFix == "PA" ? '90010' : '90011')  : $custID);
            $details["INVOICEACCOUNT"] = $isPswAxCustomer == true ? $pswAxCustomerAccount  : ($customer->customerID == 3 ? ($preFix == "PA" ? '90010' : '90011')  : $custID);
        }

        if ($isPswAxCustomer == true) {
            $details["CUSTOMERID"] = $pswAxCustomerAccount;
            $details["INVOICEACCOUNT"] = $pswAxCustomerAccount;
        }

        //getting paid amount
        $paid = 0;
        if ($data->paid > 0) {
            $paid = (float)$data->paid;
            if ($data->type == "PREPAYMENT") {
                if ($paid == 0) {
                    $paid = $paid + $data->advancePayment;
                }
            }
        }

        //for final payment, post invoice and balance
        if (abs($data->total) == abs($totalPaymentSum)) {
            $details["FINALPAYMENT"] = 1;
            $details["BALANCE"] = 0;
            $details["POSTINVOICE"] = 1;
        } else {

            if ($multiPaymentFlag == false) {

                $paidAmt = 0;
                if ($multiPayments->isNotEmpty()) {
                    $paidAmt = $multiPayments[0]["sum"];
                }

                if ($data->type == "CREDITINVOICE") {
                    $details["FINALPAYMENT"] = 0;

                    $details["BALANCE"] = abs((float)$data->total) - abs((float)$paidAmt);
                    $details["POSTINVOICE"] = 0;
                } else {
                    $details["FINALPAYMENT"] = 0;
                    $details["BALANCE"] = (float)$data->total - (float)$paidAmt;
                    $details["POSTINVOICE"] = 0;
                }
            }
        }


        //FOR SALES ORDER TYPE
        if ($data->type == "PREPAYMENT") {
            // $details["SALESORDERTYPE"] = 1;
            // $details["DOCUMENTID"] = "LB_$preFix".$data->salesDocumentID;
            $details["PREPAYMENT"] = 1;
            $details["POSTINVOICE"] = 0;
            $details["INVOICEID"] = $data->number;
        }

        $isLayByFinalPayment = 0;

        if ($data->type == "CASHINVOICE") {
            if ($isParent == true) {
                if ($parentOrder->type == "PREPAYMENT") {
                    // $details["SALESORDERTYPE"] = 1; 
                    $isLayByFinalPayment = 1;
                }
            } else {
                // $details["SALESORDERTYPE"] = 0;
                // $details["DOCUMENTID"] = "$preFix".$data->salesDocumentID;
                $details["POSTINVOICE"] = 1;
                $details["INVOICEID"] = $data->number;
            }
        }

        $isCreditContainsPositiveQty = 0;

        if ($data->type == "CREDITINVOICE") {

            $details["POSTINVOICE"] = 1;
            $details["RETURNED"] = 1;
            $details["INVOICEID"] = $data->number;
            $details["FINALPAYMENT"] = 1;

            $isCreditContainsPositiveQty = $this->checkCreditInvoiceContainPositiveQuantity($linesOrder);

            //counting return orders
            /**
             * logic
             * @if multiple sales returns
             * @the document id shouldn't be same 
             * @so count returns orders 
             * @eg.
             * @ C_PREFIX_BasedocumentID_1 , _2 , _3 ,
             * 
             * if return order then inserting base doc id to baseDocumentID columns 
             * so next time count according to base document to get _# value
             */

            // if($isParent == true){
            //     if($parentOrder->type == "PREPAYMENT"){
            //         $details["SALESORDERTYPE"] = 1; 
            //     }else if($parentOrder->type == "CASHINVOICE"){
            //         $details["SALESORDERTYPE"] = 0; 
            //     }else{
            //         $details["SALESORDERTYPE"] = 2; 
            //     }
            // }else{
            //     $details["SALESORDERTYPE"] = 0; 
            // } 

            //checking if return contains positive qty

            // foreach($linesOrder as $lo){
            //     if($lo->amount > 0){
            //         $isCreditContainsPositiveQty = 1;
            //     }
            // }


        }

        //if order contains negative qty set returned flag value to 1
        $isOrderContainsNegativeQty = $this->checkOrderContainNegativeQuantity($linesOrder);
        if ($isOrderContainsNegativeQty == 1) {
            $details["RETURNED"] = 1;
        }

        //getting sales order type for ax
        $details["SALESORDERTYPE"] = $this->getSalesOrderType($data, $isParent, $parentOrder);

        //getting AX Document ID
        $details["DOCUMENTID"] = $finalAxDocumentID;

        //for db action
        if ($isExist == false) $details["DBACTION"] = 1;
        if ($isExist == true) $details["DBACTION"] = 2;

        //scenario if layby final payment then dbaction should be 2 only for prepaymen
        $isParentPrepayment = 0;
        if ($data->type == "CASHINVOICE" && $isParent == true && $parentOrder->type == "PREPAYMENT") {
            $details["DBACTION"] = 2;
            $isExist = true;
            $isParentPrepayment = 1;
        }

        //if creditinvoice is positve then gross amount in ax should be negative
        // if($data->type == "CREDITINVOICE" && $data->total < 0){
        //     $grossAmt = abs($data->total);
        // }else{
        //     $grossAmt = (0 - abs($data->total));
        //     //if layby final payment then grossamt should be remaining amount 
        //     // if($isLayByFinalPayment){
        //     //     $grossAmt = $parentOrder->total - $parentOrder->paid;
        //     // }
        // }
        //getting gross amount
        $grossAmt = 0;

        $grossAmt = $this->getGrossAmount($data, $isCancelled, $isParent, $parentOrder);

        if ($isLayByFinalPayment == 1) {
            $details["FINALPAYMENT"] = 1;
        }

        $details["GROSSAMOUNT"] = $grossAmt;
        $details["POSTINVOICE"] = $this->getPostInvoice($data);



        if ($isDebug == 2) {
            dd($details, $multiPaymentFlag, $multiPayments);
        }
        //getting final payment
        $details["FINALPAYMENT"] = $this->getFinalPayment($data, $isCancelled);

        if ($multiPaymentFlag == true) {
            info("*********************MULTI PAYMENT PROCESS SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
            $voucherCount = 1;
            // $grossAmt = $data->type == "CREDITINVOICE" ? abs($data->total) :  (0 - abs($data->total));  
            // $multiPayments = Payment::where("documentID", $data->salesDocumentID)->where("axPending", 1)->where('type', 'SSR')->get();
            $isMultilineDone = 1;
            $lastRecID = 0;
            $balanceAmt = $grossAmt;
            foreach ($multiPayments as $key => $mp) {
                //need to change paymentAmount according to payment types and tender types
                $details["PAYMENTAMOUNT"] = $mp->sum;
                // $details["SALESORDERTYPE"] = 1;
                $details["POSTINVOICE"] = 0;

                $details["PREPAYMENT"] = 1;

                $details["FINALPAYMENT"] = 0;
                if ($multiPayments->last() == $mp) {

                    $details["PREPAYMENT"] = 1;

                    //“Final Payment” and “PostInvoice” have been ticked – these two options should only be ticked when the order is finalised, and there is no outstanding balance on the transactions
                    // if($data->type != "ORDER" && $data->type != "PREPAYMENT"){
                    //     //Parent Orders cannot have PostInvoice ticked – as they are backorders that are awaiting fulfilment.  
                    //     $details["POSTINVOICE"] = 1;
                    // }  

                    //getting Postinvoice
                    $details["POSTINVOICE"] = $this->getPostInvoice($data);

                    //getting Final Payment
                    $details["FINALPAYMENT"] = $this->getFinalPayment($data, $isCancelled);

                    // if($data->type != "PREPAYMENT"){ 
                    //     $details["FINALPAYMENT"] = 1;
                    // }
                }

                if ($isCreditContainsPositiveQty == 1) {
                    //only for credit invoice multipayments
                    $details["PREPAYMENT"] = 0;
                }

                $details["TENDERTYPE"] = $this->getTenderType($mp->type);
                if ($this->getTenderType($mp->type) == "5") {
                    $details["CARDTYPEID"] = "1";
                } else {
                    if (@$details["CARDTYPEID"]) {
                        unset($details["CARDTYPEID"]);
                    }
                }


                $details["GROSSAMOUNT"] = $balanceAmt;
                if ($data->type == "CREDITINVOICE" && $data->total < 0) {

                    $balanceAmt = $balanceAmt - abs((float)$mp->sum);
                    $details["BALANCE"] = round(abs($balanceAmt), 7);
                } else {
                    $balanceAmt = $balanceAmt + $mp->sum;
                    $details["BALANCE"] = round(abs($balanceAmt), 7);
                }

                // if($multiPayments->last() == $mp){
                //     $details["BALANCE"] = 0;
                // }

                if ($mp->type == "ACCOUNT" || $mp->type == "ACCOUNTS" || $mp->type == "SSR" || $mp->type == "SS_BONUS") {

                    $details["VOUCHER"] = 1;
                    if ($mp->type == "ACCOUNT" || $mp->type == "ACCOUNTS" || $mp->type == "SS_BONUS") {
                        //if($isPswAxCustomer == true)
                        $details["VOUCHACCOUNT"] = $this->getVoucherRef($data->notes, "ACCOUNT");  
                        $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, "ACCOUNTS");
                        if($mp->type == "SS_BONUS"){
                            $details["VOUCHACCOUNT"] = '20206';
                            $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, $mp->type);
                        }
                    } else {
                        if (@$details["VOUCHACCOUNT"]) {
                            unset($details["VOUCHACCOUNT"]);
                        }
                        $schoolAc = $preFix == "PA" ? '20062' : '17640';
                        $details["VOUCHACCOUNT"] =  $schoolAc;
                        $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, $mp->type);
                    }
                    $details["VOUCHERINVOICEID"] = 'V' . $data->number . "_" . $voucherCount;
                } else {
                    if (@$details["VOUCHERREF"]) {
                        unset($details["VOUCHERREF"]);
                    }
                    if (@$details["VOUCHERINVOICEID"]) {
                        unset($details["VOUCHERINVOICEID"]);
                    }
                    if (@$details["VOUCHACCOUNT"]) {
                        unset($details["VOUCHACCOUNT"]);
                    }
                    if (@$details["VOUCHER"]) {
                        unset($details["VOUCHER"]);
                    }
                }
                $voucherCount++;

                //now handing invoice id
                $details["INVOICEID"] = $data->number;
                $details["DBACTION"] = $isExist == true ? 2 : 1;

                if ($key > 0) {
                    $details["INVOICEID"] = $data->number . "_" . $key;
                    $details["DBACTION"] = 2;
                }

                //for RECID
                $recid = $this->confirmRecID($data);
                $details["RECID"] = $recid["NEXTVAL"];

                // info($details);
                try {

                    if ($isDebug == 3) {
                        dd($details, $checkExist);
                    }

                    AxSalesOrder::create(
                        $details
                    );

                    //now checking sales order created or not
                    $verifyOrder = AxSalesOrder::where("RECID", $recid["NEXTVAL"])->first();
                    if ($verifyOrder) {
                        $rowCount = AxSalesOrder::count();
                        $nextVal = $rowCount + $recid["NEXTVAL"];
                        $updateNextval = $this->updateRecID(50267, $nextVal);
                        if ($updateNextval == true) {
                            info("SystemSequence Table Updated");
                            $lastRecID = $recid["NEXTVAL"];

                            UserLogger::setChronLogNew($checkExist["isExist"] == true ? ($isParentPrepayment == 0 ? json_encode($checkExist["existData"], true) : '') : '', json_encode($verifyOrder, true), $checkExist["isExist"] == true ? "Ax Sales Document Updated" :  "Ax Sales Document Created");
                            info("*********************SALES ORDER CREATED TO AX, MODE : MULTIPAYMENT, ID : " . $data->salesDocumentID . " ************************************************");
                        } else {

                            info("SystemSequence Table Update Failed");
                        }
                    }
                    // $mp->axPending = 0;
                    // $mp->save();

                } catch (Exception $e) {
                    info("*********************ERROR WHILE SAVING MULTIPAYMENT ORDER OR LINES, SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
                    $isMultilineDone = 0;
                    //if errror set this as error
                    // SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->update([ "errorFlag" => 1 ]);
                    info($e);
                    throw $e;
                }
            }

            if ($isMultilineDone == 1) {
                $data->salesAxID = $lastRecID;
                $data->axPending = 0;
                if ($isExist == false) {
                    $data->isPOS = $POSFLAG == true ? 1 : 0;
                }
                $data->save();
                //updating flag of payment
                Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->update(["axPending" => 0]);

                //saving order lines
                if ($isCancelled == 0) {
                    $this->saveLineItems($data, $linesOrder, $preFix, $isParent, $parentOrder, $axWHDetails);
                }
            }
        } else {
            info("*********************SNGLE PAYMENT PROCESS SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
            if ($isTotalZero == false) {
                $singlePayment = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->where("axPending", 1)->first();
                // if($isDebug == true){
                //     $singlePayment = Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->first();
                // }
                // echo "single";
                // die;
                $details["TENDERTYPE"] = $this->getTenderType($singlePayment->type);
                if ($this->getTenderType($singlePayment->type) == "5") {
                    $details["CARDTYPEID"] = "1";
                }

                if ($singlePayment->type == "ACCOUNT" || $singlePayment->type == "ACCOUNTS" || $singlePayment->type == "SSR" || $singlePayment->type == "SS_BONUS") {

                    $details["VOUCHER"] = 1;
                    if ($singlePayment->type == "ACCOUNT" || $singlePayment->type == "ACCOUNTS" || $singlePayment->type == "SS_BONUS") {
                        //if($isPswAxCustomer == true)
                        $details["VOUCHACCOUNT"] = $this->getVoucherRef($data->notes, "ACCOUNT"); //strval($pswAxCustomerAccount);
                        $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, "ACCOUNTS");
                        if($singlePayment->type == "SS_BONUS"){
                            $details["VOUCHACCOUNT"] = '20206'; 
                            $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, $singlePayment->type);
                        }
                    } else {
                        if (@$details["VOUCHACCOUNT"]) {
                            unset($details["VOUCHACCOUNT"]);
                        }
                        // $schoolAc = '20062';
                        $schoolAc = $preFix == "PA" ? '20062' : '17640';
                        $details["VOUCHACCOUNT"] =  $schoolAc;
                        $details["VOUCHERREF"] = $this->getVoucherRef($data->notes, $singlePayment->type);
                    }
                    $details["VOUCHERINVOICEID"] = 'V' . $data->number . "_1";
                }
                // else{
                //     if(is_null($data->paid)){
                //         $details["PAYMENTAMOUNT"] = $singlePayment->sum;
                //     }

                // }

                //for prepayment recurring payments
                if ($data->type == "PREPAYMENT" && $data->total > 0) {
                    $balanceAmt = abs($grossAmt) - abs((float)$singlePayment->sum);
                    $details["BALANCE"] = round(abs($balanceAmt), 7);
                }

                //for handling prepayment settlement or final payment 
                if ($data->type == "CASHINVOICE" && $data->total > 0 && $isParent == 1 && $parentOrder->type == "PREPAYMENT") {
                    $balanceAmt = abs($grossAmt) - abs((float)$singlePayment->sum);
                    $details["BALANCE"] = round(abs($balanceAmt), 7);
                }
            }

            /**
             * @according to Sarah : AX DEV
             * for all single payment and order type : ORDER
             * we are sending prepayment as 1
             */

            if ($data->type == "ORDER") {
                $details["PREPAYMENT"] = 1;
            }

            #if Order is Cashinvoice and if its Parent Order type is Prepayment then Set prepayment to 1
            if ($isParent == 1 && $parentOrder->type == "PREPAYMENT") {
                $details["PREPAYMENT"] = 1;
            }

            if ($isCancelled == 1) {
                //adding because cancelled payment is in negative 
                // $details["BALANCE"] = $details["GROSSAMOUNT"] + $singlePayment->sum;
                $details["BALANCE"] = 0; //$details["GROSSAMOUNT"] + $singlePayment->sum;
            }

            //if sales order amt = 0 than default tender type will be 1
            if ($isTotalZero == true) {
                $details["TENDERTYPE"] = "01";
                $details["PAYMENTAMOUNT"] = 0;
                $details["BALANCE"] = 0;
            }

            $recid = $this->confirmRecID($data);
            $details["RECID"] = $recid["NEXTVAL"];

            try {

                if ($isDebug == 3) {
                    dd($details, $isExist);
                }

                AxSalesOrder::create(
                    $details
                );

                //now checking sales order created or not
                $verifyOrder = AxSalesOrder::where("RECID", $recid["NEXTVAL"])->first();
                if ($verifyOrder) {
                    $rowCount = AxSalesOrder::count();
                    $nextVal = $rowCount + $recid["NEXTVAL"];
                    $updateNextval = $this->updateRecID(50267, $nextVal);
                    if ($updateNextval == true) {
                        info("SystemSequence Table Updated");
                        $data->salesAxID = $recid["NEXTVAL"];
                        $data->axPending = 0;
                        if ($isExist == false) {
                            $data->isPOS = $POSFLAG == true ? 1 : 0;
                        }
                        $data->save();
                        //updating flag of payment
                        Payment::where("clientCode", $data->clientCode)->where("documentID", $data->salesDocumentID)->update(["axPending" => 0]);

                        //UserLogger::setChronLogNew($isExist == true ? json_encode($checkExist["existData"],true) : '' , json_encode($verifyOrder, true), $isExist == true ? "Ax Sales Document Updated" :  "Ax Sales Document Created");        
                        UserLogger::setChronLogNew($isExist == true ? ($isParentPrepayment == 0 ? json_encode($checkExist["existData"], true) : '') : '', json_encode($verifyOrder, true), $isExist == true ? "Ax Sales Document Updated" :  "Ax Sales Document Created");
                        info("*********************SALES ORDER CREATED TO AX, SALES DOC ID : " . $data->salesDocumentID . " ************************************************");
                        //now saving line items
                        if ($isCancelled == 0) {
                            $this->saveLineItems($data, $linesOrder, $preFix, $isParent, $parentOrder, $axWHDetails);
                        }
                    } else {
                        info("SystemSequence Table Update Failed");
                    }
                }
            } catch (Exception $e) {
                info("*********************ERROR WHILE CREATING SALES DOC OR LINE ITEMS, ID: " . $data->salesDocumentID . " ************************************************");
                //if errror set this as error
                // SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->update([ "errorFlag" => 1 ]);
                info($details);
                info($e);
                throw $e;
            }
        }
    }


    private function getPostInvoice($data)
    {
        if ($data->type == "ORDER" || $data->type == "PREPAYMENT" || $data->type == "WAYBILL") {
            //Parent Orders cannot have PostInvoice ticked – as they are backorders that are awaiting fulfilment.  
            return 0;
        }

        //for Credit invoice 
        return 1;
    }


    private function getFinalPayment($data, $isCancelled)
    {

        if ($isCancelled == 1) {
            return 0;
        }

        //if order or invoice is not cancelled then 
        if ($data->type == "PREPAYMENT") {
            return 0;
        }

        return 1;
    }

    //preparing gross amount

    private function getGrossAmount($data, $isCancelled, $isParent, $parentOrder)
    {
        if ($isCancelled == 1) {
            return abs($data->total);
        }

        //handling gross amount for Layby payment
        if ($data->type == "PREPAYMENT") {

            $totalPaidAmt = $this->getPaidPaymentAmount($data, [$data->salesDocumentID]);
            $remainingGrossAmt = $data->total - $totalPaidAmt;
            return (0 - abs($remainingGrossAmt));
        }

        //handling final payment of layby order
        if ($data->type == "CASHINVOICE" && $isParent == 1 && $parentOrder->type == "PREPAYMENT") {
            $totalPaidAmt = $this->getPaidPaymentAmount($data, [$data->salesDocumentID, $parentOrder->salesDocumentID]);
            $remainingGrossAmt = $data->total - $totalPaidAmt;
            return (0 - abs($remainingGrossAmt));
        }


        $grossAmt = 0;
        if ($data->total < 0) {
            $grossAmt = abs($data->total);
        } else {
            $grossAmt = (0 - abs($data->total));
        }

        return $grossAmt;
    }

    private function getPaidPaymentAmount($data, $ids)
    {
        $totalPaidAmt = 0;
        $payments = Payment::where("clientCode", $data->clientCode)->whereIn("documentID", $ids)->where("axPending", 0)->get();
        foreach ($payments as $p) {
            $totalPaidAmt += $p->sum;
        }
        return $totalPaidAmt;
    }

    //get delivery mode accroding to conditions
    private function getDeliveryMode($data, $orderLines)
    {
        $mode = 'CC_Pickup';
        // first check cc_school product exist in order lines 

        if ($data->type == "ORDER") {
            foreach ($orderLines as $line) {
                if ($line->code == "CC_School") {
                    $mode = "CC_School";
                }

                if ($line->code == "CCPickup") {
                    $mode = "CC_Pickup";
                }

                if ($line->code == "Freight1" || $line->code == "Freight3") {
                    $mode = "Aust Post";
                }
            }
            return $mode;
        } else {

            if ($this->checkSchoolProduct($data->salesDocumentID, $data) == 0) {

                if ($data->type == "WAYBILL") {
                    // $details["DLVMODE"] = "CC_Pickup";
                    $mode = "CC_Pickup";
                } else {
                    // $details["DLVMODE"] = "Pickup Cus";
                    $mode = "Pickup Cus";
                }
            } else {
                // $details["DLVMODE"] = "CC_School";
                $mode = "CC_School";
            }
        }



        return $mode;
    }

    private function checkSchoolProduct($sid, $data)
    {

        $chk = SalesDocumentDetail::where("clientCode", $data->clientCode)->where("salesDocumentID", $sid)->where("code", "CC_School")->first();
        if ($chk) {
            return 1;
        }

        return 0;
    }

    protected function getTenderType($payment)
    {
        switch ($payment) {
            case "CASH":
                return "01";
                // break;
            case "CARD":
                return "5";
            case "GIFTCARD":
                return "10";
            case "ACCOUNT":
                return "16";
            case "ACCOUNTS":
                return "16";
            case "SSR":
                return "17";
            case "SS_BONUS":
                return "18";
            case "CHECK":
                return "2";
            case "CREDIT":
                return "06";
            default:
                return "0";
        }
    }

    protected function getVoucherRef($notes, $type)
    {
        $ssrVoucher = '';
        $schoolAccountNumber = '';
        $schoolAccountPO = '';
        $cd = preg_replace('/\s+/', ' ', $notes);
        // $cd = "School Account Number: dev School AN School Account PO: Dev APO";
        // SSR Voucher Number: APPL266869
        // School Account Number: NESREEN ASAAD
        if (preg_match('/SSR Voucher Number: (.*?)(?= School Account Number:| School Account PO:| School Account PO :|$)/', $cd, $matches)) {
            $ssrVoucher = substr($matches[1], 0, 100);
        }

        // if (preg_match('/SSR Voucher: (.*?)(?= School Account Number:| School Account PO:| School Account PO :|$)/', $cd, $matches)) {
        //     $ssrVoucher = substr($matches[1], 0, 100);
        // }

        if (preg_match('/School Account Number: (.*?)(?= School Account PO:| School Account PO : |$)/', $cd, $matches)) {
            $schoolAccountNumber = substr($matches[1], 0, 20);
        }


        if (preg_match('/School Account PO: (.*)$/', $cd, $matches)) {
            $schoolAccountPO = substr($matches[1], 0, 100);
        }

        if ($schoolAccountPO == '') {
            if (preg_match('/School Account PO : (.*)$/', $cd, $matches)) {
                $schoolAccountPO = substr($matches[1], 0, 100);
            }
        }

        switch ($type) {
            case "ACCOUNT":
                return $schoolAccountNumber;
            case "ACCOUNTS":
                return $schoolAccountPO;
            case "SSR":
                return $ssrVoucher;
            case "SS_BONUS":
                return str_replace("SSR Voucher:", "", $notes);
            default:
                return '';
        }
    }

    //FOR ORDER CANCELLATION
    public function syncCancelOrder($req)
    {


        $isDebug = $req->debug ? $req->debug : false;
        $customSalesDocumentID = $req->id ? $req->id : 0;

        info("Sync Sales Document To AX Cron");

        $mi_orders = SalesDocument:: //paymentsWithClientCode()
            join("newsystem_warehouse_locations", function ($join) {
                $join->on("newsystem_warehouse_locations.warehouseID", "=", "newsystem_sales_documents.warehouseID");
                $join->on("newsystem_warehouse_locations.clientCode", "=", "newsystem_sales_documents.clientCode");
            })
            // ->where("newsystem_sales_documents.clientCode", $this->api->client->clientCode)
            // ->where("newsystem_warehouse_locations.clientCode", $this->api->client->clientCode)
            ->whereIn("newsystem_sales_documents.type", ['WAYBILL', 'ORDER', 'PREPAYMENT', 'CREDITINVOICE', 'CASHINVOICE'])
            // ->whereNotIn("newsystem_sales_documents.invoiceState", ['CANCELLED'])
            // ->where("newsystem_customers.clientCode", $this->api->client->clientCode)
            ->where("newsystem_sales_documents.axPending", 1)
            ->where("newsystem_sales_documents.noLineFlag", 0)
            ->where("newsystem_sales_documents.paymentFlag", 0)
            ->where("newsystem_sales_documents.errorFlag", 0)
            ->where("newsystem_sales_documents.erplyDeleted", 0)
            ->where("newsystem_sales_documents.number", "<>", "0")
            ->whereNotIn("newsystem_sales_documents.invoiceState", ["CANCELLED"])
            // ->where("newsystem_sales_documents.salesDocumentID", 221)
            ->select([
                "newsystem_sales_documents.*",
                "newsystem_sales_documents.notes as orderNotes",
                "newsystem_warehouse_locations.code as warehouseCode"
            ])
            ->limit(7)
            // ->toSql();
            ->get();

        // dd($mi_orders);

        if ($customSalesDocumentID > 0) {
            // dd("hi");

            $mi_orders = SalesDocument::join("newsystem_warehouse_locations", function ($join) {
                    $join->on("newsystem_warehouse_locations.warehouseID", "=", "newsystem_sales_documents.warehouseID");
                    $join->on("newsystem_warehouse_locations.clientCode", "=", "newsystem_sales_documents.clientCode");
                })
                ->whereIn("newsystem_sales_documents.type", ['WAYBILL', 'ORDER', 'PREPAYMENT', 'CREDITINVOICE', 'CASHINVOICE'])
                ->whereNotIn("newsystem_sales_documents.invoiceState", ['CANCELLED'])
                // ->where("newsystem_sales_documents.clientCode", $req->clientcode)
                ->where("newsystem_sales_documents.erplyDeleted", 0)
                ->where("newsystem_sales_documents.id", $customSalesDocumentID)
                // ->where("newsystem_sales_documents.clientCode", "newsystem_warehouse_locations.clientCode")
                // ->select(["newsystem_sales_documents.*","newsystem_sales_documents.notes as orderNotes"
                // ])  
                ->select([
                    "newsystem_sales_documents.*",
                    "newsystem_sales_documents.notes as orderNotes",
                    "newsystem_warehouse_locations.code as warehouseCode"
                ])
                // ->limit(1)
                // ->toSql();
                ->get();
            // dd($mi_orders);
        }


        if ($mi_orders->isEmpty()) {
            info("All Sales Document Synced to AX");
            return response()->json(["message" => "All Sales Document Synced"]);
        }



        foreach ($mi_orders as $data) {
            // info($data); 
            try {

                //first getting order Warehouse info of ax
                //first getting erply warehouse details
                $erply_wh_details = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();
                //second getting ax warehouse details
                $ax_wh_details = LiveWarehouseLocation::where("LocationID", $erply_wh_details->code)->first();

                $isAcademySalesOrder = $this->checkIsAcademySalesOrder($data);

                $isAxSalesOrder = false;
                $pswAxSalesID = '';

                $isPswAxCustomer = false;
                $isPswAxCustomerID = 1;
                $checkIsPswAxCustomer = LiveCustomerRelation::where($isAcademySalesOrder == 1 ? "customerID" : "pswCustomerID", $data->clientID)->first();
                // dd($checkIsPswAxCustomer);
                if ($checkIsPswAxCustomer) {
                    $isPswAxCustomer = true;
                    $isPswAxCustomerID = $checkIsPswAxCustomer->PSW_SMMCUSTACCOUNT;
                }

                $isParent = false;
                $parentOrder = [];
                if ($data->baseDocuments != "") {
                    $baseDoc = json_decode($data->baseDocuments, true);
                    // dd($baseDoc);
                    $parentOrder = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $baseDoc[0]["id"])->first();
                    if ($parentOrder) {
                        $isParent = true;
                    }
                }
                // dd($data->clientCode);
                //getting school id using first order line erplysku
                $linesOrder = SalesDocumentDetail::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->where("isDeleted", 0)->get();
                // dd($linesOrder);

                if ($data->attributes != '') {
                    $checkOrder = json_decode($data->attributes, true);
                    foreach ($checkOrder as $key => $co) {
                        if (@$co['attributeName'] == "SALESID") {
                            $isAxSalesOrder = true;
                            $pswAxSalesID = $co["attributeValue"];
                        }
                    }
                }
                $preFix = '';

                $checkIsLive = Client::where("clientCode", $data->clientCode)->first()->ENV;

                if ($checkIsLive == 0) {
                    $preFix = $data->clientCode == 605325 ? "PP" : "PP";
                }

                if ($checkIsLive == 1) {
                    $pswPatch = "PP";
                    if ($data->salesDocumentID >= 2581 && $data->salesDocumentID <= 3113) {
                        $pswPatch = "PP_";
                    }
                    $preFix = $data->clientCode == 607655 ? "PA" : $pswPatch;
                    // $preFixa = $data->clientCode == 607655 ? "PA" : $pswPatch;
                }

                $this->saveUpdateSalesOrders($data, $linesOrder, $isPswAxCustomer, $isPswAxCustomerID, $preFix, $isParent, $parentOrder, $isDebug, $ax_wh_details);
            } catch (Exception $e) {
                info($e->getMessage());
                SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->salesDocumentID)->update(["errorFlag" => 1]);
            }
        }

        return response()->json(["status" => "success"]);
    }



    protected function confirmRecID($data, $count = 1,)
    {
        //if exist update 
        $recid = $this->getRecID(50267);
        $checkRECID = AxSalesOrder::where("RECID", $recid["NEXTVAL"])->first();
        if ($checkRECID) {
            DuplicateRecid::create(
                [
                    "tableID" => 50267,
                    "RECID" => $recid["NEXTVAL"],
                    "tableName" => "IN_SALES_ORDER",
                    "type" => "SalesOrder",
                    "documentID" => $data->salesDocumentID,
                    "clientCode" => $data->clientCode,
                ]
            );
            //getting duplicate recid
            info("Sales AX : Duplicate RECID Found");
            $rowCount = AxSalesOrder::count();
            $nextVal = $rowCount + $recid["NEXTVAL"];
            $this->updateRecID(50267, $nextVal);
            if ($count == 1) {
                //second time 
                return $this->confirmRecID($data, 2);
            }

            if ($count == 2) {
                //second time 
                return $this->confirmRecID($data, 3);
            }

            if ($count == 3) {
                //second time 
                return $this->confirmRecID($data, 4);
            }

            if ($count == 4) {
                //second time 
                return $this->confirmRecID($data, 5);
            }

            info("5 Request Failed.");
            die;
            // return $this->confirmRecID(true);
        }

        return $recid;
    }


    public function checkPaymentFlag()
    {

        info("Checking All Payment Flagged Sales Document...");
        $mi_orders = SalesDocument::where("newsystem_sales_documents.clientCode", $this->api->client->clientCode)
            // ->where("newsystem_warehouse_locations.clientCode", $this->api->client->clientCode)
            ->whereIn("newsystem_sales_documents.type", ['WAYBILL', 'ORDER', 'PREPAYMENT', 'CREDITINVOICE', 'CASHINVOICE'])
            // ->where("newsystem_customers.clientCode", $this->api->client->clientCode)
            // ->where("newsystem_sales_documents.axPending", 1)
            // ->where("newsystem_sales_documents.noLineFlag", 0)
            ->where("newsystem_sales_documents.paymentFlag", 1)
            ->where("newsystem_sales_documents.checkPaymentErply", 0)
            ->where("newsystem_sales_documents.erplyDeleted", 0)
            ->where("created_at", '>', '2024-01-01')
            // ->where("newsystem_sales_documents.salesDocumentID", 221)
            ->orderBy("updated_at", 'asc')
            ->limit(90)
            // ->toSql();
            ->get();

        if ($mi_orders->isEmpty()) {

            SalesDocument::where("clientCode", $this->api->client->clientCode)
                ->where("paymentFlag", 1)
                ->update(
                    [
                        "checkPaymentErply" => 0
                    ]
                );

            info("All payment flag checked. and reset CheckPaymentErply flag to 0");
            return response("All payment flag checked. and reset CheckPaymentErply flag to 0");
        }
        // now getting payment details from erply
        // dd($mi_orders);
        $getBulk = array();
        $bulkParam = array(
            "sessionKey" => $this->api->client->sessionKey
        );

        foreach ($mi_orders as $p) {
            $param = array(
                "requestName" => "getPayments",
                "clientCode" => $this->api->client->clientCode,
                "sessionKey" => $this->api->client->sessionKey,
                "documentID" => $p->salesDocumentID,
                // "customerID" => $p->erplyCustomerID
            );
            $getBulk[] = $param;
        }


        // dd($getBulk);
        $getBulk = json_encode($getBulk, true);

        $getBulkRes = $this->api->sendRequest($getBulk, $bulkParam, 1);
        // dd($getBulkRes);
        if ($getBulkRes["status"]["errorCode"] == 0) {
            foreach ($mi_orders as $key => $mo) {
                if ($getBulkRes["requests"][$key]["status"]["errorCode"] == 0 && !empty($getBulkRes["requests"][$key]["records"])) {
                    //payment exist in erply
                    $this->paymentService->saveUpdate($getBulkRes["requests"][$key]["records"]);

                    $mo->checkPaymentErply = $mo->checkPaymentErply + 1;
                    $mo->save();
                } else {

                    if ($getBulkRes["requests"][$key]["status"]["errorCode"] == 1011 && $getBulkRes["requests"][$key]["status"]["errorField"] == 'documentID') {
                        //this sales document is deleted 
                        $mo->erplyDeleted = 1;
                    }
                    $mo->checkPaymentErply = $mo->checkPaymentErply + 1;
                    $mo->save();
                }
            }
        }



        foreach ($mi_orders as $data) {
            $paymentDetails = Payment::where("clientCode", $this->api->client->clientCode)->where("documentID", $data->salesDocumentID)->where("axPending", 1)->first();
            if ($paymentDetails) {
                //break the loop and wait for next 1min
                $data->paymentFlag = 0;
                $data->checkPaymentErply = 0;
                $data->save();
                info("Paymnet Flag Updated.");
                // echo "Payment Flag updated";
                // return response("No Payment Details Found");
                // die;
            }
        }
        info("Payment Flag Checking...");
        return response("All Sales Flagged Payment Checked Successfully.");
    }


    //handle no line flag sales document
    public function handleNoLineFlagDocuments()
    {



        $datas = SalesDocument::where("clientCode", $this->api->client->clientCode)->where("noLineFlag", 1)->orderBy("updated_at", 'asc')->limit(20)->get();

        if ($datas->isEmpty()) {
            //no records found
            die;
        }

        info("No Line Flags Api Called " . $this->api->client->ENTITY);

        $pids = '';
        foreach ($datas as $key => $data) {
            $pids .=  $key > 0 ? "," . $data->salesDocumentID : $data->salesDocumentID;
        }

        $param = array(
            // "orderBy" => "changedSince",
            // "orderByDir" => "asc",
            // "recordsOnPage" => "200",
            "getRowsForAllInvoices" => 1,
            "ids" => $pids
        );
        // dd($param);
        $res = $this->api->sendRequest("getSalesDocuments", $param);
        // dd($res);
        if ($res['status']['errorCode'] == 0 && !empty($res['records'])) {
            // print_r($res['records']);
            $this->salesDocumentService->saveUpdate($res['records']);
        }



        //now updating no line flag
        foreach ($datas as $key => $data) {
            $check = SalesDocumentDetail::where("clientCode", $this->api->client->clientCode)->where("salesDocumentID", $data->salesDocumentID)->get();
            if ($check->isNotEmpty()) {
                $data->noLineFlag = 0;
                $data->updated_at = date('Y-m-d H:i:s');
                $data->save();
            }
            $data->updated_at = date('Y-m-d H:i:s');
            $data->save();
        }
        // info("No Line Flags Api Called ". $this->api->client->ENTITY);
        return response("No Line Flag Document Checked Successfully.");
    }
}
