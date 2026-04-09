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
use App\Models\PAEI\Warehouse;
use App\Models\PswClientLive\AxCashInOut;
use App\Models\PswClientLive\Local\LiveExpensesAccount;
use App\Models\PswClientLive\Local\LiveExpensesAccountList;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;

class AxCashInOutService{

    use AxTrait; 
    protected $api;
    public function __construct(EAPIService $api){
        $this->api = $api; 
    }

    
    public function syncCashInOut($req){

        $isDebug = '';
        if($req->debug){
            $isDebug = $req->debug;
        }

        $isAcademy = $this->api->flag;



        $datas = Cashin::where("reasonID", ">", 0)->whereIn("axPending", [1,2])->limit(5)->orderBy("updated_at", 'asc')->get();
        if($datas->isEmpty()){
            info("All Cashinou  Synced to AX");
            return response("All Cashinout Synced to AX");
        }


        
        // dd($datas);
        foreach($datas as $data){
            // dd($data);
            $erplyWarehouse = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();
            // dd($warehouse);
            $axWarehouse = LiveWarehouseLocation::where("LocationID", $erplyWarehouse->code)->first();
            // dd($axWarehouse);
            $reasonCode = LiveExpensesAccountList::where( $isAcademy == true ? "erplyID" : "pswErplyID", $data->reasonID)->first();
            if(!$reasonCode){
                $data->axPending = 2;
                $data->save();
                break;
            }
            // dd($reasonCode);
            $axInfo = [];
            if($reasonCode){
                $axInfo = LiveExpensesAccount::where("STOREID", $axWarehouse->StoreID)->where("NAME", $reasonCode->name)->first();
            }
            // dd($axInfo);
            $isExist = false;
            if($data->axID > 0){
                $check = AxCashInOut::where('RECID', $data->axID)->first();
                if($check){
                    $isExist = true;
                }
            }

            // if($axInfo){

                $recid = $this->getRecID(50316);

                $details = array(
                    "DATAAREAID" => "psw",
                    // "RECVERSION" => "",
                    "RECID" => $recid["NEXTVAL"],
                    "STATUS" => 1,
                    "STOREID" => $axWarehouse->StoreID,
                    "DBACTION" => $isExist == true ? 2 : 1,
                    "ENTITY" => @$axWarehouse->ENTITY ? @$axWarehouse->ENTITY : '',
                    "TERMINALID" => $data->warehouseID,
                    "TRANSACTIONID" => $data->transactionID,
                    "MODIFIEDDATETIME" => $data->lastModified,
                    "MODIFIEDBY" => "ERPLY",
                    "CREATEDDATETIME" => $data->added,
                    "CREATEDBY" => "ERPLY",
                    // "INCOMEEXPENSEACCOUNT" => $axInfo->ACCOUNTNUM,
                    "AMOUNT" => $data->sum < 0 ? abs($data->sum) : -$data->sum,
                    "CURRENCY" => $data->currencyCode,
                    "TRANSDATE" => $data->dateTime,
                    "ACCOUNTTYPE" => $data->sum < 0 ? 1 : 0,
                    // "VOUCHER" => "",
                );

                if($axInfo){
                    $details["INCOMEEXPENSEACCOUNT"] = $axInfo->ACCOUNTNUM;
                }

                if($isDebug == 1){
                    dd($details);
                    die;
                }
                

                AxCashInOut::create($details);

                $verify = AxCashInOut::where("RECID", $recid["NEXTVAL"])->first();
                if($verify){
                    //Now Updating NextVal
                    $rowCount = AxCashInOut::count();
                    $nextVal = $rowCount + $recid["NEXTVAL"];
                    $updateNextval = $this->updateRecID(50316, $nextVal);
                    if($updateNextval == true){
                        info("SystemSequence Table Updated");
                        $data->axID = $recid["NEXTVAL"];
                        $data->axPending = 0;
                        $data->save(); 
                        UserLogger::setChronLogNew('', json_encode($verify, true),  "Ax CashInOut Created");       
                    }else{
                        info("SystemSequence Table Update Failed");
                    } 
                }
            // }
        }

        info("CashInOut Created to AX Successfully.");
        return response("CashInOut Created to AX Successfully.");

    }


}