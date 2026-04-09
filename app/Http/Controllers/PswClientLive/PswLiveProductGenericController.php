<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\PswLiveProductGenericService;
use App\Models\PswClientLive\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PswLiveProductGenericController extends Controller
{
    //
    protected $service;

    public function __construct(PswLiveProductGenericService $ps){
      $this->service = $ps;
    }



    //Generating Product File
    public function makeProductFile(Request $req){ 
        return $this->service->makeProductFile($req); 
    }


    //Inserting Product File to Temp Table
    public function handleProductFile(){

        return $this->service->readProductFileAndStore();
        
    }

    public function syncProductGenericNewsystemMatrix(){
        return $this->service->syncTempToCurrentsystemGenericMatrix();
    }
    
    public function syncProductGenericNewsystemVariation(){
        return $this->service->syncTempToCurrentsystemGenericVariation();
    }
    //syncing by last modified product
    public function syncProductAxtoMiddlewareByLastModified(){
        return $this->service->syncProductAxtoMiddlewareByLastModified();
    }
 
     
}
 