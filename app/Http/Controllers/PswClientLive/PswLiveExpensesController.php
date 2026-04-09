<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\PswLiveExpensesService;
use App\Http\Controllers\PswClientLive\Services\PswLiveGeneralService; 

class PswLiveExpensesController extends Controller
{
    //
    protected $service;

    public function __construct(PswLiveExpensesService $ps){
      $this->service = $ps;
    }



    //Generating Product File
    public function syncExpensesAccount(){ 
        return $this->service->syncExpensesAccount(); 
    }

    public function syncExpensesAccountByLastModified(){
      return $this->service->syncExpensesAccountByLastModified();
    }

    public function syncExpensesAccountList(){ 
      return $this->service->syncExpensesAccountList(); 
    }

    public function syncExpensesAccountListByLastmodified(){
      return $this->service->syncExpensesAccountListByLastmodified();
    }

  

    
 
}
 