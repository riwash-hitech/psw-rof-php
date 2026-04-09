<?php

namespace App\Http\Controllers\PswClientLive\Services;
 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;
use App\Classes\UserLogger;
use App\Models\PswClientLive\Local\LiveSupplier;
use App\Models\PswClientLive\Local\TempSupplier;
use App\Models\PswClientLive\Supplier;

class PswLiveSupplierService
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

    public function makeSupplierFile()
    {
        // if(env('isLive') == true){
        $path = public_path('PswLiveTemp/Live');
        // }else{
        //     $path = public_path('PswLiveTemp');
        // }
        File::delete($path . '/suppliers.txt');

        if (!File::exists($path)) {

            File::makeDirectory($path);
        }

        $datas = Supplier::get();
        // dd($products);
        $chunkDatas = $datas->chunk(500);

        foreach ($chunkDatas as $datas) {

            $content = 'Insert into `temp_suppliers`(`ACCOUNTNUM`,`Name`,`RECID`, `ModifiedDateTime`,`pendingProcess`) VALUES ';

            $q = null;
            $flag = 0;
            foreach ($datas as $key => $value) {
                $flag = $flag + 1;
                //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                // $key = $datas->last() == $value ? ';' : ',';
                $key = $flag == 500 ? ';' : ',';
                if($chunkDatas->last() == $datas){
                    if($datas->last() == $value){
                        $key = '';
                    }
                }

                $q .= '( "' . $value['ACCOUNTNUM'] . '","' . DB::getPdo()->quote($value['NAME']) . '",  "' . $value['RECID'] . '","' . $value['ModifiedDateTime'] . '", 1)' . $key;
            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/suppliers.txt', $content);
        }

        return $this->successWithMessage("suppliers File Generated Successfully.");
    }

    public function readSupplierFile(){
        $path = public_path('PswLiveTemp/Live/suppliers.txt');

        if (File::exists($path)) {

            $count = TempSupplier::where('pendingProcess', 0)->count();

            if ($count < 1) {
                TempSupplier::truncate();
                $file = File::get($path);

                $sqls = explode(";\n", $file);

                foreach ($sqls as $sql) {

                    if ($sql != '') {
                        DB::connection('mysql2')->select($sql);
                    }
                }

                return $this->successWithMessage("suppliers File Executed Successfully.");
            }
        } else {
            echo "no data";
            die;
        }
    }


    public function syncSuppliersToNewsystem(){
        $datas = TempSupplier::where("pendingProcess", 1)->limit(500)->get();

        foreach($datas as $data){
            $details = array(
                "ACCOUNTNUM" => $data->ACCOUNTNUM,
                "Name" => trim($data->Name),
                "RECID" => $data->RECID, 
                "ModifiedDateTime" => $data->ModifiedDateTime
            );

            LiveSupplier::updateOrcreate(
                [
                    "RECID" => $data->RECID
                ],
                $details);
            $data->pendingProcess = 0;
            $data->save();
        }

        return $this->successWithMessage("Supplier Synced to Newsystem Table Successfully.");
    }

    public function syncSupplierByLastModified(){

        $latest = LiveSupplier::orderBy("ModifiedDateTime", 'desc')->first();


        $datas = Supplier::where("ModifiedDateTime", '>', $latest->ModifiedDateTime)->orderBy('ModifiedDateTime', 'asc')->limit(50)->get();

        if(count($datas) < 1){
            info("Ax to Synccare : All Vendor Synced");
            return response("All Vendor Synced to Synccare from AX.");
            die;
        }

        foreach($datas as $data){
            $details = array(
                "ACCOUNTNUM" => $data["ACCOUNTNUM"],
                "Name" => trim($data["Name"]),
                "RECID" => $data["RECID"], 
                "ModifiedDateTime" => $data["ModifiedDateTime"]
            );

            LiveSupplier::updateOrcreate(
                [
                    "RECID" => $data["RECID"]
                ],
                $details); 
        }
        info("Supplier Synced to Synccare from AX...");
        return $this->successWithMessage("Supplier Synced to Newsystem Table Successfully.");
    }


      




}
