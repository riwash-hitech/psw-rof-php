<?php

namespace App\Http\Controllers\PswClientLive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PswClientLive\Services\PswLiveProductService;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PswLiveProductController extends Controller
{
    //
    protected $service;

    public function __construct(PswLiveProductService $ps){
      $this->service = $ps;
    }



    //Generating Product File
    public function makeProductFile(Request $req){ 
        try{
            return $this->service->makeProductFile($req); 
        }catch(Exception $e){
            info($e->getMessage());
        }
    }


    //Inserting Product File to Temp Table
    public function handleProductFile(){

        return $this->service->readProductFileAndStore();
        
    }

    public function syncTempToCurrentsystem(Request $req){
        return $this->service->syncTempToCurrentsystemProduct($req);
    }

    //syncing by last modified product
    public function syncProductAxtoMiddlewareByLastModified(){
        return $this->service->syncProductAxtoMiddlewareByLastModified();
    }

    public function updateErplySkuIcsc(){
        return $this->service->updateErplySkuIcsc();
    }

    public function syncTempToCurrentsystemMatrix(){
        return $this->service->syncTempToCurrentsystemProductGCSC();
    }

    public function pswToMiddlewareSizeSort(){
        return $this->service->syncPswLivetoMiddleware();
    }


    //FOR DESCRIPTION

    public function makeDescriptionFile(){
        return $this->service->makeDescriptionFile();
    }

    public function readDescriptionFile(){
        return $this->service->readProductDescriptionAndStore();
    }

    public function syncDescriptionNewsystem(){
        return $this->service->syncDescriptionNewsystem();
    }

    //syncing by last modification date and time
    
    public function syncProductDescriptionByLastModified(){
        return $this->service->syncProductDescriptionByLastModified();
    }


    //FOR ITEM LOCATION

    public function makeItemLocationFile(){
        return $this->service->makeItemLocationFile();
    }

    public function readItemLocationFile(){
        return $this->service->readItemLocationFile();
    }

    public function syncItemLocationNewsystem(){
        return $this->service->syncItemLocationNewsystem();
    }

    //Syncing by latest modification date and time

    public function syncItemLocationByModifiedDateAndTime(){
        return $this->service->syncItemLocationByModifiedDateAndTime();
    }


    //file for item by locations and item by icsc

    public function makeItemByLocationFile(Request $req){
        return $this->service->makeItemByLocationFile($req);
    }

    public function readItemByLocationFile(){
        return $this->service->readItemByLocationFile();
    }

    public function syncItemByLocationtoNewsystem(){
        return $this->service->syncItemByLocationtoNewsystem();
    }

    public function syncItemByLocationtoByLastModified(){
        return $this->service->syncItemByLocationtoByLastModified();
    }

    public function makeItemByICSC(){
        return $this->service->makeItemByICSC();
    }

    public function readItemByICSC(){
        return $this->service->readItemByICSC();
    }

    public function syncItemByIcscToNewsystem(){
        return $this->service->syncItemByIcscToNewsystem();
    }

    //On Hand Inventory

    public function makeOnHandInventoryFile(Request $req){
        return $this->service->makeOnHandInventoryFile($req);
    }

    public function readOnHandInventoryFile(){
        return $this->service->readOnHandInventoryFile();
    }

    public function syncOnHandInventoryToNewsystem(){
        return $this->service->syncOnHandInventoryToNewsystem();
    }

    public function syncOnHandInventoryByLastModified(){
        return $this->service->syncOnHandInventoryByLastModified();
    }

    public function syncErplyFlag(){
        $datas = DB::connection("mysql2")->table("erplyflag0")->select(["*"])->where("pending", 1)->limit(200)->get();

        foreach($datas as $d){
            $d=collect($d);
            //now updating erply flag
            // dd($d);
            LiveProductVariation::where("ERPLYSKU", $d["erplysku"])->update([ "erplyEnabled" => 1]);
            $checkMatrixEnabled = LiveProductVariation::where("WEBSKU", $d["websku"])->where("erplyEnabled", 1)->first();
            if($checkMatrixEnabled){
                LiveProductMatrix::where("WEBSKU", $d["websku"])->update([ "erplyEnabled" => 1]);
            }
            
            DB::connection("mysql2")->table("erplyflag0")->where("id", $d["id"])->update([ "pending" => 0]);

        }
        return response("Success");
        // dd($datas);
    }
    

     
}
 