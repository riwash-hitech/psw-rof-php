<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LivePushErply\Services\StoreLocationService;
use Illuminate\Http\Request;

class StoreLocationController extends Controller
{
    //
    protected $service;

    public function __construct(StoreLocationService $service)
    {
        $this->service = $service;
    }

    public function syncWarehouse(Request $req){
        return $this->service->syncStoreLocation($req);
    }
}
