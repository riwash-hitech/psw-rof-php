<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\PswClientLive\Services\PswLiveSalesOrderService; 

class PswLiveSalesOrderController extends Controller
{
    //
    protected $service;

    public function __construct(PswLiveSalesOrderService $ps){
      $this->service = $ps;
    }



    //Generating Product File
    public function makeSalesOrderFile(){ 
        return $this->service->makeSalesOrderFile(); 
    }

    public function readSalesOrderFile(){ 
        return $this->service->readSalesOrderFile(); 
    }

    public function syncSalesOrderToNewsystem(){ 
        return $this->service->syncSalesOrderToNewsystem(); 
    }
 
    

     
}
 