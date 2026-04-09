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

use App\Http\Controllers\PswClientLive\AxCashInOutController;
use App\Http\Controllers\PswClientLive\AxCustomerController;
use App\Http\Controllers\PswClientLive\AxPurchaseController;
use App\Http\Controllers\PswClientLive\AxSalesController;
use App\Http\Controllers\PswClientLive\AxTransferOrderController;
use Illuminate\Support\Facades\Route; 
 
 
/********************************* SYNCCARE TO AX *********************************************************************************************************/

//Route::get("/sync-customer-to-ax", [AxCustomerController::class, 'syncMiddlewareToAx']);
Route::get("/sync-single-customer-to-ax", [AxCustomerController::class, 'syncSingleCustomerMiddleServerToAX']);
//Sales Orders
Route::get("/sync-sales-order-to-ax", [AxSalesController::class, 'syncMiddlewareToAx']);
Route::get("/check-sales-payment-flag", [AxSalesController::class, 'checkPaymentFlag']);
Route::get("/check-no-sales-line-flag", [AxSalesController::class, 'handleNoLineFlagDocuments']);

//purchase orders
Route::get("/sync-purchase-order-to-ax", [AxPurchaseController::class, 'syncPurchaseOrder']);


//Transfer Orders
Route::get("/sync-transfer-order-to-ax", [AxTransferOrderController::class, 'syncTransferOrder']);
Route::get("/sync-transfer-order-invent-trans-id-from-ax", [AxTransferOrderController::class, 'syncTOInventTransID']);

//CashInOut 
Route::get("/sync-cashinout-to-ax", [AxCashInOutController::class, 'syncCashInOut']);















