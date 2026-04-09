<?php
namespace App\Http\Controllers\Paei\API\APIServices;

use App\Classes\Except;
use App\Http\Controllers\Paei\Services\GetSalesDocumentService;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Cashin;
use App\Models\PAEI\Currency;
use App\Models\PAEI\Customer;
use App\Models\PAEI\SalesDocument;
use App\Models\PswClientLive\Local\LiveDeliveryMode;
use App\Models\PswClientLive\Local\LiveItemLocation;
use App\Models\PswClientLive\Local\LiveOnHandInventory;
use App\Models\PswClientLive\Local\LiveProductGenericMatrix;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use App\Traits\ResponseTrait; 
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MagicApiService{

     
    use ResponseTrait;

    
    public function genericProduct($req){

        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'ItemName';
        }
        if(isset($req->strictFilter) == 0){
            $req->strictFilter = false;
        }
        
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);
        
        // env("isLive") == true ? "'https://psw.synccare.com.au/php/resyncBySchool?env=LIVE&schoolID='newsystem_product_matrix_live.SchoolID  as extra_column
         
        // $groups = $this->group->paginate($pagination);
        $datas = LiveProductGenericMatrix::
            // ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_matrix_live.DefaultStore")
            // ->where("newstystem_store_location_live.erplyID", $req->posID)
            
            // ->where("newsystem_product_matrix_live.erplyID", ">",0)
            select(["newsystem_product_generic_matrix_live.*",
            DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://psw.synccare.com.au/php/resyncGenericProductBySku?erplysku=', newsystem_product_generic_matrix_live.ERPLYSKU) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/resyncGenericProductBySku?erplysku=', newsystem_product_generic_matrix_live.ERPLYSKU) END as url")
                ]
            )
            ->withCount("variations")
            // ->distinct("newsystem_product_matrix_live.SchoolName")
            // ->withCount("school")
            ->where(function ($q) use ($requestData, $req) {
            foreach ($requestData as $keys => $value) {
                if ($value != null) { 
                    if($req->strictFilter == true){
                        $q->Where($keys, $value);
                    }else{
                        $q->Where($keys, 'LIKE', '%'.$value.'%');
                    }
                    // 'like', '%' . $value . '%'); 
                }
            }
        })
        // ->groupBy("newsystem_product_matrix_live.SchoolID")
        ->orderBy($req->sort_by, $req->direction)->paginate($pagination);
        return $this->successWithData($datas);
        // return response()->json(["status"=>200, "records" => $datas]);
    }

    public function nonGenericProduct($req){
        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'ItemName';
        }
        if(isset($req->strictFilter) == 0){
            $req->strictFilter = false;
        }
        
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);
        
        // env("isLive") == true ? "'https://psw.synccare.com.au/php/resyncBySchool?env=LIVE&schoolID='newsystem_product_matrix_live.SchoolID  as extra_column
         
        // $groups = $this->group->paginate($pagination);
        $datas = LiveProductMatrix::
            // ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_matrix_live.DefaultStore")
            // ->where("newstystem_store_location_live.erplyID", $req->posID)
            
            // ->where("newsystem_product_matrix_live.erplyID", ">",0)
            select(["newsystem_product_matrix_live.*",
            DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://psw.synccare.com.au/php/resyncProductBySku?websku=', newsystem_product_matrix_live.WEBSKU) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/resyncProductBySku?websku=', newsystem_product_matrix_live.WEBSKU) END as url")
                ]
            )
            ->withCount("variations")
            // ->distinct("newsystem_product_matrix_live.SchoolName")
            // ->withCount("school")
            ->where(function ($q) use ($requestData, $req) {
            foreach ($requestData as $keys => $value) {
                if ($value != null) { 
                    if($req->strictFilter == true){
                        $q->Where($keys, $value);
                    }else{
                        $q->Where($keys, 'LIKE', '%'.$value.'%');
                    }
                    // 'like', '%' . $value . '%'); 
                }
            }
        })
        // ->groupBy("newsystem_product_matrix_live.SchoolID")
        ->orderBy($req->sort_by, $req->direction)->paginate($pagination);
        return $this->successWithData($datas);
    }

    public function getWarehouseList($req){
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $list = LiveWarehouseLocation::select([
            "LocationID",
            "LocationName",
            DB::raw("CASE WHEN '". env('isLive') . "' THEN CONCAT('https://psw.synccare.com.au/php/resyncBySchool?storeID=', newstystem_store_location_live.LocationID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/resyncBySchool?storeID=', newstystem_store_location_live.LocationID) END as url")
        ])->paginate($pagination);
        return $this->successWithData($list);
    }


}
