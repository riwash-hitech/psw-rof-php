<?php

namespace App\Http\Controllers\PswClientLive\Services;
 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;
use App\Classes\UserLogger;
use App\Models\PswClientLive\DeliveryMode;
use App\Models\PswClientLive\DiscountCode;
use App\Models\PswClientLive\Local\LiveDeliveryMode;
use App\Models\PswClientLive\Local\LiveDiscountCode;
use App\Models\PswClientLive\Local\LiveSupplier;
use App\Models\PswClientLive\Local\TempSupplier;
use App\Models\PswClientLive\Supplier;

class PswLiveGeneralService
{

    use ResponseTrait;
    protected $customer;
    protected $letsLog;
 


    public function __construct(UserLogger $logger)
    {
        $this->letsLog = $logger;
    }

     
    public function syncDeliveryMode(){

        $datas = DeliveryMode::get();

        foreach($datas as $data){

            $details = array(
                "CODE" => $data["CODE"],
                "TXT" => $data["TXT"],
                "dmxCarrierName" => $data["DMX_CARRIERNAME"],
                "createdDateTime" => $data["CREATEDDATETIME_DLVMODE"],
                "modifiedDateTime" => $data["MODIFIEDDATETIME_DLVMODE"],
                "dataAreaID" => $data["DATAAREAID"],
                "RECID" => $data["RECID"],
            );

            LiveDeliveryMode::updateOrcreate(
                [ 
                    "RECID" => $data["RECID"]
                ],
                $details
            );

        }
        info("Ax to Synccare : All Delivery Mode Syncced");
        return response("All Delivery Mode Syncced to Synccare from AX");
    }

    public function syncDiscountCodes(){

        $datas = DiscountCode::get();

        foreach($datas as $data){
            $details = array(
                "INFOCODEID" => $data["INFOCODEID"],
                "SUBCODEID" => $data["SUBCODEID"],
                "DESCRIPTION" => $data["DESCRIPTION"],

            );

            LiveDiscountCode::updateOrcreate(
                [
                    "INFOCODEID" => $data["INFOCODEID"],
                    "SUBCODEID" => $data["SUBCODEID"],
                    "DESCRIPTION" => $data["DESCRIPTION"],
                ],
                $details
            );
        }

        info("Ax to Synccare : All Discount Code Syncced");
        return response("All Discount Codes Syncced to Synccare from AX");

    }
     


      




}
