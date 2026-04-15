<?php



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Paei\API\{CustomerAPIController, NotificationApiController, SchoolApiController, WarehouseApiController};
use App\Http\Controllers\UserController;

Route::group(['middleware'=>'auth:sanctum'], function(){

    Route::post("/saveCustomer", [CustomerAPIController::class, 'saveCustomer']);



    //Temp API For School Only
    Route::get("/getSchool", [SchoolApiController::class, 'getSchool']); //only school for selection
    Route::get("/getSchoolV2", [SchoolApiController::class, 'getSchoolV2']); //only school for selection

    Route::get("/getAllSchool", [SchoolApiController::class, 'getAll']); // variation product selected schools only
    Route::get("/getCartOrders", [SchoolApiController::class, 'getOfferOrder']);
    Route::post("/deleteOrder", [SchoolApiController::class, 'deleteOffer']);

    //get customers location wise
    Route::get("/getAllCustomers", [CustomerAPIController::class, 'getAllCustomers']);

    //sales orders
    Route::post("/saveSalesOrder", [SchoolApiController::class, 'salesOrder']);

});

Route::get("/searchCustomers", [CustomerAPIController::class, 'searchCustomers']);

Route::get("/getAllMatrix", [SchoolApiController::class, 'getAllMatrix']); //only school for selection

//for receipt
Route::get("/getReceipt", [SchoolApiController::class, 'getReceipt']);

Route::group(["prefix" => "wms"], function(){
    Route::get("/warehouse/list", [WarehouseApiController::class, 'getWarehouseList']);
    Route::get("/orders", [WarehouseApiController::class, 'warehouseWiseOrders']);
    Route::get("/orders/lines", [WarehouseApiController::class, 'orderLineItemOnly']);
    Route::get("/fulfill/order", [WarehouseApiController::class, 'readyToFulfill']);
    Route::get("/fulfilled/order", [WarehouseApiController::class, 'fulfilledOrders']);
    Route::get("/readyToBePicked/order", [WarehouseApiController::class, 'readyToBePicked']);
    Route::post("/updateToPicked/order", [WarehouseApiController::class, 'updateToPickedOrder']);
    Route::get("/express/order", [WarehouseApiController::class, 'expressOrder']);
    Route::get("/order/count", [WarehouseApiController::class, 'orderCount']);
    Route::get("/search/orders", [WarehouseApiController::class, 'filterOrder']);

    //inventory trnasfer
    Route::get("/inventory/transfer/from", [WarehouseApiController::class, 'getTransferOrderFrom']);
    Route::get("/inventory/transfer/to", [WarehouseApiController::class, 'getTransferOrderTo']);

    //notifications
    Route::get("/sms", [NotificationApiController::class, 'getSmsNotifications']);
    Route::get("/email", [NotificationApiController::class, 'getEmailNotification']);


});

Route::post("/erplyLogin", [UserController::class, 'erplyLogin']);
















