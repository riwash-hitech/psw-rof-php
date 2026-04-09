<?php

namespace App\Http\Controllers\Paei\API\AxMagic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\API\AxMagic\Services\ItemByLocationMagicService;
use Illuminate\Http\Request;

class ItemByLocationMagicController extends Controller
{
    //
    protected $service; 

    public function __construct(ItemByLocationMagicService $service ){
        $this->service = $service;
       
    }

 
    public function getItemByLocations(Request $req){

        return $this->service->getItemByLocations($req);
    }
    public function resyncItemByLocation(Request $req){

        return $this->service->resyncItemByLocation($req);
    }

     

}
