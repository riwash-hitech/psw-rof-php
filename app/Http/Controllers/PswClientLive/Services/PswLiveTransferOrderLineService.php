<?php

namespace App\Http\Controllers\PswClientLive\Services;
 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;
use App\Classes\UserLogger;
use App\Models\PswClientLive\Local\AxSyncDatetime;
use App\Models\PswClientLive\Local\LiveSupplier;
use App\Models\PswClientLive\Local\LiveTransferOrderLine;
use App\Models\PswClientLive\Local\TempSupplier;
use App\Models\PswClientLive\Local\TempTransferOrderLine;
use App\Models\PswClientLive\Supplier;
use App\Models\PswClientLive\TransferOrderLine;
use Exception;

class PswLiveTransferOrderLineService
{

    use ResponseTrait;
    protected $customer;
    protected $letsLog;



    public function __construct(UserLogger $logger)
    {
        $this->letsLog = $logger;
    }

    function escapeFunc($val)
    {
        // $val = trim($val);
        // $val = str_replace("'", "\'", $val);
        // $val = str_replace('"', '\"', $val);
        return DB::getPdo()->quote($val);
        // return $val;
    }

    private function makeNullDate($date){
        if($date == ''){
            return '0000-00-00';
        }
        return $date;
    }

    public function makeTransferOrderFile()
    {
        try{
            $limit = 200000;
            info("*************************************** AX to Synccare : Transfer Order Lines by Last Modified Cron Called. *******************************************");
            // echo "hello";
            // die;

            $path = public_path('PswLiveTemp');

            File::delete($path . '/transferOrderLines.txt');

            if (!File::exists($path)) {

                File::makeDirectory($path);
            }
 
            $lastModified = AxSyncDatetime::where("id", 1)->first()->transfer_order;
            // echo $lastModified;
            // die;
            if('0000-00-00 00:00' == $lastModified || '0000-00-00' == $lastModified || is_null($lastModified) == true || $lastModified == '0000-00-00 00:00:00.000'){
                $datas = TransferOrderLine::orderBy("Line Modified DateTime", 'asc')->limit($limit)->get();
                
            }else{
                $datas = TransferOrderLine::where("Line Modified DateTime", '>=', $lastModified)->orderBy("Line Modified DateTime", 'asc')->limit($limit)->get();
                 
            }

            info("*************************************** AX to Synccare : Transfer Order Lines ".count($datas)." Rows Fetched *******************************************");
            // dd($datas);

            // dd($datas);
            $chunkDatas = $datas->chunk(500);

            foreach ($chunkDatas as $datas) {

                $content = 'Insert into `temp_transfer_order_lines`(`TransferNumber`,`TransferStatus`,`ItemNumber`, `Configuration`,`Colour`,`Size`,`FromWarehouse`,`FromLocation`,`Quantity`,`ShippedQty`,`ShipRemaining`,`ToWarehouse`,`ReceivedQty`,`ReceivedRemain`,`HeaderModifiedDateTime`,`LineModifiedDateTime`,`ERPLYSKU`,`INVENTTRANSID`,`pendingProcess`) VALUES ';

                $q = null;
                $flag = 0;
                foreach ($datas as $key => $value) {
                    $flag = $flag + 1;
                    //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                    // $key = $datas->last() == $value ? ';' : ',';
                    $key = $flag == 500 ? ';' : ',';
                    $sep = $datas->last() == $value ? ';' : ',';

                    if($chunkDatas->last() == $datas) {
                        if($datas->last() == $value){
                            $sep = '';
                        }
                    }

                    $q .= '( "' . $value['Transfer Number'] . '","' . $value['Transfer Status'] . '",  "' . $value['Item Number'] . '","' . $value['Configuration'] . '","' . $value['Colour'] . '","' . $value['Size'] . '","' . $value['From Warehouse'] . '","' . $value['From Location'] . '","' . $value['Quantity'] . '","' . $value['Shipped Qty'] . '","' . $value['Ship Remaining'] . '","' . $value['To Warehouse'] . '","' . $value['Received Qty'] . '","' . $value['Receive Remain'] . '","' . $this->makeNullDate($value['Header Modified DateTime']) . '","' . $this->makeNullDate($value['Line Modified DateTime']) . '","' . $value['ERPLY SKU'] . '","' . $value['INVENTTRANSID'] . '", 1)' . $sep;
                }

                $content = $content . '' . $q . '' . "\n";
                // if($chunkDatas->last() == $datas){
                //     $content = rtrim($content, );
                // }
                File::append($path . '/transferOrderLines.txt', $content);
            }

            info("*************************************** AX to Synccare : Transfer Order Lines File Generated Successfully. *******************************************");
        
            return $this->readTransferOrderFile();
        }catch(Exception $e){
            info($e->getMessage());
            // dd($e);
        }
        //return $this->successWithMessage("transferOrderLines File Generated Successfully.");
    }

