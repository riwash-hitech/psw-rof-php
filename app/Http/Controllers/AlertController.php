<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Services\AlertService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    protected $service;
    public function __construct(AlertService $service)
    {
        $this->service = $service;
    }

    public function salesOrder(Request $req)
    {
        return $this->service->salesOrder($req);
    }
}
