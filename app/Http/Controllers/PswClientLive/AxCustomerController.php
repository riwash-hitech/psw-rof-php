<?php


namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\AxCustomerService;
use Illuminate\Http\Request;

class AxCustomerController extends Controller
{
    //

    protected $service;

    public function __construct(AxCustomerService $service){
        $this->service = $service;
    }

    public function syncMiddlewareToAx(){
        
        return $this->service->syncMiddlewareToAx();
    }

    public function syncSingleCustomerMiddleServerToAX(Request $req){

        if($req->id){
            return $this->service->syncSingleCustomerMiddleServerToAX($req->id);
        }

        return response("Invalid Customer ID!");

    }
}
