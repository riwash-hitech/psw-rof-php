<?php
namespace App\Http\Controllers\Paei\API\APIServices;

use App\Classes\Except;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use App\Models\PAEI\EmailNotification;
use App\Models\PAEI\InventoryTransfer;
use App\Models\PAEI\MessageNotification;
use App\Models\PAEI\SalesDocument;
use App\Models\PAEI\SalesDocumentDetail;
use App\Models\PAEI\Warehouse;
use App\Models\PswClientLive\Local\LiveWarehouseLocation; 
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\WmsValidationTrait;

class WarehouseApiService{

    use ResponseTrait, WmsValidationTrait;
    protected $warehouse;
    protected $api;

    public function __construct(Warehouse $w, EAPIService $api){
        $this->warehouse = $w;
        $this->api = $api;
    }

    public function getByWarehouseID($id){

        $warehouse = $this->warehouse->where('clientCode',  $this->api->client->clientCode)->where("id", $id)->get();
        return response()->json(["status"=>200, "records" => $warehouse]);

    }

    public function getWarehouses($req){
        return $this->getWarehousesV2($req);
        die;
        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'name';
        }
        if(isset($req->strictFilter) == 0){
            $req->strictFilter = true;
        }
         

        $pagination = $req->recordsOnPage == '' ? 20 : $req->recordsOnPage;
        $requestData = $req->except(Except::$except);
        // $warehouses = $this->warehouse->paginate($pagination);
        $warehouses = $this->warehouse->where(function ($q) use ($requestData, $req) {
            $q->where('clientCode', $this->api->client->clientCode);
            foreach ($requestData as $keys => $value) {
                if ($value != null) { 
                    if($req->strictFilter == true){
                        $q->Where($keys, $value);
                    }else{
                        $q->Where($keys, 'LIKE', '%'.$value.'%');
                    }
                    // 'like', '%' . $value . '%'); 
                }
            }
        })->orderBy($req->sort_by, $req->direction)->paginate($pagination);


