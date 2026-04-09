<?php

namespace App\Http\Controllers\PswClientLive\Services;
 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;
use App\Classes\UserLogger;
use App\Models\PswClientLive\Local\LiveSalesOrder;
use App\Models\PswClientLive\Local\LiveSupplier;
use App\Models\PswClientLive\Local\LiveTransferOrderLine;
use App\Models\PswClientLive\Local\TempSalesOrder;
use App\Models\PswClientLive\Local\TempSupplier;
use App\Models\PswClientLive\Local\TempTransferOrderLine;
use App\Models\PswClientLive\SalesOrder;
use App\Models\PswClientLive\Supplier;
use App\Models\PswClientLive\TransferOrderLine;

class PswLiveSalesOrderService
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
        $val = str_replace("'", "\'", $val);
        $val = str_replace('"', '\"', $val);
        return $val;
    }

    public function makeSalesOrderFile()
    {

        // echo "hello";
        // die;

        $path = public_path('PswLiveTemp');

        File::delete($path . '/salesOrders.txt');

        if (!File::exists($path)) {

            File::makeDirectory($path);
        }

        // $datas = TransferOrderLine::select("*")->orderBy("Line Modified DateTime",'asc')->skip(200000)->take(100000)->get(['*']);
        $datas = SalesOrder::get();
                
            // TransferOrderLine::select('*')
            //         ->from(DB::raw('(SELECT *, ROW_NUMBER() OVER (ORDER BY [Line Modified DateTime] ASC) AS row_number FROM ERPLY_TransferOrderLines) AS sub'))
            //         ->where('row_number', '>', 200000)
            //         ->orderBy('Line Modified DateTime', 'asc')
            //         ->take(100000)
            //         ->get();

        // dd($datas);
        $chunkDatas = $datas->chunk(500);

        foreach ($chunkDatas as $datas) {

            $content = 'Insert into `temp_sales_orders`(`SALESID`, `OPENLINE`,`erplysku1`,`ITEMID`, `CONFIGID`, `INVENTCOLORID`, `INVENTSIZEID`, `SALESSTATUS`, `CUSTACCOUNT`, `SchoolAccount`, `SALESPOOLID`, `DeliveryMode`, `DELIVERYNAME`, `DELIVERYADDRESS`, `DELIVERYSTREET`,
             `DELIVERYCITY`, `DELIVERYZIPCODE`, `DELIVERYSTATE`, `EMAIL`, `Phone`, `INVENTLOCATIONID`, `WMSLOCATIONID`, `SALESQTY`, `REMAINSALESPHYSICAL`, `SALESLINERECID`, `ModifiedDateTime`, `MODIFIEDDATETIME_SALESTABLE`) VALUES ';
            
            $q = null;
            $flag = 0;
            foreach ($datas as $key => $value) {
                $flag = $flag + 1;
                //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                // $key = $datas->last() == $value ? ';' : ',';
                $key = '';
                
                    $key = $datas->last() == $value ? ';' : ',';
                if($chunkDatas->last() == $datas){
                    if($datas->last() == $value){
                        $key = '';
                    }
                }

                $q .= '( "' . $value['SALESID'] . '","' . $value['OPENLINE'] . '",  "' . $value['erplysku1'] . '","' . $value['ITEMID'] . '","' . $value['CONFIGID'] . '","' . $value['INVENTCOLORID'] . '","' . $value['INVENTSIZEID'] . '","' . $value['SALESSTATUS'] . '","' . $value['CUSTACCOUNT'] . '","' . $value['School Account'] . '",
                "' . $value['SALESPOOLID'] . '","' . $value['Delivery Mode'] . '","' . $value['DELIVERYNAME'] . '","' . $value['DELIVERYADDRESS'] . '","' . $value['DELIVERYSTREET'] . '","' . $value['DELIVERYCITY'] . '","' . $value['DELIVERYZIPCODE'] . '","' . $value['DELIVERYSTATE'] . '","' . $value['EMAIL'] . '",
                "' . $value['Phone'] . '","' . $value['INVENTLOCATIONID'] . '","' . $value['WMSLOCATIONID'] . '","' . $value['SALESQTY'] . '","' . $value['REMAINSALESPHYSICAL'] . '","' . $value['SALESLINERECID'] . '","' . $value['ModifiedDateTime'] . '","' . $value['MODIFIEDDATETIME_SALESTABLE'] . '")' . $key;
            }

            $content = $content . '' . $q . '' . "\n";
            // if($chunkDatas->last() == $datas){
            //     $content = rtrim($content, );
            // }
            File::append($path . '/salesOrders.txt', $content);
        }

        return $this->successWithMessage("salesOrders File Generated Successfully.");
    }

    public function readSalesOrderFile(){
        $path = public_path('PswLiveTemp/salesOrders.txt');

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

                return $this->successWithMessage(" salesOrders File Executed Successfully.");
            // }
        } else {
            echo "no data";
            die;
        }
    }


    public function syncSalesOrderToNewsystem(){
        $datas = TempSalesOrder::where("pendingProcess", 1)->limit(500)->get();

        foreach($datas as $data){
            $details = array(
                "SALESID" => $data->SALESID,
                "OPENLINE" => $data->OPENLINE,
                "erplysku1" => $data->erplysku1,
                "ITEMID" => $data->ITEMID,
                "CONFIGID" => $data->CONFIGID,
                "INVENTCOLORID" => $data->INVENTCOLORID,
                "INVENTSIZEID" => $data->INVENTSIZEID,
                "SALESSTATUS" => $data->SALESSTATUS,
                "CUSTACCOUNT" => $data->CUSTACCOUNT,
                "SchoolAccount" => $data->SchoolAccount,
                "SALESPOOLID" => $data->SALESPOOLID,
                "DeliveryMode" => $data->DeliveryMode,
                "DELIVERYNAME" => $data->DELIVERYNAME,
                "DELIVERYADDRESS" => $data->DELIVERYADDRESS,
                "DELIVERYSTREET" => $data->DELIVERYSTREET,
                "DELIVERYCITY" => $data->DELIVERYCITY,
                "DELIVERYZIPCODE" => $data->DELIVERYZIPCODE,
                "DELIVERYSTATE" => $data->DELIVERYSTATE,
                "EMAIL" => $data->EMAIL,
                "Phone" => $data->Phone,
                "INVENTLOCATIONID" => $data->INVENTLOCATIONID,
                "WMSLOCATIONID" => $data->WMSLOCATIONID,
                "SALESQTY" => $data->SALESQTY,
                "REMAINSALESPHYSICAL" => $data->REMAINSALESPHYSICAL,
                "SALESLINERECID" => $data->SALESLINERECID,
                // "ModifiedDateTime" => $data->ModifiedDateTime,
                // "MODIFIEDDATETIME_SALESTABLE" => $data->MODIFIEDDATETIME_SALESTABLE,
            );
            if($data->ModifiedDateTime){
                $details["ModifiedDateTime"] = $data->ModifiedDateTime;
            }
            if($data->MODIFIEDDATETIME_SALESTABLE){
                $details["MODIFIEDDATETIME_SALESTABLE"] = $data->MODIFIEDDATETIME_SALESTABLE;
            }

            LiveSalesOrder::create(
                $details);
            $data->pendingProcess = 0;
            $data->save();
        }

        return $this->successWithMessage("Sales Order Synced to Newsystem Table Successfully.");
    }


      




}
