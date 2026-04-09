<?php

namespace App\Http\Controllers\Paei\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\API\APIServices\CashinsApiService;
use App\Http\Controllers\Paei\API\APIServices\CurrencyApiService;
use App\Http\Controllers\Paei\API\APIServices\EmployeeApiService;
use App\Models\PswClientLive\Local\LiveProductVariation;
use Illuminate\Http\Request;

class GeneralApiController extends Controller
{
    //
    protected $employee; 

    public function __construct(){
        // $this->employee = $mp;
       
    }

    public function getProductErplySku(Request $req){
        info("Request from psw data server");
        
        if($req->sku){
            
            $datas = LiveProductVariation::whereIn("ERPLYSKU", $req->sku)->get();

            return response()->json(["status" => 200, "data" => $datas]);

        }

    }
}
