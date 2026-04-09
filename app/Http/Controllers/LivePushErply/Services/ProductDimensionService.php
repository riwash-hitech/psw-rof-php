<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\LiveProductColor;
use App\Models\PswClientLive\Local\LiveProductSize;
use Illuminate\Http\Request;

class ProductDimensionService{
    //
    protected $api;
    protected $dimColor;
    protected $dimSize;
 

    public function __construct(EAPIService $api, LiveProductColor $color, LiveProductSize $size){
        $this->api = $api;
        $this->dimColor = $color;
        $this->dimSize = $size;
        
    }

    public function syncDimColor(){

        return $this->syncDimColorV2(); 
        die;

        $colors = $this->dimColor->where('name', '<>', '')->where('pendingProcess', 1)->limit(100)->get();
        
        $bulkParam = array();
        foreach($colors as $c){
            $param = array(
                "requestName" => "addItemToMatrixDimension",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "dimensionID" => 1,
                "name" => $c->name,
                "value" => $c->name
            );

            if($c->erplyColorID > 0){
                $param["dimensionID"] = $c->erplyColorID;
            }



            array_push($bulkParam, $param);
        }

        if(count($bulkParam) < 1){
            info("All Product Color Sync");
            return response()->json(["status" => "success", "message" => "All Product Color Sync"]);
        }

        $bulkParam = json_encode($bulkParam, true);
        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        $res = $this->api->sendRequest($bulkParam, $bulkparam,1,0,0);
        // info($res);
        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($colors as $key => $c){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    $c->erplyColorID = $res['requests'][$key]['records'][0]['itemID'];
                    $c->pendingProcess = 0;
                    $c->save();
                    info($res['requests'][$key]['records'][0]['itemID']." Item added");
                }else{
                    info("Error ". $res['requests'][$key]['status']['errorCode']." Color Code". $c->name);
                }
            } 
        }
        return response()->json(['status'=>200, 'response'=> $res]);

    }

    public function syncDimColorV2(){

        $colors = $this->dimColor->where('name', '<>', '')->where($this->api->flag == true ? 'pendingProcess' : 'pswPending', 1)->limit(100)->get();
        
        if($colors->isEmpty()){
            info("All Product Color Syncced to Erply.");
            return response("All Product Color Syncced to Erply.");
            die;
        }
        

        $bulkParam = array();
        foreach($colors as $c){
            $param = array(
                "requestName" => "addItemToMatrixDimension",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "dimensionID" => 1,
                "name" => $c->name,
                "value" => $c->name
            );

            if($this->api->flag == true){
                if($c->erplyColorID > 0){
                    $param["dimensionID"] = $c->erplyColorID;
                }
            }
            if($this->api->flag == false){
                if($c->pswColorID > 0){
                    $param["dimensionID"] = $c->pswColorID;
                }
            } 

            array_push($bulkParam, $param);
        }

        if(count($bulkParam) < 1){
            info("All Product Color Sync");
            return response()->json(["status" => "success", "message" => "All Product Color Sync"]);
        }

        $bulkParam = json_encode($bulkParam, true);
        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

        $res = $this->api->sendRequest($bulkParam, $bulkparam,1,0,0);
        // info($res);
        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($colors as $key => $c){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    if($this->api->flag == true){
                        $c->erplyColorID = $res['requests'][$key]['records'][0]['itemID'];
                        $c->pendingProcess = 0;
                        $c->save();
                    }
                    if($this->api->flag == false){
                        $c->pswColorID = $res['requests'][$key]['records'][0]['itemID'];
                        $c->pswPending = 0;
                        $c->save();
                    }
                    
                    info($res['requests'][$key]['records'][0]['itemID']." Item added");
                }else{
                    info("Error ". $res['requests'][$key]['status']['errorCode']." Color Code". $c->name);
                }
            } 
        }
        return response()->json(['status'=>200, 'response'=> $res]);
    }

    public function syncDimSize(){

        return $this->syncDimSizeV2(); 
        die;
        $sizes = $this->dimSize->where('name', '<>', '')->where('pendingProcess', 1)->limit(100)->get();
        
        
        $bulkParam = array();
        foreach($sizes as $s){
            $param = array(
                "requestName" => "addItemToMatrixDimension",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "dimensionID" => 8,
                "name" => $s->name,
                "value" => $s->name
            );

            if($s->erplySizeID > 0){
                $param["dimensionID"] = $s->erplySizeID;
            }

            array_push($bulkParam, $param);
        }

        if(count($bulkParam) < 1){
            info("All Product Size Sync");
            return response()->json(["status" => "success", "message" => "All Product Size Sync"]);
        }

        $bulkParam = json_encode($bulkParam, true);
        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

         
        $res = $this->api->sendRequest($bulkParam, $bulkparam,1,1,0);
         
        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($sizes as $key => $s){
                if($res['requests'][$key]['status']['errorCode'] == 0){

                    $s->erplySizeID = $res['requests'][$key]['records'][0]['itemID'];
                    $s->pendingProcess = 0;
                    $s->save();
                    info($res['requests'][$key]['records'][0]['itemID']." SIze Item added");
                }else{
                    info("error ".$res['requests'][$key]['status']['errorCode']."  cisieze code ". $s->name);
                }
            }
            
        }
        return response()->json(['status'=>200, 'response'=> $res]);


    }

    public function syncDimSizeV2(){
        $sizes = $this->dimSize->where('name', '<>', '')->where($this->api->flag == true ? 'pendingProcess' : 'pswPending', 1)->limit(100)->get();
        
        if($sizes->isEmpty()){
            info("All Product Size Syncced to Erply.");
            return response("All Product Size Syncced to Erply.");
            die;
        }
        
        $bulkParam = array();
        foreach($sizes as $s){
            $param = array(
                "requestName" => "addItemToMatrixDimension",
                "sessionKey" => $this->api->client->sessionKey,//$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "dimensionID" => 8,
                "name" => $s->name,
                "value" => $s->name
            );

            
            if($this->api->flag == true){
                if($s->erplySizeID > 0){
                    $param["dimensionID"] = $s->erplySizeID;
                }
            }
            if($this->api->flag == false){
                if($s->pswSizeID > 0){
                    $param["dimensionID"] = $s->pswSizeID;
                }
            }

            $bulkParam[] = $param;
        }

        if(count($bulkParam) < 1){
            info("All Product Size Sync");
            return response()->json(["status" => "success", "message" => "All Product Size Sync"]);
        }

        $bulkParam = json_encode($bulkParam, true);
        $bulkparam = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );

         
        $res = $this->api->sendRequest($bulkParam, $bulkparam,1,1,0);
         
        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($sizes as $key => $s){
                if($res['requests'][$key]['status']['errorCode'] == 0){

                    if($this->api->flag == true){
                        $s->erplySizeID = $res['requests'][$key]['records'][0]['itemID'];
                        $s->pendingProcess = 0;
                        $s->save();
                    }
                    if($this->api->flag == true){
                        $s->pswSizeID = $res['requests'][$key]['records'][0]['itemID'];
                        $s->pswPending = 0;
                        $s->save();
                    }

                    info($res['requests'][$key]['records'][0]['itemID']." SIze Item added");
                }else{
                    info("error ".$res['requests'][$key]['status']['errorCode']."  cisieze code ". $s->name);
                }
            }
            
        }
        return response()->json(['status'=>200, 'response'=> $res]);
    }
}
