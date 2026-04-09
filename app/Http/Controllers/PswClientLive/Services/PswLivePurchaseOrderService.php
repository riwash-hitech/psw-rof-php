<?php

namespace App\Http\Controllers\PswClientLive\Services;

use App\Models\PswClientLive\Customer;
use App\Models\PswClientLive\CustomerRelation;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LivePurchaseOrder;
use App\Models\PswClientLive\Local\TempCustomer;
use App\Models\PswClientLive\Local\TempCustomerRelation;
use App\Models\PswClientLive\Local\TempPurchaseOrder;
use App\Models\PswClientLive\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;

class PswLivePurchaseOrderService
{

    use ResponseTrait;



    public function __construct()
    {
    }

    function escapeFunc($val)
    {
        // $val = trim($val);
        $val = str_replace("'", "\'", $val);
        $val = str_replace('"', '\"', $val);
        return $val;
    }

    public function makePurchaseOrderFile()
    {

        $path = public_path('PswLiveTemp');

        File::delete($path . '/purchaseOrders.txt');

        if (!File::exists($path)) {

            File::makeDirectory($path);
        }

        $datas = PurchaseOrder::get();//where("ERPLY SKU", "<>", '')->get();
        // dd($products);
        $chunkDatas = $datas->chunk(500);

        foreach ($chunkDatas as $datas) {

            $content = 'Insert into `temp_purchase_orders`(
                    `PURCHID`,
                    `PURCHSTATUS`,
                    `PSW_CROSSDOCKWAREHOUSE`,
                    `PURCHNAME`,
                    `ITEMID`,
                    `CONFIGID`,
                    `INVENTSIZEID`,
                    `INVENTCOLORID`,
                    `PURCHQTY`, 
                    `REMAINPURCHPHYSICAL`,
                    `LastModifiedDateTime`,
                    `PSW_REPORTWAREHOUSECAT`,
                    `ERPLYSKU`,
                    `INVENTTRANSID`,
                    `ICSC`,
                    `pendingProcess` 
                   ) VALUES ';

            $q = null;
            $count = 0;
            foreach ($datas as $key => $value) {
                $count = $count + 1;
                // $key = $datas->last() == $value ? ';' : ',';
                $key = $datas->last() == $value ? ';' : ',';

                if($chunkDatas->last() == $datas){
                    if($datas->last() == $value){
                        $key = '';
                    }
                }

                $q .= '( "' . $value['PURCHID'] . '",
                        "' . $value['PURCHSTATUS'] . '",
                        "' . $value['PSW_CROSSDOCKWAREHOUSE'] . '",
                        "' . $this->escapeFunc($value['PURCHNAME']) . '",
                        "' . $value['ITEMID'] . '",
                        "' . $value['CONFIGID'] . '",
                        "' . $value['INVENTSIZEID'] . '",
                        "' . $value['INVENTCOLORID'] . '",
                        "' . $value['PURCHQTY'] . '",
                        "' . $value['REMAINPURCHPHYSICAL'] . '",
                        "' . $value['Last Modified DateTime'] . '",
                        "' . $value['PSW_REPORTWAREHOUSECAT'] . '", 
                        "' . $value['ERPLY SKU'] . '", 
                        "' . $value['INVENTTRANSID'] . '", 
                        "' . $value['ICSC'] . '", 
                        1)' . $key;
            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/purchaseOrders.txt', $content);
        }

