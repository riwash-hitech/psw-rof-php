<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\LivePushErply\Services\ErplyBinBayService; 
use Illuminate\Http\Request;

class ErplyBinbayController extends Controller
{
    //
    protected $service;

    public function __construct(ErplyBinBayService $service){
        $this->service = $service;
    }

    public function syncBinBayLocations(){
        return $this->service->syncBinBayLocations();
    }

    public function saveBinRecords(Request $req){
        return $this->service->saveBinRecords($req);
    }

    public function adjustBinRecord(Request $req){
        return $this->service->adjustBinRecord($req);
    }
 


}
