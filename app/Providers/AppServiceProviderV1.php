<?php

namespace App\Providers;

use App\Classes\UserLogger;
//use App\Http\Controllers\EAPI;
use App\Http\Controllers\Paei\API\APIServices\CurrencyApiService;
use App\Http\Controllers\Paei\API\CurrencyApiController;
use App\Http\Controllers\Paei\GetMatrixProductController;
use App\Http\Controllers\Paei\GetUserOperationLogController;
use App\Http\Controllers\Paei\Services\GetProductService;
// use App\Http\Controllers\Paei\Services\UserOperationInterface;
use App\Http\Controllers\ProductBulkDeleteController;
use App\Http\Controllers\Services\EAPIService;
// use App\Interfaces\ApiInterface;
use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Paei\GetCustomerController;
use App\Http\Controllers\Paei\GetCustomerGroupController;
use App\Http\Controllers\Paei\GetWarehouseController;
use App\Http\Controllers\Paei\Services\GetCustomerGroupService;
use App\Http\Controllers\Paei\Services\GetCustomerService;
use App\Http\Controllers\Paei\Services\GetWarehouseService;
use App\Models\Client;
use App\Models\PAEI\MatrixProduct;
use App\Models\PAEI\VariationProduct;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
include("EAPI.class.php");

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        // $this->app->bind(ApiInterface::class, EAPIService::class);
        // $this->app->bind(EAPIService::class, function(){ 
        //     $api = new EAPI();
        //     $client = Client::findOrfail(2);
        //     $api->clientCode = $client->clientCode;
        //     $api->username = $client->username;
        //     $api->password = $client->password;
        //     $api->url = "https://".$api->clientCode.".erply.com/api/";
        //     return new EAPIService($api, $client);
        // });

        // $this->app->bind(Client::class, function(){
        //     $client = Client::findOrfail(2);
        //     return $client;
        // });

        // $this->app->bind(EAPIService::class, function(){ 
        //     $api = new EAPI();
        //     $client = Client::findOrfail(2);
        //     $api->clientCode = $client->clientCode;
        //     $api->username = $client->username;
        //     $api->password = $client->password;
        //     $api->url = "https://".$api->clientCode.".erply.com/api/";
        //     return new EAPIService($api, $client);
        // });
        
        // $this->app->when(GetUserOperationLogController::class)->needs(UserOperationInterface::class)->give(function(Request $req){
            
        // });

        // $this->app->bind(UserOperationInterface::class, function(){

        // });

        $this->app->bind(EAPIService::class, function(){ 

            $req = app(\Illuminate\Http\Request::class);
            $api = new EAPI(); 

            $empLogin = $req->erplyLogin ? $req->erplyLogin : 0;
            // $client = Client::findOrfail(3);
            
            // if($req->env == "TEST"  || $req->entity == "Academy" && env("isLive") == false){
            if(env("isLive") == false){
                $client = Client::findOrfail(3);
                $entity = strtolower($req->entity);
                if($entity == "psw"){
                    $client = Client::findOrfail(5);
                }
            }
            //FOR LIVE
            // if($req->env == "LIVE"  || $req->entity == "Academy" && env("isLive") == true){
            if(env("isLive") == true){
                $client = Client::findOrfail(4);
                $entity = strtolower($req->entity);
                if($entity == "psw"){
                    $client = Client::findOrfail(5);
                }
            }
                
            //if the request is from ROF POS Employee
            if($req->erplyLogin == 1){
                try{

                    $details = auth('sanctum')->user()->email;//->email;
                    //now getting details from client table
                    //$client = Client::where("username", $details)->first();
			info($details.' '. auth('sanctum')->user()->clientCode);
		    $client = Client::where("username", $details)->where('clientCode', auth('sanctum')->user()->clientCode)->first();
			info($client);
                }catch(Exception $e){
			info($e);
                    return response()->json(["message" => "Authentication Failed."]);
                }
                // if($client->ENV == 1){

                // }
                // dd($details);//, $details["id"], $details["email"]);
            }

            if(isset($client) == 0){
                info($req);
                info(url()->current()."*****API Called Without Parameters***********************************************");
		return response()->json(["message" => "Authentication Failed."]);
                die;
            }

            $api->clientCode = $client->clientCode;
            $api->username = $client->username;
            $api->password = $client->password;
            $api->url = "https://".$api->clientCode.".erply.com/api/";
            return new EAPIService($api, $client);
        });

        // $this->app->bind(MessageMediaMessagesClient);
        


        // //FOR ERPLY USER OPERATION LOGS 
        // //PRODUCT
        // $this->app->when(GetMatrixProductController::class)
        // ->needs(UserOperationInterface::class)
        // ->give(function(){
        //     $getProductService = $this->app->make(GetProductService::class);
        //     return $getProductService;
        // });

        // //CUSTOMER
        // $this->app->when(GetCustomerController::class)
        // ->needs(UserOperationInterface::class)
        // ->give(function(){
        //     $service = $this->app->make(GetCustomerService::class);
        //     return $service;
        // });

        // //CUSTOMER GROUPS
        // $this->app->when(GetCustomerGroupController::class)
        // ->needs(UserOperationInterface::class)
        // ->give(function(){
        //     $service = $this->app->make(GetCustomerGroupService::class);
        //     return $service;
        // });

        // //WAREHOSUE
        // $this->app->when(GetWarehouseController::class)
        // ->needs(UserOperationInterface::class)
        // ->give(function(){
        //     $service = $this->app->make(GetWarehouseService::class);
        //     return $service;
        // });

        // $this->app->when(ProductBulkDeleteController::class)->needs(EAPIService::class)->give(function(){
        //     $api = new EAPI();
        //     $client = Client::findOrfail(3);
        //     $api->clientCode = $client->clientCode;
        //     $api->username = $client->username;
        //     $api->password = $client->password;
        //     $api->url = "https://".$api->clientCode.".erply.com/api/";
        //     return new EAPIService($api, $client);
        // });

        // $this->app->when(CurrencyApiService::class)->needs(EAPIService::class)->give(function(){
        //     $api = new EAPI();
        //     $client = Client::findOrfail(2);
        //     $api->clientCode = $client->clientCode;
        //     $api->username = $client->username;
        //     $api->password = $client->password;
        //     $api->url = "https://".$api->clientCode.".erply.com/api/";
        //     return new EAPIService($api, $client);
        // });

        // $this->app->when(ProductBulkDeleteController::class)->needs(EAPIService::class, function(){
        //     $api = new EAPI();
        //     $client = Client::findOrfail(1);
        //     $api->clientCode = $client->clientCode;
        //     $api->username = $client->username;
        //     $api->password = $client->password;
        //     $api->url = "https://".$api->clientCode.".erply.com/api/";
        //     return new EAPIService($api, $client);
        // });

        // $this->app->when(CurrencyApiService::class)->needs(EAPIService::class, function(){
        //     $api = new EAPI();
        //     $client = Client::findOrfail(1);
        //     $api->clientCode = $client->clientCode;
        //     $api->username = $client->username;
        //     $api->password = $client->password;
        //     $api->url = "https://".$api->clientCode.".erply.com/api/";
        //     return new EAPIService($api, $client);
        // });

        // $this->app->when(ProductBulkDeleteController::class, function($app){
        //     $this->app->bind(EAPIService::class, function(){ 
        //         $api = new EAPI();
        //         $client = Client::findOrfail(1);
        //         $api->clientCode = $client->clientCode;
        //         $api->username = $client->username;
        //         $api->password = $client->password;
        //         $api->url = "https://".$api->clientCode.".erply.com/api/";
        //         return new EAPIService($api, $client);
        //     });
    
        //     $this->app->bind(Client::class, function(){
        //         $client = Client::findOrfail(1);
        //         return $client;
        //     });
        // });

        // $this->app->when(CurrencyApiController::class, function($app)
        // {
        //     $this
        //     $this->app->bind(EAPIService::class, function(){ 
        //         $api = new EAPI();
        //         $client = Client::findOrfail(2);
        //         $api->clientCode = $client->clientCode;
        //         $api->username = $client->username;
        //         $api->password = $client->password;
        //         $api->url = "https://".$api->clientCode.".erply.com/api/";
        //         return new EAPIService($api, $client);
        //     });
    
        //     $this->app->bind(Client::class, function(){
        //         $client = Client::findOrfail(2);
        //         return $client;
        //     });
        // });
           
      
        

        

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        view()->composer('*', function($view)
        {
            if (Auth::check()) {
                $view->with('currentUser', Auth::user());
            }else {
                $view->with('currentUser', null);
            }
        });
    }
}
