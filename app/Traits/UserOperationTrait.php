<?php

namespace App\Traits;

use App\Classes\UserLogger;
use App\Models\PAEI\UserOperationLog;
use App\Models\PswClientLive\AxSystemSequence;
use Illuminate\Support\Facades\DB;

trait UserOperationTrait{


    public function handleOperationLog($l, $clientCode){
        // foreach($res as $l){ 
            $old = UserOperationLog::where('clientCode', $clientCode)->where("logID", $l["logID"])->where('tableName', $l["tableName"])->first();
            
            $new = UserOperationLog::updateOrcreate(
                [
                    "clientCode" => $clientCode,
                    "logID" => $l["logID"],
                    "tableName" => $l["tableName"]
                ],
                [
                    "clientCode" => $clientCode,
                    "logID" => $l["logID"],
                    "userName" => $l["username"],
                    "tableName" => $l["tableName"],
                    "itemID" => $l['itemID'],
                    "operation" => $l['operation'],
                    "timestamp" => date('Y-m-d H:i:s',$l['timestamp']),
                ]
            ); 
            UserLogger::setChronLogNew($old ? json_encode($old, true) : '', json_encode($new, true), $old  ? "User Operation Log Updated" : "User Operation Log Created");    
     
    }

    public function getLastUpdateDateDelete($table){
        // echo "im call";
        $latest = UserOperationLog::where('clientCode', $this->api->client->clientCode)->where('tableName', $table)->orderBy('timestamp', 'desc')->first();
        if($latest){
            return strtotime($latest->timestamp);
        }
        return 0;// strtotime($latest);
    }
    
    
}