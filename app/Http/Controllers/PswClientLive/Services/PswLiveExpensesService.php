<?php

namespace App\Http\Controllers\PswClientLive\Services;
 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;
use App\Classes\UserLogger;
use App\Models\PswClientLive\DeliveryMode;
use App\Models\PswClientLive\DiscountCode;
use App\Models\PswClientLive\ExpensesAccount;
use App\Models\PswClientLive\ExpensesAccountList;
use App\Models\PswClientLive\Local\LiveDeliveryMode;
use App\Models\PswClientLive\Local\LiveDiscountCode;
use App\Models\PswClientLive\Local\LiveExpensesAccount;
use App\Models\PswClientLive\Local\LiveExpensesAccountList;
use App\Models\PswClientLive\Local\LiveSupplier;
use App\Models\PswClientLive\Local\TempSupplier;
use App\Models\PswClientLive\Supplier;

class PswLiveExpensesService
{

    use ResponseTrait;
    protected $customer;
    protected $letsLog;
 


    public function __construct(UserLogger $logger)
    {
        $this->letsLog = $logger;
    }

     
    public function syncExpensesAccount(){

        $datas = ExpensesAccount::get();

        foreach($datas as $data){

            $details = array(
                "STOREID" => $data["STOREID"],
                "LocationName" => trim($data["Location Name"]),
                "ACCOUNTNUM" => $data["ACCOUNTNUM"],
                "NAME" => trim($data["NAME"]),
                "ACCOUNTTYPE" => $data["ACCOUNTTYPE"],
                "LEDGERACCOUNT" => $data["LEDGERACCOUNT"],
                "CREATEDDATETIME" => $data["CREATEDDATETIME2"],
                "MODIFIEDDATETIME" => $data["MODIFIEDDATETIME"],
            );

            LiveExpensesAccount::create(
                $details
            );

        }
        info("Ax to Synccare : All Expenses Account Syncced");
        return response("Ax to Synccare : All Expenses Account Syncced");
    }

    public function syncExpensesAccountByLastModified(){

        $latest = LiveExpensesAccount::orderBy("MODIFIEDDATETIME", 'desc')->first();

        $datas = ExpensesAccount::where("MODIFIEDDATETIME",'>', $latest->MODIFIEDDATETIME)->orderBy("MODIFIEDDATETIME", 'asc')->get();

        if($datas->isEmpty()){
            info("AX to Synccare : All Expenses Account By Location Syncced");
            return response("AX to Synccare : All Expenses Account Syncced");
        }

        foreach($datas as $data){

            $details = array(
                "STOREID" => $data["STOREID"],
                "LocationName" => trim($data["Location Name"]),
                "ACCOUNTNUM" => $data["ACCOUNTNUM"],
                "NAME" => trim($data["NAME"]),
                "ACCOUNTTYPE" => $data["ACCOUNTTYPE"],
                "LEDGERACCOUNT" => $data["LEDGERACCOUNT"],
                "CREATEDDATETIME" => $data["CREATEDDATETIME1"],
                "MODIFIEDDATETIME" => $data["MODIFIEDDATETIME"],
            );

            LiveExpensesAccount::updateOrcreate(
                [
                    "STOREID" => $data["STOREID"],
                    "LocationName" => trim($data["Location Name"]),
                    "ACCOUNTNUM" => $data["ACCOUNTNUM"],
                    "NAME" => trim($data["NAME"]),
                    "ACCOUNTTYPE" => $data["ACCOUNTTYPE"],
                ],
                $details
            );

        }
        info("Ax to Synccare : All Expenses Account Synccing...");
        return response("Ax to Synccare : All Expenses Account Synccing...");
    }





    public function syncExpensesAccountList(){

        $datas = ExpensesAccountList::get();

        foreach($datas as $data){

            $details = array(
                "name" => $data["NAME"],
                "accountType" => $data["ACCOUNTTYPE"],
                "ledgerAccount" => $data["LEDGERACCOUNT"],
            );

            LiveExpensesAccountList::create($details);
        }

        return response("All Expenses List Synced to Synccare");

    }
    
    public function syncExpensesAccountListByLastmodified(){

        // $expensesList = LiveExpensesAccountList::orderBy

        $datas = ExpensesAccountList::get();

        foreach($datas as $data){

            $details = array(
                "name" => $data["NAME"],
                "accountType" => $data["ACCOUNTTYPE"],
                "ledgerAccount" => $data["LEDGERACCOUNT"],
            );

            LiveExpensesAccountList::updateOrcreate(
                [
                    "name" => $data["NAME"],
                    "accountType" => $data["ACCOUNTTYPE"],
                    "ledgerAccount" => $data["LEDGERACCOUNT"],
                ],
                $details
            );
        }

        return response("All Expenses List Synced to Synccare");

    }
     


      




}
