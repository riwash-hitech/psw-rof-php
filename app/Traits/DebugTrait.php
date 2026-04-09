<?php

namespace App\Traits;

use App\Models\PswClientLive\Local\LiveProductCategory;
use App\Models\PswClientLive\Local\LiveProductColor;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductSize;

trait DebugTrait{

    
    public  function setInfo($msg = '',  $isObject = 0, $preFix = '', $postFix = '', $isPrint = 1){
         
        if($isPrint == 1 && $isObject == 0){
            info($preFix." ".$msg." ".$postFix);
        }

        if($isPrint == 1 && $isObject == 1){
            info($msg);
            // info($preFix." ".$msg." ".$postFix);
        }
    }
 
  
}