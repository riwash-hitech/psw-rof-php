<?php
namespace App\Http\Controllers\PswClientLive\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use App\Models\PAEI\PurchaseDocument;
use App\Models\PAEI\PurchaseDocumentDetail;
use App\Models\PAEI\ServerInfo;
use App\Models\PswClientLive\AxCustomer;
use App\Models\PswClientLive\AxPurchaseOrder;
use App\Models\PswClientLive\Local\LivePurchaseOrder;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use App\Models\PswClientLive\Local\TempPurchaseOrder;
use App\Traits\AxTrait;
use DateTime;
use DateTimeZone;

class AxPurchaseOrderService{

    protected $ax_purchase;
    use AxTrait;
    protected $api;

    public function __construct(EAPIService $api){
        $this->api = $api;
    }

    public function syncPurchaseOrder(){
        //  echo "hello";
        //  die;
        $datas = PurchaseDocument::where("clientCode", $this->api->client->clientCode)->where("axPending", 1)->where('type','PRCINVOICE')->where("isErplyPo", 0)->limit(3)->get();
        // dd($datas);

        if($datas->isEmpty()){
            info("Synccare to AX : All Purchase Order Synced");
            return response("All Purchase Order Synced");
        }

        foreach($datas as $data){
            $warehouse = LiveWarehouseLocation::where("erplyID", $data->warehouseID)->first();
            //getting unique id

            $isBaseDoc = false;
            $isAxPO = false;
            $baseDocs = [];
            if($data->baseDocuments != ''){
                $isBaseDoc = true;
                $baseDocs = json_decode($data->baseDocuments, true);

                 $gettingBaseFromTable = PurchaseDocument::where("clientCode", $this->api->client->clientCode)->where("pdID", $baseDocs[0]["id"])->first();
 

                 if($gettingBaseFromTable){
                    if($gettingBaseFromTable->attributes){

                        foreach(json_decode($gettingBaseFromTable->attributes,true) as $att){
                            if($att["attributeName"] == "AXPURCHASEID"){
                                $isAxPO = true;
                            }
                        }
                    }
                 }
            }

            if($isAxPO == false){
                //update flag in database
                $data->isErplyPo = 1;
                $data->save();
                info("Erply PO Found.");
                die;
                break;
            }


            $details = array(
                "DATAAREAID" => "psw",
                // "RECVERSION" => "", 
                "PURCHID" => $isBaseDoc == true ? $baseDocs[0]["number"] : $data->number,//$data->pdID,
                // "INVENTTRANSID" => $data->pdID,
                // "PURCHRECEIVEDNOW" => "",
                "STATUS" => 1,
                "STOREID" => $warehouse->StoreID,
                // "DBACTION" => 1,
                "ENTITY" => $warehouse->ENTITY,
                "TERMINALID" => $data->employeeID,//$warehouse->StoreID,
                // "TRANSACTIONID" => $data->pdID,
                "TRANSDATE" => $data->date,
                "PACKINGSLIPID" => $data->number,
                "MODIFIEDDATETIME" => (new DateTime($data->lastModified, new DateTimeZone($warehouse->timeZone ? $warehouse->timeZone : ServerInfo::where("clientCode",$this->api->client->clientCode)->first()->timezone)))->setTimezone(new DateTimeZone('GMT'))->format('Y-m-d H:i:s'),
                "MODIFIEDBY" => "ERPLY",
                "CREATEDDATETIME" =>  (new DateTime($data->added, new DateTimeZone($warehouse->timeZone ? $warehouse->timeZone : ServerInfo::where("clientCode",$this->api->client->clientCode)->first()->timezone)))->setTimezone(new DateTimeZone('GMT'))->format('Y-m-d H:i:s'),
                "CREATEDBY" => "ERPLY", 
            );
            

            // dd($details);
            // die;

            $lines = PurchaseDocumentDetail::where("clientCode", $this->api->client->clientCode)->where("purchaseDocumentID", $data->pdID)->get();
            $totQty = 0;
            $parentDetails = json_decode($data->baseDocuments,true);
            info( $parentDetails[0]['number']);
            // $livePurchaseOrder = LivePurchaseOrder::where("purchaseDocumentID", $parentDetails[0]['id'])->first();
            // dd($livePurchaseOrder);
            $temp_details = LivePurchaseOrder::where("PURCHID", $parentDetails[0]['number'])->get();
            foreach($lines as $key => $l){

                //checking if lines exist
                $isExist = false;
                if($l->axID > 0){
                    $check = AxPurchaseOrder::where("RECID", $l->axID)->first();
                    if($check){
                        $isExist = true;
                    }
                }

                // $totQty = $totQty + $l->amount;
                $recid = $this->getRecID(50311); 

                if($isExist == true){
                    $details["DBACTION"] = $l->deleted == 1 ? 3 : 2;
                }else{
                    $details["DBACTION"] = 2;
                }

                $details["RECID"] = $recid["NEXTVAL"];
                $details["TRANSACTIONID"] = $l->stableRowID;
                $details["PURCHRECEIVEDNOW"] = $l->amount;
                $details["INVENTTRANSID"] = $temp_details[$key]["INVENTTRANSID"];
                //now getting lines details from temp table
                
                // $temp_details = TempPurchaseOrder::where("PURCHID", $livePurchaseOrder->PURCHID)->where("ERPLYSKU", $livePurchaseOrder->ERPLYSKU)
                // dd($details);
                // die;
                AxPurchaseOrder::create($details);

                $verify = AxPurchaseOrder::where("RECID", $recid["NEXTVAL"])->first();
                if($verify){

                    $rowCount = AxPurchaseOrder::count();
                    $nextVal = $rowCount + $recid["NEXTVAL"];
                    $updateNextval = $this->updateRecID(50311, $nextVal);
                    if($updateNextval == true){
                        $l->axID =  $recid["NEXTVAL"];
                        $l->save();
                        info("SystemSequence Table Updated");
                        // $data->axPurchaseID = $recid["NEXTVAL"];
                        $data->axPending = 0; 
                        $data->save();
                    
                    }else{
                        
                        info("SystemSequence Table Update Failed");
                    }

                }

            }
            


        }

        return response("Purchase Orders Synced to AX Successfully.");

    }


}