<?php

namespace App\Traits;

use App\Models\PswClientLive\AxSystemSequence;
use Illuminate\Support\Facades\DB;

trait AxTrait{

     
    public function getRecID($tabid){
        $systemSeq = DB::connection("sqlsrv_psw_live")->select("SELECT NEXTVAL,RECVERSION FROM SYSTEMSEQUENCES WITH(UPDLOCK, HOLDLOCK) WHERE ID = -1 AND TABID = $tabid");
        $systemSeq = json_encode($systemSeq, true);
        $systemSeq = json_decode($systemSeq, true);
        return $systemSeq[0];
    }

    public function updateRecID($tabid, $nextval){
        $update = DB::connection("sqlsrv_psw_live")->table('SYSTEMSEQUENCES')
            ->where('TABID', $tabid)
            ->where('ID', -1)
            ->update(['NEXTVAL' => $nextval]);
        // $update = AxSystemSequence::where("TABID", $tabid)->where("ID", -1)->update(["NEXTVAL" => $nextval]);
        // ("UPDATE SYSTEMSEQUENCES SET NEXTVAL=$nextval WHERE ID = -1 AND TABID = $tabid");
        
        if($update){
            return true;
        }

        return false;
    }
    

}