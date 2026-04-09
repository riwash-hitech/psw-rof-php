<?php

namespace App\Providers; 

use App\Http\Controllers\Paei\GetMatrixProductController; 
use App\Http\Controllers\Paei\Services\GetProductService; 
use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Paei\GetCustomerController;
use App\Http\Controllers\Paei\GetCustomerGroupController;
use App\Http\Controllers\Paei\GetProductGroupController;
use App\Http\Controllers\Paei\GetWarehouseController;
use App\Http\Controllers\Paei\Services\GetCustomerGroupService;
use App\Http\Controllers\Paei\Services\GetCustomerService;
use App\Http\Controllers\Paei\Services\GetProductGroupService;
use App\Http\Controllers\Paei\Services\GetWarehouseService; 
use Illuminate\Support\ServiceProvider; 

class UserOperationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    { 
        //FOR ERPLY USER OPERATION LOGS 
        //PRODUCT
        $this->app->when(GetMatrixProductController::class)
        ->needs(UserOperationInterface::class)
        ->give(function(){
            $getProductService = $this->app->make(GetProductService::class);
            return $getProductService;
        });

        //PRODUCT GROUP 
        $this->app->when(GetProductGroupController::class)
        ->needs(UserOperationInterface::class)
        ->give(function(){
            $service = $this->app->make(GetProductGroupService::class);
            return $service;
        });

        //CUSTOMER
        $this->app->when(GetCustomerController::class)
        ->needs(UserOperationInterface::class)
        ->give(function(){
            $service = $this->app->make(GetCustomerService::class);
            return $service;
        });

        //CUSTOMER GROUPS
        $this->app->when(GetCustomerGroupController::class)
        ->needs(UserOperationInterface::class)
        ->give(function(){
            $service = $this->app->make(GetCustomerGroupService::class);
            return $service;
        });

        //WAREHOSUE
        $this->app->when(GetWarehouseController::class)
        ->needs(UserOperationInterface::class)
        ->give(function(){
            $service = $this->app->make(GetWarehouseService::class);
            return $service;
        });

         
        

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
