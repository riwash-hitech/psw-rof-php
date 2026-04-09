<?php
namespace App\Http\Controllers\PswClientLive\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use App\Models\PswClientLive\AxCustomer;
use App\Models\PswClientLive\AxSystemSequence;
use Illuminate\Support\Facades\DB;
use App\Traits\AxTrait;
use App\Classes\UserLogger;
use App\Http\Controllers\Paei\Services\GetCustomerService;
use App\Models\PAEI\Cashin;
use App\Models\PAEI\InventoryTransfer;
use App\Models\PAEI\InventoryTransferLine;
use App\Models\PswClientLive\AxTransferOrder;
use App\Models\PswClientLive\AxTransferOrderLine;
use App\Models\PswClientLive\Local\LiveItemLocation;
use App\Models\PswClientLive\Local\LiveOnHandInventory;
use App\Models\PswClientLive\Local\LiveProductGenericVariation;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveTransferOrderLine;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;

class AxTransferOrderService{

    use AxTrait; 
    protected $api;

    public function __construct(EAPIService $api){ 
        $this->api = $api;
    }

    protected function make10($number){
        $val = "ERP";
        $leng = strlen((string)$number) + 3;
        
        for($i= $leng; $i<=10;$i++){
            $val .= "0";
        }

        return $val."".(string)$number;
    }

    
    public function syncTransferOrder(){

        $isErplyTO = true;
        $datas = InventoryTransfer::where('type','TRANSFER_ORDER')->where("date",">", "2023-09-24")->where('isErplyTO', 1)->where('axPending', 1)->limit(3)->get();
        if($datas->isEmpty()){
            $isErplyTO = false;
            $datas = InventoryTransfer::where('type','TRANSFER')->where("date",">", "2023-09-24")->where('axPending', 1)->where('readyForAX', 1)->limit(3)->get();
            // if(count($datas) 
            // die;
        }
        if($datas->isEmpty()){
            $this->syncTOInventTransID();
            info("All Transfer Order Synced to AX");
            return response("All Transfer Order Synced to AX");
        }
        // dd($datas);
        foreach($datas as $data){
            $fromID = LiveWarehouseLocation::where("erplyID", $data->warehouseFromID)->first();
            $toID = LiveWarehouseLocation::where("erplyID", $data->warehouseToID)->first();
            $recid = $this->getRecID(50312);

            $isCSR = false;
            $erplyTransferID = '';


            $isExist = false;
            if($data->axTransferID > 0){
                $chk = AxTransferOrder::where("RECID", $data->axTransferID)->first();
                if($chk){
                    $isExist = true;
                }
            }
            
            $parentTransfer = InventoryTransfer::where("inventoryTransferID", $data->inventoryTransferOrderID)->first();

            $isPswAxTransferOrder = false;
            $pswAxTransferNumber = '';

            if(@$parentTransfer->attributes != ''){

                $attributes = json_decode($parentTransfer->attributes, true);
                foreach($attributes as $tt){
                    if($tt["attributeName"] == "TransferNumber"){
                        $isPswAxTransferOrder = true;
                        $pswAxTransferNumber = $tt["attributeValue"];
                    }
                }
                // $pswAxTransferNumber = $attributes[0]["attributeValue"];
                // info("attribut transfer");
                // dd($attributes);
                // dd($data->inventoryTransferOrderID);
                // $details["RECID"] = $recid["NEXTVAL"];
                if($isPswAxTransferOrder == true){
                    $axTO = LiveTransferOrderLine::where("TransferNumber", $pswAxTransferNumber)->first();

                    if($axTO->TransferStatus == 0 || $axTO->TransferStatus == 1 || $axTO->TransferStatus == 2){
                        $isCSR = true;
                    }
                }else{
                    $isPswAxTransferOrder = false;
                    $erplyTransferID = $this->make10($data->inventoryTransferID);
                }
            }else{
                $isPswAxTransferOrder = false;
                $erplyTransferID = $this->make10($data->inventoryTransferID);
            }

            // $INVENTLOCATIONIDTRANSIT = '';
            // if($isPswAxTransferOrder == false){
            //     $INVENTLOCATIONIDTRANSIT = $toID->INVENTLOCATIONIDTRANSIT;
            // }else{

            //     $INVENTLOCATIONIDTRANSIT = $toID->INVENTLOCATIONIDTRANSIT;
            // }
            
            // echo $isPswAxTransferOrder == true ? "AX" : "erply";
            // die;
            

            $transferDetails = array(
                "DATAAREAID" => "psw",
                // "RECVERSION" => "",
                "RECID" => $recid["NEXTVAL"],
                "DBACTION" => $isPswAxTransferOrder == true ? 2 : ($data->type == "TRANSFER" ? 2 : 1), 
                "STATUS" => 1,
                "ENTITY" => @$toID->ENTITY,
                "STOREID" => $toID->StoreID,
                "TERMINALID" => $toID->StoreID,
                "TRANSACTIONID" =>  $data->inventoryTransferID,
                "TRANSFERID" =>  $isPswAxTransferOrder == true ? $axTO->TransferNumber : $erplyTransferID,
                "INVENTLOCATIONIDFROM" => $fromID->LocationID,
                "INVENTLOCATIONIDTO" => $toID->LocationID,
                "INVENTLOCATIONIDTRANSIT" =>  $toID->INVENTLOCATIONIDTRANSIT,//$isCSR == true ? $toID->INVENTLOCATIONIDTRANSIT : $fromID->INVENTLOCATIONIDTRANSIT,
                // "DLVMODEID" => "PSW Van",
                "SHIPDATE" => $data->date,
                "DELIVERYINSTRUCTIONS" => $data->notes,
                "SHIPNOW" => $data->type == "TRANSFER" ? 0 : 1,
                "RECEIVEDATE" => $data->added,
                "RECEIVENOW" => $data->type == "TRANSFER" ? 1 : 0,
                "MODIFIEDDATETIME" => $data->lastModified,
                "MODIFIEDBY" => "ERPLY",
                "CREATEDDATETIME" => $data->added,
                "CREATEDBY" => "ERPLY"
            );

            //now getting attributes
            if($data->attributes != ''){
                $toAtt = json_decode($data->attributes, true);
                foreach($toAtt as $tatt){
                    if($tatt["attributeName"] == "delivery_modes"){
                        $transferDetails["DLVMODEID"] = $tatt["attributeValue"];
                    }
                }
            }

            // dd($transferDetails);

            //creating transfer order

            AxTransferOrder::create($transferDetails);

            $verifyOrder = AxTransferOrder::where("RECID", $recid["NEXTVAL"])->first();
            if($verifyOrder){
                $rowCount = AxTransferOrder::count();
                $nextVal = $rowCount + $recid["NEXTVAL"];
                $updateNextval = $this->updateRecID(50312, $nextVal);
                if($updateNextval == true){
                    info("SystemSequence Table Updated");
                    $data->axTransferID = $recid["NEXTVAL"];
                    $data->axPending = 0;
                    $data->save();
                    
                    UserLogger::setChronLogNew('' , json_encode($verifyOrder, true), "Ax Transfer Order Created" );        
                }else{
                    
                    info("SystemSequence Table Update Failed");
                }
            }

            //for transfer Lines
           
            $erplyLines = InventoryTransferLine::where("clientCode", $this->api->client->clientCode)->where("created_at",'>','2023-06-01')->where("transferID", $data->inventoryTransferID)->get();

            foreach($erplyLines as $lines){
                $recidLine = $this->getRecID(50313);
                
                $isGeneric = 0;
                $proDetails = LiveProductVariation::where("erplyID", $lines->productID)->first();
                if(!$proDetails){
                    $isGeneric = 1;
                    $proDetails = LiveProductGenericVariation::where("erplyID", $lines->productID)->first();
                }

                //now getting inventtransID
                if($isPswAxTransferOrder == true){
                    $axITID = LiveTransferOrderLine::where("TransferNumber", $pswAxTransferNumber)
                                ->where("ERPLYSKU", $proDetails->ERPLYSKU)
                                ->first();
                    if(!$axITID){
                        $axITID = LiveTransferOrderLine::where("TransferNumber", $pswAxTransferNumber)
                                ->where("ERPLYSKU", $proDetails->ICSC)
                                ->first();
                    }
                }

                $locationInfo = '';
                if($data->type == "TRANSFER_ORDER"){

                    $erplyLocation = LiveItemLocation::where("warehouse", $fromID->LocationID)->where("ERPLYSKU", $isGeneric == 0 ?  $proDetails->ERPLYSKU : $proDetails->ICSC)->first();
                    if($erplyLocation){
                        $locationInfo = @$erplyLocation->issueLocation ? $erplyLocation->issueLocation : 'DEF';
                    }
                } 
                if($data->type == "TRANSFER"){

                    $erplyLocation = LiveItemLocation::where("warehouse", $toID->LocationID)->where("ERPLYSKU", $isGeneric == 0 ?  $proDetails->ERPLYSKU : $proDetails->ICSC)->first();
                    if($erplyLocation){
                        $locationInfo = @$erplyLocation->issueLocation ? $erplyLocation->issueLocation : 'DEF';
                    }
                } 

                // $binBay = LiveOnHandInventory::where("ERPLYSKU", $proDetails->ERPLYSKU)->where("Warehouse", $fromID->LocationID)->first();

                $lineDetails = array(
                    "DATAAREAID" => "psw",
                    // "RECVERSION" => "",
                    "RECID" => $recidLine["NEXTVAL"],
                    "TRANSFERID" => $isPswAxTransferOrder == true ? $axTO->TransferNumber : $erplyTransferID,
                    "ITEMID" => $proDetails->ITEMID,
                    "INVENTCOLORID" => $proDetails->ColourID,
                    "CONFIGID" => $proDetails->CONFIGID,
                    "INVENTSIZEID" => $proDetails->SizeID,
                    "WMSLOCATIONID" => $locationInfo,// @$binBay->Location ? @$binBay->Location : "DEF",
                    "QTYTRANSFER" => $lines->amount,
                    "STOREID" => $toID->StoreID,
                    "ENTITY" => $toID->ENTITY,
                    "TERMINALID" => $toID->StoreID,
                    "TRANSACTIONID" => $lines->stableRowID,
                    "QTYRECEIVENOW" => $data->type == "TRANSFER" ? $lines->amount : 0,
                    "QTYSHIPNOW" => $data->type == "TRANSFER" ? 0 : $lines->amount,
                    "MODIFIEDDATETIME" => $data->lastModified,
                    "MODIFIEDBY" => "ERPLY",
                    "CREATEDDATETIME" => $data->added,
                    "CREATEDBY" => "ERPLY"
                );
                if($isPswAxTransferOrder == true){
                    $lineDetails["INVENTTRANSID"] = $axITID->INVENTTRANSID;
                }
                
                if($data->type == "TRANSFER"){
                    //TO Originated from erply
                    if($isPswAxTransferOrder == false){
                        //now getting invent trans id from transfer order lines
                        $inventTransIDforErplyTransfer = InventoryTransferLine::where("clientCode", $this->api->client->clientCode)
                                                        ->where("transferID", $data->inventoryTransferOrderID)
                                                        ->where("productID", $lines->productID)
                                                        ->first();

                        $lineDetails["INVENTTRANSID"] = $inventTransIDforErplyTransfer->inventTransID;
                    }
                }
                // dd($lineDetails);
                // die;
                AxTransferOrderLine::create($lineDetails);

                $verifyOrder = AxTransferOrderLine::where("RECID", $recidLine["NEXTVAL"])->first();
                if($verifyOrder){
                    $rowCount = AxTransferOrderLine::count();
                    $nextVal = $rowCount + $recidLine["NEXTVAL"];
                    $updateNextval = $this->updateRecID(50313, $nextVal);
                    if($updateNextval == true){
                        info("SystemSequence Table Updated");
                        $lines->axLineID = $recidLine["NEXTVAL"];
                        // $lines->axPending = 0;
                        $lines->save();
                        
                        UserLogger::setChronLogNew('' , json_encode($verifyOrder, true), "Ax Transfer Order Line" );        
                    }else{
                        
                        info("SystemSequence Table Update Failed");
                    }
                }


            }
             
             


        }

        echo "Transfer Order Created";

        
        $this->syncTOInventTransID();
    }
 
