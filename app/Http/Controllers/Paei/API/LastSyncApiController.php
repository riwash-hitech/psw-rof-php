<?php

namespace App\Http\Controllers\Paei\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use App\Models\PAEI\MatrixProduct;
use App\Models\PAEI\ProductCategory;
use App\Models\PAEI\ProductGroup;
use App\Models\PAEI\SalesDocument;
use App\Models\PAEI\VariationProduct;
use App\Models\PAEI\Warehouse;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Product;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LastSyncApiController extends Controller
{
    //
    protected $matrix;
    protected $variation;
    protected $category;
    protected $group;
    protected $warehouse;
    protected $customer;
    protected $api;

    public function __construct(MatrixProduct $mp, VariationProduct $vp, ProductCategory $pc, ProductGroup $pg, Warehouse $wl, Customer $customer, EAPIService $api){
        $this->matrix = $mp;
        $this->variation = $vp;
        $this->category = $pc;
        $this->group = $pg;
        $this->warehouse = $wl;
        $this->customer = $customer;
        $this->api = $api; 
    }

    public function getLastSyncAll(){

        // $data = DB::select();

        $lastSync = array();
        $customer = $this->customer->where("clientCode", $this->api->client->clientCode )->orderBy('lastModified','desc')->first()->lastModified;
        $category = $this->category->where("clientCode", $this->api->client->clientCode )->orderBy('updated_at','desc')->first()->updated_at;
        $group = $this->group->where("clientCode", $this->api->client->clientCode )->orderBy('changed','desc')->first()->changed;
        $warehouse = $this->warehouse->where("clientCode", $this->api->client->clientCode )->orderBy('changed', 'desc')->first()->changed;
        $matrix = new DateTime($this->matrix->where("clientCode", $this->api->client->clientCode )->orderBy('lastModified', 'desc')->first()->lastModified);
        $variation = new DateTime($this->variation->where("clientCode", $this->api->client->clientCode )->orderBy('lastModified', 'desc')->first()->lastModified);
        if($matrix > $variation){
            array_push($lastSync, array("product" => $this->matrix->orderBy('lastModified', 'desc')->first()->lastModified));
        }else{
            array_push($lastSync, array("product" => $this->variation->orderBy('lastModified', 'desc')->first()->lastModified));
        }
        array_push($lastSync, array("customer" => $customer));
        array_push($lastSync, array("category" => $category));
        array_push($lastSync, array("group" => $group));
        array_push($lastSync, array("warehouse" => $warehouse));



        $lastSync = json_encode($lastSync, true);
        return response()->json(["status"=> 200, "records" => $lastSync]);

    }

    //AX TO SYNCCARE

    public function axToSynccareProduct(){
        $axTotalProduct = Product::count();
        
        // dd($axTotalProduct);
        
        $data = LiveProductVariation::select(
            DB::raw('count(*) as synccareTotalProductAX'),  
            DB::raw('sum(case when erplyPending = 0 then 1 else 0 end) as synccareToErplySyncCompleted'), 
            // DB::raw('sum(case when erplyPending = 1 then 1 else 0 end) as synccareToErplySyncPending'), 
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00') . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '"  then 1 else 0 end) as axToSynccareSyncedToday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-1 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59', strtotime('-1 day')) . '" then 1 else 0 end) as axToSynccareSyncedYesterday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-7 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '" then 1 else 0 end) as axToSynccareSynced7Days')
        )->first()->toArray();
        $data["axTotalProduct"] = $axTotalProduct;
        $data["axToSynccarePending"] = $data["axTotalProduct"] - $data["synccareTotalProductAX"];
        // $data = array_merge($data,$erplyToSynccare);
         
        // dd($data);

        return response()->json(["status"=> 200, "records" => collect($data)]);
    }
    
    public function synccareToErplyProduct(){
        $data = LiveProductVariation::select(
            // DB::raw('count(*) as synccareTotalProductAX'),  
            // DB::raw('sum(case when erplyPending = 0 then 1 else 0 end) as synccareToErplySyncCompleted'), 
            DB::raw('sum(case when erplyPending = 1 then 1 else 0 end) as synccareToErplySyncPending'), 
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00') . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '" and erplyPending = 0  then 1 else 0 end) synccareToErplySyncedToday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-1 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59', strtotime('-1 day')) . '" and erplyPending = 0 then 1 else 0 end) as synccareToErplySyncedYesterday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-7 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '" and erplyPending = 0 then 1 else 0 end) as synccareToErplySynced7Days')
        )->first()->toArray();
        return response()->json(["status"=> 200, "records" => collect($data)]);
    }

    public function erplyToSynccareProduct(){

        $data = VariationProduct::where("clientCode", $this->api->client->clientCode)->where('deleted', 0)->select(
            DB::raw("count(*) as erplyToSynccare"),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00') . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '"  then 1 else 0 end) as erplyToSynccareSyncedToday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-1 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59', strtotime('-1 day')) . '" then 1 else 0 end) as erplyToSynccareSyncedYesterday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-7 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '" then 1 else 0 end) as erplyToSynccareSynced7Days')
        )->first()->toArray();
        
        return response()->json(["status"=> 200, "records" => collect($data)]);
    }


    //for sales orders
    public function erplyToSynccareSalesOrders(){
        $data = SalesDocument::where("clientCode", $this->api->client->clientCode)->select(
            DB::raw("count(*) as erplyToSynccare"),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00') . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '"  then 1 else 0 end) as erplyToSynccareSyncedToday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-1 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59', strtotime('-1 day')) . '" then 1 else 0 end) as erplyToSynccareSyncedYesterday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-7 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '" then 1 else 0 end) as erplyToSynccareSynced7Days')
        )->first()->toArray();
        
        return response()->json(["status"=> 200, "records" => collect($data)]);
    }

    public function synccareToAxSalesOrders(){
        $data = SalesDocument::where("clientCode", $this->api->client->clientCode)->select(
            DB::raw("sum(case when axPending = 0 then 1 else 0 end) as synccareToAXCompleted"),
            DB::raw("sum(case when axPending = 1 then 1 else 0 end) as synccareToAXPending"),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00') . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '" and axPending = 0  then 1 else 0 end) as synccareToAxSyncedToday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-1 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59', strtotime('-1 day')) . '" and axPending = 0 then 1 else 0 end) as synccareToAxSyncedYesterday'),
            DB::raw('sum(case when updated_at >= "' . date('Y-m-d 00:00:00', strtotime('-7 day')) . '" and updated_at <= "' . date('Y-m-d 23:59:59') . '" and axPending = 0 then 1 else 0 end) as synccareToAxSynced7Days')
        )->first()->toArray();
        
        return response()->json(["status"=> 200, "records" => collect($data)]);
    }


}
