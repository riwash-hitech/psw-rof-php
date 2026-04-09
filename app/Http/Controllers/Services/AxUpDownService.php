<?php
namespace App\Http\Controllers\Services;
  
class AxUpDownService
{
     
    static public function isAxReadDown(){
        #   1  = AX IS DOWN
        #   0  = AX IS UP
        return 0;
    }

    static public function isAxWriteDown(){
        #   1  = AX IS DOWN
        #   0  = AX IS UP
        return 0;
    }

}