    public function syncTOInventTransID(){

        info("Checking ERPLY TOs InventTransID");

        $erplyTOs = InventoryTransfer::where("clientCode", $this->api->client->clientCode)->where('type','TRANSFER')->where('readyForAX', 0)->where('axPending', 1)->limit(5)->get();
        // dd($erplyTOs);
        //now checking ax transfer line

        foreach($erplyTOs as $etos){
            $parentTOs = InventoryTransfer::where('inventoryTransferID', $etos->inventoryTransferOrderID)->where('axPending', 0)->first();
            //now getting parents TO
            // dd($parentTOs);
            $erplyTransferID = $this->make10($parentTOs->inventoryTransferID);
            // echo $erplyTransferID;
            // die;

            //first check if this TO exists in ax TOs

            $checkTo = LiveTransferOrderLine::where("TransferNumber", $erplyTransferID)->where("INVENTTRANSID",'<>','')->get();
            // dd($checkTo);
            //getting tos lines
            $erplyLines = InventoryTransferLine::where("clientCode", $this->api->client->clientCode)->where("transferID", $parentTOs->inventoryTransferID)->get();
            // dd($erplyLines);
            $isAllDone = false;
            //comparing row counts of TOs
            if(count($checkTo) == count($erplyLines)){

                // echo "Row Count Matched";
                // die;

                //All Transfer Order Lines Matched
                $updateRowCount = 0;
                foreach($erplyLines as $lines){

                    //first getting  
                    //product : generic or non-generic
                    $proDetails = LiveProductVariation::where("erplyID", $lines->productID)->first();
                    if(!$proDetails){
                        $proDetails = LiveProductGenericVariation::where("erplyID", $lines->productID)->first();
                    }   

                     
                    $axITID = LiveTransferOrderLine::where("TransferNumber", $erplyTransferID)
                                ->where("ERPLYSKU", $proDetails->ERPLYSKU)
                                ->first();
                    if(!$axITID){
                        $axITID = LiveTransferOrderLine::where("TransferNumber", $erplyTransferID)
                                ->where("ERPLYSKU", $proDetails->ICSC)
                                ->first();
                    }
                    // dd($axITID);
                    //if $ax transfer order line found then just update the inventtransid
                    if($axITID){

                        $lines->inventTransID = $axITID->INVENTTRANSID;
                        $lines->save();
                        $updateRowCount++;
                    }
                    
                }

                if($updateRowCount == count($erplyLines)){
                    $isAllDone = true;
                    $etos->readyForAX = 1;
                    $etos->save();
                }

            } 

            
            // die;
        }
    }


}