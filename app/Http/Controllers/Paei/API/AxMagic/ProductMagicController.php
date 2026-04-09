<?php

namespace App\Http\Controllers\Paei\API\AxMagic;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Paei\API\AxMagic\Services\ProductMagicService;
use Illuminate\Http\Request;

class ProductMagicController extends Controller
{
    //
    protected $service; 

    public function __construct(ProductMagicService $service ){
        $this->service = $service;
       
    }

 
    public function updateMatrixErplyEnabled(Request $req){
        return $this->service->updateMatrixErplyEnabled($req);
    }

    public function updateVariationErplyEnabled(Request $req){

        return $this->service->updateVariationErplyEnabled($req);
    }

     

}
