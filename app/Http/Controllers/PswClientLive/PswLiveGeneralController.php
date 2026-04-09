<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\PswLiveGeneralService; 

class PswLiveGeneralController extends Controller
{
    //
    protected $service;

    public function __construct(PswLiveGeneralService $ps){
      $this->service = $ps;
    }



    //Generating Product File
    public function syncDeliveryMode(){ 
        return $this->service->syncDeliveryMode(); 
    }

    public function syncDiscountCodes(){ 
        return $this->service->syncDiscountCodes(); 
    }
 
}
 