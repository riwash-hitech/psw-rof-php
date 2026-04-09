<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\LivePushErply\Services\ErplyBinBayService;
use App\Http\Controllers\LivePushErply\Services\ErplyReasonCodeService;
use Illuminate\Http\Request;

class ErplyReasonCodeController extends Controller
{
    //
    protected $service;

    public function __construct(ErplyReasonCodeService $service){
        $this->service = $service;
    }

     

    public function syncReasonCode(){
        return $this->service->syncReasonCode();
    }
 


}
