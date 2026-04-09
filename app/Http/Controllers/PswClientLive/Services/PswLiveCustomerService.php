<?php

namespace App\Http\Controllers\PswClientLive\Services;

use App\Models\PswClientLive\Customer;
use App\Models\PswClientLive\CustomerRelation;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\TempCustomer;
use App\Models\PswClientLive\Local\TempCustomerRelation; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;

class PswLiveCustomerService
{

    use ResponseTrait;
    protected $customer;



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

    public function makeCustomerFlagFile()
    {

        $path = public_path('PswLiveTemp');

        File::delete($path . '/customerFlag.txt');

        if (!File::exists($path)) {

            File::makeDirectory($path);
        }

        $datas = Customer::get();
        // dd($products);
        $chunkDatas = $datas->chunk(500);

        foreach ($chunkDatas as $datas) {

            $content = 'Insert into `temp_customer_flag`(`ACCOUNTNUM`,
                    `NAME`,
                    `CUSTGROUP`,
                    `ACADEMYFLAG`,
                    `PSWFLAG`,
                    `ERPLYFLAG`,
                    `ERPLYFLAGModified`,
                    `ENTITY`,
                    `ModifiedDateTime`, 
                    `pendingProcess`
                   ) VALUES ';

            $q = null;

            foreach ($datas as $key => $value) {

                //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                $key = $datas->last() == $value ? ';' : ',';

                $q .= '( "' . $value['ACCOUNTNUM'] . '",
                        "' . $this->escapeFunc($value['NAME']) . '",
                        "' . $value['CUSTGROUP'] . '",
                        ' . ($value['ACADEMY FLAG'] == "" ? "0" : $value['ACADEMY FLAG'])  . ',
                        ' . ($value['PSW FLAG'] == "" ?  "0" : $value['PSW FLAG'] ) . ',
                        "' . $value['ERPLY FLAG'] . '",
                        "' . $value['ERPLY FLAG Modified'] . '",
                        "' . $value['ENTITY'] . '",
                        "' . $value['ModifiedDateTime'] . '", 
                        1)' . $key;
            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/customerFlag.txt', $content);
        }

