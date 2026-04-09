<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\PswLiveCustomerService; 

class PswLiveCustomerController extends Controller
{
    //
    protected $service;

    public function __construct(PswLiveCustomerService $ps){
      $this->service = $ps;
    }



    //Generating Customer Flag File
    public function makeCustomerFlagFile(){ 
        return $this->service->makeCustomerFlagFile(); 
    }

    public function readAndStoreCustomerFlagFile(){
        return $this->service->readAndStoreCustomerFlagFile();
    }

    public function syncCustomerFlagToNewsystemTable(){
        return $this->service->syncCustomerFlagToNewsystemTable();
    }

    public function makeCustomerRelationFile(){
        return $this->service->makeCustomerRelationFile();
    }

    public function readCustomerRelationFile(){
        return $this->service->readCustomerRelationFile();
    }

    public function syncCustomerRelationToNewsystemTable(){
        return $this->service->syncCustomerRelationToNewsystemTable();
    }

    public function syncBusinessCustomerByLastModified(){
        return $this->service->syncBusinessCustomerByLastModified();
    }
 
    

     
}
 