        return response()->json(["status"=>200, "records" => $warehouses]);
    }

    public function getWarehousesV2($req){
        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'LocationName';
        }
        if(isset($req->strictFilter) == 0){
            $req->strictFilter = true;
        }
         

        $pagination = $req->recordsOnPage == '' ? 20 : $req->recordsOnPage;
        $requestData = $req->except(Except::$except);
        // $warehouses = $this->warehouse->paginate($pagination);
        $warehouses = LiveWarehouseLocation::where(function ($q) use ($requestData, $req) {
            // $q->where('clientCode', $this->api->client->clientCode);
            foreach ($requestData as $keys => $value) {
                if ($value != null) { 
                    if($req->strictFilter == true){
                        $q->Where($keys, $value);
                    }else{
                        $q->Where($keys, 'LIKE', '%'.$value.'%');
                    }
                    // 'like', '%' . $value . '%'); 
                }
            }
        })->orderBy($req->sort_by, $req->direction)->paginate($pagination);


        return response()->json(["status"=>200, "records" => $warehouses]);
    }

    public function getProductLong(){

    }


    protected function packaging($matrix, $variation){
        $newPackage = array();
        foreach($matrix as $m){
            array_push($newPackage , $m);
        }

        foreach($variation as $v){
            array_push($newPackage , $v);
        }
        return $newPackage;
    }



    protected function makeQuery($req){

    }



    /**
     * FOR WAREHOUSE MGMT API LIST
     */

    public function getWarehouseList($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $list = LiveWarehouseLocation::where("LocationID", $req->warehouseID)->paginate($pagination);
        return $this->successWithData($list);
    }

    public function warehouseWiseOrders($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }

        $customExcept = Except::$except;
        $customExcept[] = "warehouseID";
        $requestData = $req->except($customExcept);
        // dd($requestData);
        // if(isset($req->warehouseID) == 0 && $req->warehouseID == ''){
        //     return $this->failWithMessage("Invalid Warehouse ID!");
        // }

        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'salesDocumentID';
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        if($req->salesDocumentID){
            $reqType = $req->type ? $req->type : '';
            if($reqType == "express"){
                $orders = SalesDocument::
                //with("SalesDetails.axRelation")
                with([
                    'SalesDetails' => function ($salesDetailsQuery) use ($currentWarehouse) {
                        $salesDetailsQuery->where("clientCode", $currentWarehouse["clientCode"])
                        ->with(['axRelation' => function ($axRelationQuery) use ($currentWarehouse) {
                            $axRelationQuery->where('DefaultStore',  $currentWarehouse["warehouseCode"])
                                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        }]);
                    }
                ])
                ->with("Customer")
                ->with(['payments' => function($q) use($currentWarehouse){
                    $q->where("sum",'>', 0)
                    ->where("clientCode", $currentWarehouse["clientCode"]);
                }])
                // ->addSelect("newsystem_sales_documents.*","newsystem_sales_document_details.*","newsystem_product_variation_live.erplyID")
                // ->where("clientCode", $this->api->client->clientCode)
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->where("type", "ORDER")
                ->where("isSynccarePos", 1)
                // ->where("warehouseID", $req->warehouseID)
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0)
                ->where("readyToFulfill", 0)
                ->where("isExpress", 1)
                ->where("salesDocumentID",'>', $req->salesDocumentID)
                ->select(
                    [
                        'id',
                        'salesDocumentID',
                        'type',
                        'warehouseID',
                        'warehouseName',
                        'number',
                        'date',
                        'time',
                        'clientID',
                        'clientName',
                        'clientEmail',
                        'total',
                        'attributes',
                        'created_at',
                        'isExpress',
                        'readyToFulfill',
                        'pickedOrder',
                        'isPrinted',
                        'lastModified',
                        'added',
                        DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink")
                    ]
                )
                
                ->orderBy("salesDocumentID", 'asc')
                ->paginate($pagination);
                // ->select(["newsystem_sales_documents.*", "SalesDetails.productID as erplyID","SalesDetails.*"])
                // ->get();   

                return $this->successWithData($orders); 
            }
            
            $orders = SalesDocument:://with("SalesDetails.axRelation")
                with([
                    'SalesDetails' => function ($salesDetailsQuery) use ($currentWarehouse) {
                        $salesDetailsQuery->where("clientCode", $currentWarehouse["clientCode"])
                        ->with(['axRelation' => function ($axRelationQuery) use ($currentWarehouse) {
                            $axRelationQuery->where('DefaultStore',  $currentWarehouse["warehouseCode"])
                                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        }]);
                    }
                ])
                ->with("Customer")
                ->with(['payments' => function($q) use($currentWarehouse){
                    $q->where("sum",'>', 0)
                    ->where("clientCode", $currentWarehouse["clientCode"]);
                }])
                // ->addSelect("newsystem_sales_documents.*","newsystem_sales_document_details.*","newsystem_product_variation_live.erplyID")
                // ->where("clientCode", $this->api->client->clientCode)
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->where("type", "ORDER")
                ->where("isSynccarePos", 1)
                // ->where("warehouseID", $req->warehouseID)
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0)
                ->where("readyToFulfill", 0)
                ->where("isExpress", 0)
                ->where("salesDocumentID",'>', $req->salesDocumentID)
                ->select(
                    [
                        'id',
                        'salesDocumentID',
                        'type',
                        'warehouseID',
                        'warehouseName',
                        'number',
                        'date',
                        'time',
                        'clientID',
                        'clientName',
                        'clientEmail',
                        'total',
                        'attributes',
                        'created_at',
                        'isExpress',
                        'readyToFulfill',
                        'pickedOrder',
                        'isPrinted',
                        'lastModified',
                        'added',
                        DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink")
                    ]
                    )
                ->orderBy("salesDocumentID", 'asc')
                ->paginate($pagination);
                // ->select(["newsystem_sales_documents.*", "SalesDetails.productID as erplyID","SalesDetails.*"])
                // ->get();   

            return $this->successWithData($orders); 
        }
        
        

        $orders = SalesDocument:://with("SalesDetails.axRelation")
                with([
                    'SalesDetails' => function ($salesDetailsQuery) use ($currentWarehouse) {
                        $salesDetailsQuery->where("clientCode", $currentWarehouse["clientCode"])
                        ->with(['axRelation' => function ($axRelationQuery) use ($currentWarehouse) {
                            $axRelationQuery->where('DefaultStore',  $currentWarehouse["warehouseCode"])
                                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        }]);
                    }
                ])
                ->with(["Customer" => function($q) use($currentWarehouse){
                    $q->where("clientCode", $currentWarehouse["clientCode"]);
                } 
                ])
                ->with(['payments' => function($q) use($currentWarehouse){
                    $q->where("sum",'>', 0)
                    ->where("clientCode", $currentWarehouse["clientCode"]);
                }])
                // ->withCount(['payments' => function($q) use($currentWarehouse){
                //     $q->select(DB::raw('SUM(amount) as total_sum'))
                //       ->where('amount', '>', 0)
                //       ->where('clientCode', $currentWarehouse["clientCode"]);
                // }])->withDefault(['payments_count' => 0, 'payments_sum' => 0])
                // ->addSelect("newsystem_sales_documents.*","newsystem_sales_document_details.*","newsystem_product_variation_live.erplyID")
                // ->where("clientCode", $this->api->client->clientCode)
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->where("type", "ORDER")
                ->where("isSynccarePos", 1)
                // ->where("warehouseID", $req->warehouseID)
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0)
                ->where("readyToFulfill", 0)
                ->where("isExpress", 0)
                ->select('id','salesDocumentID','type','warehouseID','warehouseName','number','date','time','clientID','clientName','clientEmail','total','attributes',
                    // 'created_at',
                    'isExpress',
                    'readyToFulfill',
                    'pickedOrder',
                    'isPrinted',
                    'created_at',
                    'updated_at',
                    'lastModified',
                    'added',
                    DB::raw("CASE WHEN '". $this->api->isLiveEnv() . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseIDs=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentIDs=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink"),
                    // DB::raw("CASE WHEN '". $this->api->isLiveEnv() . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink")
                )
                ->where(function ($q) use ($requestData, $req) {
                    // $q->where('clientCode', $this->api->client->clientCode);
                    foreach ($requestData as $keys => $value) {
                        if ($value != null) { 
                            if($req->strictFilter == true){
                                $q->Where($keys, $value);
                            }else{
                                $q->Where($keys, 'LIKE', '%'.$value.'%');
                            }
                            // 'like', '%' . $value . '%'); 
                        }
                    }
                })
                ->orderBy($req->sort_by, $req->direction)
                ->paginate($pagination);
                // ->select(["newsystem_sales_documents.*", "SalesDetails.productID as erplyID","SalesDetails.*"])
                
                // ->get();
        
        return $this->successWithData($orders);
    }

   

    public function orderLineItemOnly($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }

        //first getting list of orders
        if(isset($req->warehouseID) == 0 && $req->warehouseID == ''){
            return $this->failWithMessage("Invalid Warehouse ID!");
        }
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $orders = SalesDocument::
                // ->addSelect("newsystem_sales_documents.*","newsystem_sales_document_details.*","newsystem_product_variation_live.erplyID")
                where("clientCode", $currentWarehouse["clientCode"])
                ->where("isSynccarePos", 1)
                ->where("type", "ORDER")
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0)
                ->where("readyToFulfill", 0)
                ->pluck("salesDocumentID")
                ->toArray();
        
        $lines = SalesDocumentDetail::with("axRelation")
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->whereIn("salesDocumentID", $orders)
                ->selectRaw("productID, SUM(amount) as quantity, itemName, code")
                ->groupBy("productID")
                // ->paginate($pagination);
                ->get();
        

        return $this->successWithData($lines);
    }

    public function readyToFulfill($req){

        return $this->readyToFulfillv2($req);
        die;

        if(isset($req->salesDocumentID) == 1 && $req->salesDocumentID != ''){

            SalesDocument::where("clientCode", $this->api->client->clientCode)
            ->where("salesDocumentID", $req->salesDocumentID)
            ->where("isSynccarePos", 1)
            ->update(
                [
                    "readyToFulfill" => 1
                ]
            );

            //here handle send sms and email 
            

            return $this->successWithMessage("Order Fullfilled Successfully.");
        }
    }

    public function readyToFulfillv2($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }

        if(isset($req->id) == 1 && $req->id != ''){

            $sd = SalesDocument::where("id", $req->id)->first();
            
            $customer = Customer::where("clientCode", $sd->clientCode)->where("customerID", $sd->clientID)->first();
            // dd($sd, $customer);
            //now updating readytofulfill = 1
            $sd->readyToFulfill = 1;
            $sd->readyToFulfillDateTime = Carbon::now(new \DateTimeZone('Australia/Sydney'))->format('Y-m-d H:i:s');
            $sd->save();
             
        
            $isSendSMS = @$req->sendSms ? @$req->sendSms : 0;
            info("************************************************************************* sms ". $isSendSMS);
            //here handle send sms and email 
            // if($isSendSMS == 0){
            //     dd("Hello im False");
            // }
            // if($isSendSMS == 1){
            //     dd("Hello im true");
            // }
            if($isSendSMS == 1){

                // dd($isSendSMS);

                $content = 'Hi '.$customer->fullName.', 
Your Order Number '.$sd->number.' is ready to be picked up from '.$sd->warehouseName.'.
Thank You';

                $smsPayload = [
                    "clientCode" => $sd->clientCode,
                    "orderID" => $sd->salesDocumentID,
                    "warehouseID" => $sd->warehouseID,
                    "orderNumber" => $sd->number,
                    "fromStore" => $sd->warehouseName,
                    "store" => $sd->warehouseName,
                    "pickupDate" => date('Y-m-d H:i:s'),
                    "customerID" => $sd->clientID,
                    "customerName" => $sd->clientName,
                    "pendingProcess" => 1,
                    "destination_number" => $customer->mobile,
                    "content" => $content 
                ];

                MessageNotification::updateOrCreate(
                    [
                        "clientCode" => $sd->clientCode,
                        "orderID" => $sd->salesDocumentID,
                        "orderNumber" => $sd->number,

                    ],
                    $smsPayload
                );
            }

            $fromEmail = 'notifications@psw.com.au';
            if($this->api->isLiveEnv() == 1){
                if($sd->clientCode == 607655){
                    $fromEmail = "notifications@academyuniforms.com.au";
                }
            }

            $isSendEmail = @$req->sendEmail ?  @$req->sendEmail : 0;

            info("************************************************************************* email ".  $isSendEmail);
            if($isSendEmail == 1){

                // dd($isSendEmail);

                $contentEmail = '<h3> Hi '.$customer->fullName.', </h3>
                <p>
                Your Order <b>'.$sd->number.'</b> is ready to be picked up from <b>'.$sd->warehouseName.'</b>
                </p>
                <h4>Thank You</h4>';

                if(1 == 2){
                    $contentEmail = '<h3>Web orders,</h3>
                    <p> Hi '.$customer->fullName.', Order Number '.$sd->number.' is ready for collection at 
                    '.$sd->warehouseName.'. Pickup Mon-Fri {9am-5pm} & Sat {10am-12:30pm}</p>';

                }


                $emailPayload = [
                    "clientCode" => $sd->clientCode,
                    "orderID" => $sd->salesDocumentID,
                    "orderNumber" => $sd->number,
                    "toEmail" => $customer->email,
                    "fromEmail" => $fromEmail,
                    "subject" => "Pickup Order Notification",
                    "message" => $contentEmail,
                    // "message" => $contentEmail,
                    "warehouseID" => $sd->warehouseID
                ];  

                EmailNotification::updateOrcreate(
                    [
                        "clientCode" => $sd->clientCode,
                        "orderID" => $sd->salesDocumentID,
                        "orderNumber" => $sd->number,
                    ],
                    $emailPayload
                );
            }

            return $this->successWithMessage("Order Fullfilled Successfully.");
        }
    }

    public function fulfilledOrders($req ){
        
        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }
        
        if(isset($req->warehouseID) == 0 && $req->warehouseID == ''){
            return $this->failWithMessage("Invalid Warehouse ID!");
        }   
    
        $date = @$req->date ? @$req->date : "1Day";
       
        
        $targetDate = date("Y-m-d");
        if (isset($date) && in_array($date, ["1Day", "3Day", "5Day", "7Day"])) {
            $daysToAdd = intval(substr($date, 0, -3)); // Extract the numeric part from the date string
            $daysToAdd = $daysToAdd - 1;
            // if($daysToAdd == 1){
            //     $daysToAdd = 0;
            // }

            $targetDate = date("Y-m-d", strtotime("-$daysToAdd days")); // Calculate the target date
           
            // $q->where("date", ">=", $targetDate);
        }
        // dd($targetDate);
        
            $pagination = @$req->recordsOnPage ? @$req->recordsOnPage : 20;
       
        
        $orders = SalesDocument:://with("SalesDetails.axRelation")
                with([
                    'SalesDetails' => function ($salesDetailsQuery) use ($currentWarehouse) {
                        $salesDetailsQuery->where("clientCode", $currentWarehouse["clientCode"])
                        ->with(['axRelation' => function ($axRelationQuery) use ($currentWarehouse) {
                            $axRelationQuery->where('DefaultStore',  $currentWarehouse["warehouseCode"])
                                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        }]);
                    }
                ])
                ->with("Customer")
                // ->addSelect("newsystem_sales_documents.*","newsystem_sales_document_details.*","newsystem_product_variation_live.erplyID")
                // ->where("clientCode", $this->api->client->clientCode)
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->where("type", "ORDER")
                ->where("isSynccarePos", 1)
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0)
                ->where("readyToFulfill", 1)
                ->where("date", ">=", $targetDate)
                // ->where(function($q) use ($req){
                    
                // })
                ->select('id','salesDocumentID','type','warehouseID','warehouseName','number','date','time','clientID','clientName','clientEmail','total','attributes',
                    'created_at',
                    'isExpress',
                    'readyToFulfill',
                    'pickedOrder',
                    DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink")
                )
                ->orderBy("salesDocumentID", 'asc')
                ->paginate($pagination);
                // ->select(["newsystem_sales_documents.*", "SalesDetails.productID as erplyID","SalesDetails.*"])
                // ->get();
       
        return $this->successWithData($orders);
    }

    public function readyToBePicked($req ){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }
        
        
        if(isset($req->warehouseID) == 0 && $req->warehouseID == ''){
            return $this->failWithMessage("Invalid Warehouse ID!");
        } 
        
        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'added';
        } 

        
        
        $pagination = @$req->recordsOnPage ? @$req->recordsOnPage : 20; 
        // DB::connection('mysql2');
        $orders = SalesDocument:://with("SalesDetails.axRelation")
                with([
                    'SalesDetails' => function ($salesDetailsQuery) use ($currentWarehouse) {
                        $salesDetailsQuery->where("clientCode", $currentWarehouse["clientCode"])
                        ->with(['axRelation' => function ($axRelationQuery) use ($currentWarehouse) {
                            $axRelationQuery->where('DefaultStore',  $currentWarehouse["warehouseCode"])
                                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        }]);
                    }
                ])
                ->with("Customer")
                ->with(['payments' => function($q) use($currentWarehouse){
                    $q->where("sum",'>', 0)
                    ->where("clientCode", $currentWarehouse["clientCode"]);
                }])
                // ->whereHas('SalesDetails.axRelation', function ($q) use ($currentWarehouse) {
                //     $q->connection('mysql2')
                //         ->where("DefaultStore", $currentWarehouse["warehouseCode"])
                //         ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                // })
                // ->addSelect("newsystem_sales_documents.*","newsystem_sales_document_details.*","newsystem_product_variation_live.erplyID")
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->where("type", "ORDER")
                ->where("isSynccarePos", 1)
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0)
                ->where("readyToFulfill", 1)
                ->where("pickedOrder", 0) 
                ->select('id','salesDocumentID','type','warehouseID','warehouseName','number','date','time','clientID','clientName','clientEmail','total','attributes',
                    'created_at',
                    'isExpress',
                    'readyToFulfill',
                    'pickedOrder',
                    'lastModified',
                    'added',
                    DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink")
                )
                ->orderBy($req->sort_by, $req->direction)
                ->paginate($pagination);
                // ->select(["newsystem_sales_documents.*", "SalesDetails.productID as erplyID","SalesDetails.*"])
                // ->get();
                // DB::reconnect(config('database.default'));
        return $this->successWithData($orders);
    }

    public function updateToPickedOrder($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }
        

        if($req->ids){
            $bulkOrder = explode(",", $req->ids);

            foreach($bulkOrder as $order){
                SalesDocument::where("id", $order)->where("clientCode", $currentWarehouse["clientCode"])->where("warehouseID", $currentWarehouse["warehouseID"])->where("isSynccarePos",1)
                ->update(
                    [
                        "pickedOrder" => 1,
                        "pickedDatetime" => Carbon::now(new \DateTimeZone('Australia/Sydney'))->format('Y-m-d H:i:s')
                    ]
                );
            }

            return $this->successWithMessage("Order Picked Successfully.");
            
        }
        
    }

    public function filterOrder($req){

        

        $version = $req->version ? $req->version : '';
        if($version == "v2"){
            return $this->filterOrderV2($req);
            die;
        }
        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }
        // return $this->checkWarehouseID($req);

        $filterData = $req->except(['warehouseID','keywords','page','strictFilter','version']);
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 5;
         
        $datas = SalesDocument:://with("SalesDetails.axRelation")
                with([
                    'SalesDetails' => function ($salesDetailsQuery) use ($currentWarehouse) {
                        $salesDetailsQuery->where("clientCode", $currentWarehouse["clientCode"])
                        ->with(['axRelation' => function ($axRelationQuery) use ($currentWarehouse) {
                            $axRelationQuery->where('DefaultStore',  $currentWarehouse["warehouseCode"])
                                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        }]);
                    }
                ])
                ->with("Customer") 
                ->with(['payments' => function($q) use($currentWarehouse){
                    $q->where("sum",'>', 0)
                    ->where("clientCode", $currentWarehouse["clientCode"]);
                }])
                ->where("type", "ORDER")
                ->where("isSynccarePos", 1)
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0) 
                ->when($req->keywords != '', function ($q) use($req) {
                    return $q->where('number', $req->keywords)
                            ->orWhere('clientName', 'like', '%'.$req->keywords.'%');
                })
                ->where(function ($q) use ($filterData, $req) {
                    // $q->where('clientCode', $this->api->client->clientCode);
                    foreach ($filterData as $keys => $value) {
                        
                        if ($value != null )  {

                            if($keys == "isExpress" && $value == 2){
                                // dd($)
                            }else{
                                if($req->strictFilter == true){
                                    $q->Where($keys, $value);
                                }else{
                                    $q->Where($keys, 'LIKE', '%'.$value.'%');
                                }
                            }  
                        }
                    }
                }) 
                ->select(
                    [
                        'id',
                        'salesDocumentID',
                        'type',
                        'warehouseID',
                        'warehouseName',
                        'number',
                        'date',
                        'time',
                        'clientID',
                        'clientName',
                        'clientEmail',
                        'total',
                        'attributes',
                        'created_at',
                        'isExpress',
                        'readyToFulfill',
                        'pickedOrder',
                        'added',
                        'lastModified',
                        'isPrinted',
                        DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink")
                    ]
                    )
                ->orderBy("salesDocumentID", 'asc')
                // ->toSql();
                // ->first();
                ->paginate($pagination);
        // dd($datas);
        return $this->successWithData($datas);
        
    }

    public function filterOrderV2($req){
        dd("API V2");
        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }
        // return $this->checkWarehouseID($req);

        $filterData = $req->except(['warehouseID','keywords','page','strictFilter', 'version']);
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 5;
         
        $datas = SalesDocument:://with("SalesDetails.axRelation")
                with([
                    'SalesDetails' => function ($salesDetailsQuery) use ($currentWarehouse) {
                        $salesDetailsQuery->with(['axRelation' => function ($axRelationQuery) use ($currentWarehouse) {
                            $axRelationQuery->where('DefaultStore',  $currentWarehouse["warehouseCode"])
                                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        }]);
                    }
                ])
                ->with("Customer") 
                ->where("type", "ORDER")
                ->where("isSynccarePos", 1)
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0) 
                ->when($req->keywords != '', function ($q) use($req) {
                    return $q->where('number', $req->keywords)
                            ->orWhere('clientName', 'like', '%'.$req->keywords.'%');
                })
                ->where(function ($q) use ($filterData, $req) {
                    // $q->where('clientCode', $this->api->client->clientCode);
                    foreach ($filterData as $keys => $value) {
                        
                        if ($value != null )  {

                            if($keys == "isExpress" && $value == 2){
                                // dd($)
                            }else{
                                if($req->strictFilter == true){
                                    $q->Where($keys, $value);
                                }else{
                                    $q->Where($keys, 'LIKE', '%'.$value.'%');
                                }
                            }  
                        }
                    }
                }) 
                ->select(
                    [
                        'id',
                        'salesDocumentID',
                        'type',
                        'warehouseID',
                        'warehouseName',
                        'number',
                        'date',
                        'time',
                        'clientID',
                        'clientName',
                        'clientEmail',
                        'total',
                        'attributes',
                        'created_at',
                        'isExpress',
                        'readyToFulfill',
                        'pickedOrder',
                        DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink")
                    ]
                    )
                ->orderBy("salesDocumentID", 'asc')
                // ->toSql();
                // ->first();
                ->paginate($pagination);
        // dd($datas);
        return $this->successWithData($datas);
        
    }

    public function expressOrder($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }
        
        $customExcept = Except::$except;
        $customExcept[] = "warehouseID";
        $requestData = $req->except($customExcept);

        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'salesDocumentID';
        }

        // if(isset($req->warehouseID) == 0 && $req->warehouseID == ''){
        //     return $this->failWithMessage("Invalid Warehouse ID!");
        // }   
 
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $orders = SalesDocument:://with("SalesDetails.axRelation")
                with([
                    'SalesDetails' => function ($salesDetailsQuery) use ($currentWarehouse) {
                        $salesDetailsQuery->where("clientCode", $currentWarehouse["clientCode"])
                        ->with(['axRelation' => function ($axRelationQuery) use ($currentWarehouse) {
                            $axRelationQuery->where('DefaultStore',  $currentWarehouse["warehouseCode"])
                                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        }]);
                    }
                ])
                ->with("Customer")
                ->with(['payments' => function($q) use($currentWarehouse){
                    $q->where("sum",'>', 0)
                    ->where("clientCode", $currentWarehouse["clientCode"]);
                }])
                // ->addSelect("newsystem_sales_documents.*","newsystem_sales_document_details.*","newsystem_product_variation_live.erplyID")
                // ->where("clientCode", $this->api->client->clientCode)
                ->where("clientCode", $currentWarehouse["clientCode"])
                ->where("type", "ORDER")
                ->where("isSynccarePos", 1)
                ->where("isExpress", 1)
                // ->where("warehouseID", $req->warehouseID)
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0)
                ->where("readyToFulfill", 0) 
                ->where("isExpress", 1) 
                ->select('id','salesDocumentID','type','warehouseID','warehouseName','number','date','time','clientID','clientName','clientEmail','total','attributes',
                    'created_at',
                    'isExpress',
                    'readyToFulfill',
                    'pickedOrder',
                    'isPrinted',
                    'created_at',
                    'updated_at',
                    'lastModified',
                    'added',
                    DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=', newsystem_sales_documents.warehouseID,'&salesDocumentID=',newsystem_sales_documents.salesDocumentID) END as pickingSlipLink")
                )->where(function ($q) use ($requestData, $req) {
                    // $q->where('clientCode', $this->api->client->clientCode);
                    foreach ($requestData as $keys => $value) {
                        if ($value != null) { 
                            if($req->strictFilter == true){
                                $q->Where($keys, $value);
                            }else{
                                $q->Where($keys, 'LIKE', '%'.$value.'%');
                            }
                            // 'like', '%' . $value . '%'); 
                        }
                    }
                })
                ->orderBy($req->sort_by, $req->direction)
                ->paginate($pagination); 
        
        return $this->successWithData($orders);
    }

    public function orderCount($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }
        

        if(isset($req->warehouseID) == 0 && $req->warehouseID == ''){
            return $this->failWithMessage("Invalid Warehouse ID!");
        }


        $date = "1Day"; 
        $targetDate = date("Y-m-d");
        if (isset($date) && in_array($date, ["1Day", "3Day", "5Day", "7Day"])) {
            $daysToAdd = intval(substr($date, 0, -3)); // Extract the numeric part from the date string
            $daysToAdd = $daysToAdd - 1; 
            $targetDate = date("Y-m-d", strtotime("-$daysToAdd days")); // Calculate the target date
        
            // $q->where("date", ">=", $targetDate);
        }

        $smsNotif = MessageNotification::where("clientCode", $currentWarehouse["clientCode"])->where("warehouseID", $currentWarehouse["warehouseID"])->count();
        $emailNotif = EmailNotification::where("clientCode", $currentWarehouse["clientCode"])->where("warehouseID", $currentWarehouse["warehouseID"])->count();

        
        // $data = SalesDocument::select(
        //     DB::raw('COALESCE(sum(case when readyToFulfill = 0 and isExpress = 0 and isPrinted = 0 then 1 else 0 end), 0) as warehouseOrder'),  
        //     DB::raw('COALESCE(sum(case when isPrinted = 1 and readyToFulfill = 0 then 1 else 0 end), 0) as preparingOrder'),  
        //     DB::raw('COALESCE(sum(case when date >= "' . $targetDate . '" and type = "ORDER" and isSynccarePos = 1 and readyToFulfill = 1  then 1 else 0 end), 0) as fulfilledOrder'),
        //     DB::raw('COALESCE(sum(case when readyToFulfill = 0 and  isExpress = 1 and isPrinted = 0 then 1 else 0 end), 0) as expressOrder'),
        //     DB::raw('COALESCE(sum(case when readyToFulfill = 1 and  pickedOrder = 0 then 1 else 0 end), 0 ) as readyToBePickedOrder'),
        //     // DB::raw('MAX(salesDocumentID) as latestSalesDocumentID'),
        //     // DB::table("newsystem_inventory_transfers")->where("warehouseFromID", $req->warehouseID)->orWhere("warehouseToID", $req->warehouseID)->count() 
        //     DB::raw('
        //         MAX(CASE WHEN isExpress = 0 THEN salesDocumentID END) as latestSalesDocumentID,
        //         MAX(CASE WHEN isExpress = 1 THEN salesDocumentID END) as latestExpressSalesDocumentID
        //     ')
        // ) 
        $data = SalesDocument::select(
            DB::raw('
                COALESCE(SUM(CASE WHEN readyToFulfill = 0 AND isExpress = 0 AND isPrinted = 0 THEN 1 ELSE 0 END), 0) as warehouseOrder,
                COALESCE(SUM(CASE WHEN isPrinted = 1 AND readyToFulfill = 0 THEN 1 ELSE 0 END), 0) as preparingOrder,
                COALESCE(SUM(CASE WHEN date >= ? AND type = "ORDER" AND isSynccarePos = 1 AND readyToFulfill = 1 THEN 1 ELSE 0 END), 0) as fulfilledOrder,
                COALESCE(SUM(CASE WHEN readyToFulfill = 0 AND isExpress = 1 AND isPrinted = 0 THEN 1 ELSE 0 END), 0) as expressOrder,
                COALESCE(SUM(CASE WHEN readyToFulfill = 1 AND pickedOrder = 0 THEN 1 ELSE 0 END), 0) as readyToBePickedOrder,
                MAX(CASE WHEN isExpress = 0 THEN salesDocumentID ELSE NULL END) as latestSalesDocumentID,
                MAX(CASE WHEN isExpress = 1 THEN salesDocumentID ELSE NULL END) as latestExpressSalesDocumentID
            ')
        ) 
        ->addBinding([$targetDate], 'select')
        ->where("type", "ORDER")
        ->where("isSynccarePos", 1)
        ->where("warehouseID", $currentWarehouse["warehouseID"])
        ->where("clientCode", $currentWarehouse["clientCode"])
        ->where("deleted", 0)
        // ->where("readyToFulfill", 0)
        // ->where("isExpress", 0)
        ->first();

        // $latestInstore = SalesDocument::select(DB::raw('MAX(salesDocumentID) as latestSalesDocumentID'),)
        // ->where("isExpress", 0)
        // ->where("type", "ORDER")
        // ->where("isSynccarePos", 1)
        // ->where("warehouseID", $currentWarehouse["warehouseID"])
        // ->where("clientCode", $currentWarehouse["clientCode"])
        // ->where("deleted", 0)
        // ->first();

        // $latestExpress = SalesDocument::select(DB::raw('MAX(salesDocumentID) as latestExpressSalesDocumentID'),)
        // ->where("isExpress", 1)
        // ->where("type", "ORDER")
        // ->where("isSynccarePos", 1)
        // ->where("warehouseID", $currentWarehouse["warehouseID"])
        // ->where("clientCode", $currentWarehouse["clientCode"])
        // ->where("deleted", 0)
        // ->first();
        // dd($latestExpress);

        // Check if $data is not null
        if ($data) {
            // Convert the result to an array
            $resultArray = $data->toArray();
        } else {
            // Handle the case where there are no matching records (first() returned null)
            $resultArray = [];
        }

        // Count records from the newsystem_inventory_transfers table
        $inventoryTransferCount = DB::table("newsystem_inventory_transfers")
            ->where("clientCode", $currentWarehouse["clientCode"])
            ->where("warehouseFromID", $currentWarehouse["warehouseID"])
            ->orWhere("warehouseToID", $currentWarehouse["warehouseID"])
            ->count();
            // ->where("warehouseID", $currentWarehouse["warehouseID"])
            // ->where("clientCode", $currentWarehouse["clientCode"])

        // Add the inventory transfer count to the result array
        $resultArray['inventoryTransferCount'] = $inventoryTransferCount;
        $resultArray['sms'] = $smsNotif;
        $resultArray['email'] = $emailNotif;
        // if($latestExpress){
        //     $resultArray['latestExpressSalesDocumentID'] = $latestExpress->latestExpressSalesDocumentID;
        // }else{
        //     $resultArray['latestExpressSalesDocumentID'] = 0;
        // }
        
        // if($latestInstore){
        //     $resultArray['latestSalesDocumentID'] = $latestInstore->latestSalesDocumentID;
        // }else{
        //     $resultArray['latestSalesDocumentID'] = 0;
        // }

        
        
        return $this->successWithData($resultArray);
        // dd($data);
    }

    public function getTransferOrderFrom($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }

        $limit = $req->recordsOnPage ? $req->recordsOnPage : 20;

        $datas = InventoryTransfer::with("TransferLine.ProductDetails")->where("clientCode", $currentWarehouse["clientCode"])->where("warehouseFromID", $currentWarehouse["warehouseID"])->orderBy("inventoryTransferID", 'asc')->paginate($limit);

        return $this->successWithDataAndMessage("Inventory Transfer From Location", $datas);

    }

    public function getTransferOrderTo($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }

        $limit = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $datas = InventoryTransfer::where("clientCode", $currentWarehouse["clientCode"])->where("warehouseToID", $currentWarehouse["warehouseID"])->orderBy("inventoryTransferID", 'asc')->paginate($limit);

        return $this->successWithDataAndMessage("Inventory Transfer From Location", $datas);

    }
    

}
