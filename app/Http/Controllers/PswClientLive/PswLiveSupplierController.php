<?php


namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\AxCustomerService;
use App\Http\Controllers\PswClientLive\Services\PswLiveSupplierService;
use Illuminate\Http\Request;

class PswLiveSupplierController extends Controller
{
    //

    protected $service;

    public function __construct(PswLiveSupplierService $service){
        $this->service = $service;
    }

    public function makeSupplierFile(){
        
        return $this->service->makeSupplierFile();
    }

    public function readSupplierFile(){
        
        return $this->service->readSupplierFile();

    }

    public function syncSuppliersToNewsystem(){
        return $this->service->syncSuppliersToNewsystem();
    }

    public function syncSupplierByLastModified(){
        return $this->service->syncSupplierByLastModified();
    }

     
}
