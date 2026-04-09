<?php

namespace App\Http\Controllers\Paei\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\API\APIServices\SchoolApiService;
use Illuminate\Http\Request;

class SchoolApiController extends Controller
{
    //
    protected $service; 

    public function __construct(SchoolApiService $service ){
        $this->service = $service;
       
    }

    public function getSchool(Request $req){
        // getSchool
        // return $this->service->getSchool($req);
        return $this->service->getSchool($req);

    }

    public function getSchoolV2(Request $req){
        // getSchool
        // return $this->service->getSchool($req);
        return $this->service->getSchoolV2($req);

    }

    public function getAllMatrix(Request $req){
        // getSchool
        // return $this->service->getSchool($req);
        return $this->service->getAllMatrix($req);

    }

    

    public function getAll(Request $req){
         
        // return $this->service->getSchool($req);
        return $this->service->getAll($req);

    }

    public function getDeliveryMode(Request $req){
         
        // return $this->service->getSchool($req);
        return $this->service->getDeliveryMode($req);

    }

    public function salesOrder(Request $req){
        return $this->service->salesOrder($req);
    }

    public function getOfferOrder(Request $req){
        return $this->service->getOfferOrder($req);
    }

    public function getReceipt(Request $req){
        return $this->service->getReceipt($req);
    }

    public function deleteOffer(Request $req){
        return $this->service->deleteOffer($req);
    }
    
    public function deleteOfferAfterOneDay(Request $req)
    {
        return $this->service->deleteOfferAfterOneDay($req);
    }



}
