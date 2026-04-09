<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\PswImageService; 

class PswImageController extends Controller
{
    //
    protected $service;

    public function __construct(PswImageService $ps){
      $this->service = $ps;
    }

    public function checkImage(){
        return $this->service->checkImage();
    }

    public function checkMatrixImage(){
        return $this->service->checkMatrixImage();
    }

 
    

     
}
 