        return $this->successWithMessage("Purchase Order File Generated Successfully.");
    }
 

    public function readPurchaseOrdersFile()
    {
        // echo "hello sir ...";
        // die;
        $path = public_path('PswLiveTemp/purchaseOrders.txt');

        if (File::exists($path)) {

            $count = TempPurchaseOrder::where('pendingProcess', 0)->count();

            if ($count < 1) {
                TempPurchaseOrder::truncate();
                $file = File::get($path);

                $sqls = explode(";", $file);

                foreach ($sqls as $sql) {
                    // print_r($sql);
                    // die;
                    if ($sql != '') {
                        DB::connection('mysql2')->select($sql);
                    }
                }

                return $this->successWithMessage("Purchase Orders File Executed Successfully.");
            }
        } else {
            echo "no data";
            die;
        }
    }

    public function syncPurchaseOrderToNewsystem(){
        $datas = TempPurchaseOrder::where("pendingProcess", 1)->limit(500)->get();

        foreach($datas as $data){
            $details = array(
                "PURCHID" => $data->PURCHID,
                "PURCHSTATUS" => $data->PURCHSTATUS,
                "PSW_CROSSDOCKWAREHOUSE" => $data->PSW_CROSSDOCKWAREHOUSE,
                "PURCHNAME" => $data->PURCHNAME,
                "ITEMID" => $data->ITEMID,
                "CONFIGID" => $data->CONFIGID,
                "INVENTSIZEID" => $data->INVENTSIZEID,
                "INVENTCOLORID" => $data->INVENTCOLORID,
                "PURCHQTY" => $data->PURCHQTY,
                "REMAINPURCHPHYSICAL" => $data->REMAINPURCHPHYSICAL,
                "ERPLYSKU" => $data->ERPLYSKU,
                "LastModifiedDateTime" => $data->LastModifiedDateTime,
                "PSW_REPORTWAREHOUSECAT" => $data->PSW_REPORTWAREHOUSECAT,
                "INVENTTRANSID" => $data->INVENTTRANSID,
                "ICSC" => $data->ICSC 
            );

            LivePurchaseOrder::create($details);
            $data->pendingProcess = 0;
            $data->save();
        }

        return $this->successWithMessage("Purchase Order Synced to Newsystem Table Successfully.");
    }

    //syncing purchase order by last modified

    public function syncPurchaseOrderByLastModified(){

        $latest = LivePurchaseOrder::where("LastModifiedDateTime",'>', "2023-06-01")->orderBy("LastModifiedDateTime", 'desc')->first();
        
        $datas = PurchaseOrder::where("Last Modified DateTime", ">", $latest->LastModifiedDateTime)->orderBy("Last Modified DateTime", "asc")->limit(50)->get();
        // info("Syncing Ax Purchase Order to Synccare...".count($datas));
        if(count($datas) < 1){
            info("Ax to Synccare : All PO Synced");
            return response(" All Ax Purchase Order Synced to Synccare");
        }
        foreach($datas as $data){
            $details = array(
                "PURCHID" => $data["PURCHID"],
                "PURCHSTATUS" => $data["PURCHSTATUS"],
                "PSW_CROSSDOCKWAREHOUSE" => $data["PSW_CROSSDOCKWAREHOUSE"],
                "INVENTLOCATIONID" => $data["INVENTLOCATIONID"],
                "PURCHNAME" => $data["PURCHNAME"],
                "ITEMID" => $data["ITEMID"],
                "CONFIGID" => $data["CONFIGID"],
                "INVENTSIZEID" => $data["INVENTSIZEID"],
                "INVENTCOLORID" => $data["INVENTCOLORID"],
                "PURCHQTY" => $data["PURCHQTY"],
                "REMAINPURCHPHYSICAL" => $data["REMAINPURCHPHYSICAL"],
                "LastModifiedDateTime" => $data["Last Modified DateTime"],
                "INVENTTRANSID" => $data["INVENTTRANSID"],
                "PSW_REPORTWAREHOUSECAT" => $data["PSW_REPORTWAREHOUSECAT"],
                "ERPLYSKU" => $data["ERPLY SKU"],
                "ICSC" => $data["ICSC"],
                "pendingProcess" => 1
            );

            LivePurchaseOrder::updateOrcreate(
                [
                    "PURCHID" => $data["PURCHID"],
                    "ITEMID" => $data["ITEMID"],
                    "CONFIGID" => $data["CONFIGID"],
                    "INVENTSIZEID" => $data["INVENTSIZEID"],
                    "INVENTCOLORID" => $data["INVENTCOLORID"],
                ],

                $details);
        }

        info("Ax to Synccare : Purchase Order Synced Successfully.");

        return response("success");
    }


 


}
