<?php

namespace App\Traits; 

trait IsFinalTrait{

    public function __construct(){
        
    }
    
    public function isFinal($req, $title){

        $isFinal = 0;
        if($req->isfinal){
            $isFinal = 1;
        }

        if($isFinal == 0){
            info($title." Cron Die IsFinal");
            die;
        }
    }

    

}