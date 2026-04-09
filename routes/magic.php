<?php
 
 
use App\Http\Controllers\Paei\API\AxMagic\ItemByLocationMagicController;
use App\Http\Controllers\Paei\API\AxMagic\ProductMagicController;
use Illuminate\Support\Facades\Route; 
 

//for product get
Route::group(["prefix" => "magic/v1/"], function(){

    //Stock By Location
    Route::get("getItemByLocations", [ItemByLocationMagicController::class, 'getItemByLocations']);
    Route::get("resyncItemByLocation", [ItemByLocationMagicController::class, 'resyncItemByLocation']);
    Route::get("matrixProductErplyEnabled", [ProductMagicController::class, 'updateMatrixErplyEnabled']);
    Route::get("variationProductErplyEnabled", [ProductMagicController::class, 'updateVariationErplyEnabled']);
     
});
 