        return $this->successWithMessage("Customer Flag File Generated Successfully.");
    }

    public function readAndStoreCustomerFlagFile()
    {
        $path = public_path('PswLiveTemp/customerFlag.txt');

        if (File::exists($path)) {

            $count = TempCustomer::where('pendingProcess', 0)->count();

            if ($count < 1) {
                TempCustomer::truncate();
                return $this->processFile($path);
            }
        } else {
            echo "no data";
            die;
        }
    }

    protected function processFile($path)
    {

        $file = File::get($path);

        $sqls = explode(";\n", $file);

        foreach ($sqls as $sql) {

            if ($sql != '') {
                DB::connection('mysql2')->select($sql);
            }
        }

        return $this->successWithMessage("Customer Flag File Executed Successfully.");
    }
    
    public function syncCustomerFlagToNewsystemTable(){

        $datas = TempCustomer::where("pendingProcess", 1)->limit(500)->get();

        foreach($datas as $data){
            $details = array(
                "ACCOUNTNUM" => $data->ACCOUNTNUM,
                "NAME" => $data->NAME,
                "CUSTGROUP" => $data->CUSTGROUP,
                "ACADEMYFLAG" => $data->ACADEMYFLAG,
                "PSWFLAG" => $data->PSWFLAG,
                "ERPLYFLAG" => $data->ERPLYFLAG,
                "ERPLYFLAGModified" => $data->ERPLYFLAGModified,
                "ENTITY" => $data->ENTITY,
                "ModifiedDateTime" => $data->ModifiedDateTime 
            );

            LiveCustomer::create($details);
            $data->pendingProcess = 0;
            $data->save();
        }

        return $this->successWithMessage("Customer Flag Synced to Newsystem Table Successfully.");

    }
    public function makeCustomerRelationFile()
    {

        $path = public_path('PswLiveTemp');

        File::delete($path . '/customerRelation.txt');

        if (!File::exists($path)) {

            File::makeDirectory($path);
        }

        $datas = CustomerRelation::get();
        // dd($products);
        $chunkDatas = $datas->chunk(500);

        foreach ($chunkDatas as $datas) {

            $content = 'Insert into `temp_customer_business_relations`(`PSW_SMMCUSTACCOUNT`,
                    `NAME`,
                    `ADDRESS`,
                    `STREET`,
                    `CITY`,
                    `ZIPCODE`,
                    `STATE`,
                    `PHONE`,
                    `EMAIL`, 
                    `CUSTGROUP`,
                    `ERPLY_FLAG`,
                    `SAB_RBOSTOREPRIMARY`,
                    `SAB_RBOSTORESECONDARY`,
                    `STATUS`,
                    `CREDITMAX`,
                    `MANDATORYCREDITLIMIT`,
                    `BusRelLastModified`,
                    `ERPLYFLAGModified`,
                    `pendingProcess`
                   ) VALUES ';

            $q = null;

            foreach ($datas as $key => $value) {

                //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                $key = $datas->last() == $value ? ';' : ',';
                if($chunkDatas->last() == $datas){
                    if($datas->last() == $value){
                        $key = '';
                    }
                }

                $q .= '( "' . $value['PSW_SMMCUSTACCOUNT'] . '",
                        "' . $this->escapeFunc($value['NAME']) . '",
                        "' . $this->escapeFunc($value['ADDRESS'] == "" ? "" : $value['ADDRESS']) . '",
                        "' . ($this->escapeFunc($value['STREET'])  == "" ? "" : $this->escapeFunc($value['STREET']) ) . '",
                        "' . ($this->escapeFunc($value['CITY'])  == "" ? "" : $this->escapeFunc($value['CITY']) ) . '",
                        "' . $value['ZIPCODE'] . '",
                        "' . $value['STATE'] . '",
                        "' . $this->escapeFunc($value['PHONE']) . '",
                        "' . $this->escapeFunc($value['EMAIL']) . '", 
                        "' . $this->escapeFunc($value['CUSTGROUP']) . '", 
                        "' . $this->escapeFunc($value['ERPLY FLAG']) . '", 
                        "' . $this->escapeFunc($value['SAB_RBOSTOREPRIMARY']) . '", 
                        "' . $this->escapeFunc($value['SAB_RBOSTORESECONDARY']) . '", 
                        "' . $this->escapeFunc($value['STATUS']) . '", 
                        "' . $this->escapeFunc($value['CREDITMAX']) . '", 
                        "' . $this->escapeFunc($value['MANDATORYCREDITLIMIT']) . '", 
                        "' . $value['Bus Rel Last Modified'] . '", 
                        "' . $value['ERPLY FLAG Modified'] . '", 
                        1)' . $key;
            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/customerRelation.txt', $content);
        }

        return $this->successWithMessage("Customer Business Relation File Generated Successfully.");
    }

    public function readCustomerRelationFile()
    {
        $path = public_path('PswLiveTemp/customerRelation.txt');

        if (File::exists($path)) {

            $count = TempCustomerRelation::where('pendingProcess', 0)->count();

            if ($count < 1) {
                TempCustomerRelation::truncate();
                $file = File::get($path);

                $sqls = explode(";\n", $file);

                foreach ($sqls as $sql) {

                    if ($sql != '') {
                        DB::connection('mysql2')->select($sql);
                    }
                }

                return $this->successWithMessage("Customer Business Relation File Executed Successfully.");
            }
        } else {
            echo "no data";
            die;
        }
    }

    public function syncCustomerRelationToNewsystemTable(){

        $datas = TempCustomerRelation::where("pendingProcess", 1)->limit(500)->get();

        foreach($datas as $data){
            $details = array(
                "PSW_SMMCUSTACCOUNT" => $data->PSW_SMMCUSTACCOUNT,
                "NAME" => $data->NAME,
                "ADDRESS" => $data->ADDRESS,
                "STREET" => $data->STREET,
                "CITY" => $data->CITY,
                "ZIPCODE" => $data->ZIPCODE,
                "STATE" => $data->STATE,
                "PHONE" => $data->PHONE,
                "EMAIL" => $data->EMAIL,
                "CUSTGROUP" => $data->CUSTGROUP,
                "ERPLY_FLAG" => $data->ERPLY_FLAG,
                "SAB_RBOSTOREPRIMARY" => $data->SAB_RBOSTOREPRIMARY,
                "SAB_RBOSTORESECONDARY" => $data->SAB_RBOSTORESECONDARY,
                "STATUS" => $data->STATUS,
                "CREDITMAX" => $data->CREDITMAX,
                "MANDATORYCREDITLIMIT" => $data->MANDATORYCREDITLIMIT,
                "BusRelLastModified" => $data->BusRelLastModified ? $data->BusRelLastModified : "0000-00-00 00:00:00",
                "ERPLYFLAGModified" => $data->ERPLYFLAGModified ? $data->ERPLYFLAGModified : "0000-00-00 00:00:00",
                "pendingProcess" => 1,
                "addressPending" => 1, 
            );

            LiveCustomerRelation::updateOrcreate(
                [
                    "PSW_SMMCUSTACCOUNT" => $data->PSW_SMMCUSTACCOUNT,
                ],
                $details
            );
            $data->pendingProcess = 0;
            $data->save();
        }

        return $this->successWithMessage("Customer Business Relation Synced to Newsystem Table Successfully.");
    }

    public function syncBusinessCustomerByLastModified(){
        $datas = LiveCustomerRelation::orderBy("BusRelLastModified", 'desc')->limit(1)->first();
        // echo $datas->BusRelLastModified;
        // die;
        $axData = CustomerRelation::where("Bus Rel Last Modified",">", $datas->BusRelLastModified)->limit(100)->get();

        // dd($axData);

        if($axData->isEmpty()){
            info("Ax to Synccare : All Business Customer Syncced.");
            return response("Ax to Synccare : All Business Customer Syncced.");
        }

        foreach($axData as $data){
            $details = array(
                "PSW_SMMCUSTACCOUNT" => $data["PSW_SMMCUSTACCOUNT"],
                "NAME" => $data["NAME"],
                "ADDRESS" => $data["ADDRESS"],
                "STREET" => $data["STREET"],
                "CITY" => $data["CITY"],
                "ZIPCODE" => $data["ZIPCODE"],
                "STATE" => $data["STATE"],
                "PHONE" => $data["PHONE"],
                "EMAIL" => $data["EMAIL"],
                "CUSTGROUP" => $data["CUSTGROUP"],
                "ERPLY_FLAG" => $data["ERPLY FLAG"],
                "SAB_RBOSTOREPRIMARY" => $data["SAB_RBOSTOREPRIMARY"],
                "SAB_RBOSTORESECONDARY" => $data["SAB_RBOSTORESECONDARY"],
                "STATUS" => $data["STATUS"],
                "CREDITMAX" => $data["CREDITMAX"],
                "MANDATORYCREDITLIMIT" => $data["MANDATORYCREDITLIMIT"],
                "BusRelLastModified" => $data["Bus Rel Last Modified"],
                "ERPLYFLAGModified" => $data["ERPLY Flag Modified"] ? $data["ERPLY Flag Modified"] : "0000-00-00 00:00:00",
                "pendingProcess" => 1, 
                "addressPending" => 1, 
            );

            LiveCustomerRelation::updateOrcreate(
                [
                    "PSW_SMMCUSTACCOUNT" => $data["PSW_SMMCUSTACCOUNT"]
                ],
                $details
            );
        }

        info("Ax to Synccare : Business Customer Synccing...");
        return response("Ax to Synccare : Business Customer Synccing...");

    }



    //sync by last modification date time




}
