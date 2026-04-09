<?php

namespace App\Http\Controllers\Paei\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\API\APIServices\NotificationApiService; 
use App\Models\PAEI\Warehouse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    //
    protected $service;


    public function __construct(NotificationApiService $service){
        $this->service = $service;
        // $this->variation = $vp;
    }


    public function getSmsNotifications(Request $req){
        return $this->service->getSmsNotifications($req);
    }

    public function getEmailNotification(Request $req){
        return $this->service->getEmailNotification($req);
    }

     

}
