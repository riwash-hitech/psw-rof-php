<?php

namespace App\Http\Controllers\MiddlewareToAx;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MiddlewareToAx\Services\AxCustomerService;
use Illuminate\Http\Request;

class AxCustomerController2 extends Controller
{
    //

    protected $service;

    // public function __construct(AxCustomerService $service){
    //     $this->service = $service;
    // }

    public function syncMiddlewareToAx(){
        echo "hi";
        die;
        // return $this->service->syncMiddlewareToAx();
    }
}