    public function readTransferOrderFile(){

        info("*************************************** AX to Synccare : Preparing for Inserting Transfer Order Lines Value to Temp Table. *******************************************");


        //truncate temp table if all data processed
        $check = TempTransferOrderLine::where("pendingProcess", 1)->first();
        if(!$check){
            TempTransferOrderLine::truncate();
            info("Temp Transfer Order Line Truncated...");
        }
        $path = public_path('PswLiveTemp/transferOrderLines.txt');

        if (File::exists($path)) {

            // $count = TempTransferOrderLine::where('pendingProcess', 0)->count();

            // if ($count < 1) {
                // TempTransferOrderLine::truncate();
                $file = File::get($path);

                $sqls = explode(";\n", $file);

                foreach ($sqls as $sql) {

                    if ($sql != '') {
                        DB::connection('mysql2')->select($sql);
                    }
                }


                //now updating lastmodified date
                $latest = TempTransferOrderLine::orderBy("LineModifiedDateTime", 'desc')->first();
                AxSyncDatetime::where("id", 1)->update(["transfer_order" => $latest->LineModifiedDateTime]);

                info("*************************************** AX to Synccare : Transfer Order Lines File Executed Successfully. *******************************************");
                return $this->successWithMessage("TransferOrderLine File Executed Successfully.");
            // }
        } else {
            info("TransferOrderLine file not found.");
            return response("TransferOrderLine file not found");
            echo "no data";
            die;
        }
    }


    public function syncTransferOrderToNewsystem(){
        try{
            $datas = TempTransferOrderLine::where("pendingProcess", 1)->limit(500)->get();
            if($datas->isEmpty()){
                // info("")
                return response("Empty Temp Tranfer Order ");
            }

            foreach($datas as $data){
                $details = array(
                    "TransferNumber" => $data->TransferNumber,
                    "TransferStatus" => $data->TransferStatus,
                    "ItemNumber" => $data->ItemNumber, 
                    "Configuration" => $data->Configuration,
                    "Colour" => $data->Colour,
                    "Size" => $data->Size,
                    "FromWarehouse" => $data->FromWarehouse,
                    "FromLocation" => $data->FromLocation,
                    "Quantity" => $data->Quantity ? $data->Quantity : 0,
                    "ShippedQty" => $data->ShippedQty ? $data->ShippedQty : 0,
                    "ShipRemaining" => $data->ShipRemaining ? $data->ShipRemaining : 0,
                    "ToWarehouse" => $data->ToWarehouse,
                    "ReceivedQty" => $data->ReceivedQty,
                    "ReceivedRemain" => $data->ReceivedRemain,
                    "HeaderModifiedDateTime" => $data->HeaderModifiedDateTime,
                    "LineModifiedDateTime" => $data->LineModifiedDateTime,
                    "ERPLYSKU" => $data->ERPLYSKU,
                    // "pendingProcess" => 1,
                    "INVENTTRANSID" => $data->INVENTTRANSID,
                );

                if (str_contains($data->TransferNumber, 'ERP')) { 
                    $details["isErplyTO"] = 1;     
                }

                LiveTransferOrderLine::updateOrcreate(
                    [
                        "TransferNumber" => $data->TransferNumber,
                        "ERPLYSKU" => $data->ERPLYSKU
                    ],
                    $details
                );
                $data->pendingProcess = 0;
                $data->save();
            }
            info("Trander Order Lines Synccing to Newsystem Table.");
        }catch(Exception $e){
            info("Error Transfer Order Temp to Current ".$e->getMessage());
        }

        return $this->successWithMessage("Trander Order Lines Synced to Newsystem Table Successfully.");
    }

    public function syncTransferOrderByLastModified(){
        $latest = LiveTransferOrderLine::orderBy("LineModifiedDateTime", 'desc')->first();
        // dd($latest);
        if($latest){
            $datas = TransferOrderLine::where("Line Modified DateTime", ">", $latest->LineModifiedDateTime)->orderBy("Line Modified DateTime",'asc')->limit(200)->get();
        }else{
            $datas = TransferOrderLine::orderBy("Line Modified DateTime",'asc')->limit(500)->get();
        }
        // dd($datas);
        if(count($datas) < 1){
            info("AX to Synccare : All TO Synced");
            die;
        }
        foreach($datas as $data){
            $details = array(
                "TransferNumber" => $data["Transfer Number"],
                "TransferStatus" => $data["Transfer Status"],
                "ItemNumber" => $data["Item Number"], 
                "Configuration" => $data["Configuration"],
                "Colour" => $data["Colour"],
                "Size" => $data["Size"],
                "FromWarehouse" => $data["From Warehouse"],
                "FromLocation" => $data["From Location"],
                "Quantity" => $data["Quantity"],
                "ShippedQty" => $data["Shipped Qty"],
                "ShipRemaining" => $data["Ship Remaining"],
                "ToWarehouse" => $data["To Warehouse"],
                "ReceivedQty" => $data["Received Qty"],
                "ReceivedRemain" => $data["Received Remain"],
                "HeaderModifiedDateTime" => $data["Header Modified DateTime"],
                "LineModifiedDateTime" => $data["Line Modified DateTime"],
                "ERPLYSKU" => $data["ERPLY SKU"],
                "INVENTTRANSID" => $data["INVENTTRANSID"],
                "pendingProcess" => 1
            );

             //check if erply transfer order
            if (str_contains($data["Transfer Number"], 'ERP')) { 
                $details["isErplyTO"] = 1;     
            }

            LiveTransferOrderLine::updateOrcreate(
                [
                    "TransferNumber" => $data["Transfer Number"],
                    // "ItemNumber" => $data["Item Number"], 
                    // "Configuration" => $data["Configuration"],
                    // "Colour" => $data["Colour"],
                    // "Size" => $data["Size"],
                    "ERPLYSKU" => $data["ERPLY SKU"],
                ],
                $details
            ); 
        }
        info("Transfer Order Lines Synced to Synccare Table");
        return $this->successWithMessage("Transfer Order Lines Synced to Synccare Table Successfully.");
    }




      




}
