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

use App\Http\Controllers\PswClientLive\AxResyncController;
use App\Http\Controllers\PswClientLive\PswLiveCustomerController;
use App\Http\Controllers\PswClientLive\PswLiveExpensesController;
use App\Http\Controllers\PswClientLive\PswLiveGeneralController;
use App\Http\Controllers\PswClientLive\PswLiveProductController;
use App\Http\Controllers\PswClientLive\PswLiveProductGenericController;
use App\Http\Controllers\PswClientLive\PswLivePurchaseOrderController;
use App\Http\Controllers\PswClientLive\PswLiveSalesOrderController;
use App\Http\Controllers\PswClientLive\PswLiveStoreLocationController;
use App\Http\Controllers\PswClientLive\PswLiveSupplierController;
use App\Http\Controllers\PswClientLive\PswLiveTransferOrderLineController;
use Illuminate\Support\Facades\Route; 
 
 /***************************** PSW CLIENT LIVE DB TO SYNCCARE SERVER *****************************/ 
/****PRODUCT**********/
Route::get("/generate-product-dev-file", [PswLiveProductController::class, 'makeProductFile']);
Route::get("/read-product-dev-file", [PswLiveProductController::class, 'handleProductFile']);
Route::get("/read-product-size-sort", [PswLiveProductController::class, 'pswToMiddlewareSizeSort']);
Route::get("/update-erplysku-icsc", [PswLiveProductController::class, 'updateErplySkuIcsc']);

//sync temp product to current product table
Route::get("/sync-temp-product-to-matrix-product", [PswLiveProductController::class, 'syncTempToCurrentsystemMatrix']);
Route::get("/sync-temp-product-to-currentsystem-product", [PswLiveProductController::class, 'syncTempToCurrentsystem']);
//productDescription
Route::get("/make-product-des-file", [PswLiveProductController::class, 'makeDescriptionFile']);
Route::get("/read-product-des-file", [PswLiveProductController::class, 'readDescriptionFile']);
Route::get("/sync-product-des-newsystem", [PswLiveProductController::class, 'syncDescriptionNewsystem']);

//product generic sync
Route::get("/make-product-generic-file", [PswLiveProductGenericController::class, 'makeProductFile']);
Route::get("/read-product-generic-file", [PswLiveProductGenericController::class, 'handleProductFile']);
Route::get("/sync-product-generic-newsystem-matrix", [PswLiveProductGenericController::class, 'syncProductGenericNewsystemMatrix']);
Route::get("/sync-product-generic-newsystem-variation", [PswLiveProductGenericController::class, 'syncProductGenericNewsystemVariation']);
Route::get("/sync-product-generic-by-lastmodified", [PswLiveProductGenericController::class, 'syncProductAxtoMiddlewareByLastModified']);


//Item Locations
Route::get("/make-item-locations-file", [PswLiveProductController::class, 'makeItemLocationFile']);
Route::get("/read-item-locations-file", [PswLiveProductController::class, 'readItemLocationFile']);
Route::get("/sync-item-locations", [PswLiveProductController::class, 'syncItemLocationNewsystem']);

//On Hand Inventory
Route::get("/make-on-hand-inventory-file", [PswLiveProductController::class, 'makeOnHandInventoryFile']);
Route::get("/read-on-hand-inventory-file", [PswLiveProductController::class, 'readOnHandInventoryFile']);
Route::get("/sync-on-hand-inventory-to-newsystem", [PswLiveProductController::class, 'syncOnHandInventoryToNewsystem']);
Route::get("/sync-on-hand-inventory-by-lastmodified", [PswLiveProductController::class, 'syncOnHandInventoryByLastModified']);


//Store Location
Route::get("/generate-store-location", [PswLiveStoreLocationController::class, 'makeStoreLocationFile']);
Route::get("/read-store-location", [PswLiveStoreLocationController::class, 'handleStoreLocationFile']);
Route::get("/temp-location-to-live", [PswLiveStoreLocationController::class, 'syncToLive']);

//Customers 
Route::get("/generate-customer-flag-file", [PswLiveCustomerController::class, 'makeCustomerFlagFile']);
Route::get("/read-customer-flag-file", [PswLiveCustomerController::class, 'readAndStoreCustomerFlagFile']);
Route::get("/sync-customer-flag-to-newsystem", [PswLiveCustomerController::class, 'syncCustomerFlagToNewsystemTable']);
Route::get("/generate-customer-relation-file", [PswLiveCustomerController::class, 'makeCustomerRelationFile']);
Route::get("/read-customer-relation-file", [PswLiveCustomerController::class, 'readCustomerRelationFile']);
Route::get("/sync-customer-relation-to-newsystem", [PswLiveCustomerController::class, 'syncCustomerRelationToNewsystemTable']);
Route::get("/sync-business-customer-by-lastmodified", [PswLiveCustomerController::class, 'syncBusinessCustomerByLastModified']);

//Product Item Relation by Warehouse Location and ICSC
Route::get("/generate-item-by-locations-file", [PswLiveProductController::class, 'makeItemByLocationFile']);
Route::get("/read-item-by-locations-file", [PswLiveProductController::class, 'readItemByLocationFile']);
Route::get("/sync-item-by-locations-to-newsystem", [PswLiveProductController::class, 'syncItemByLocationtoNewsystem']);
Route::get("/sync-item-by-locations-by-lastmodified", [PswLiveProductController::class, 'syncItemByLocationtoByLastModified']);

//Item By ICSC
Route::get("/generate-item-by-icsc-file", [PswLiveProductController::class, 'makeItemByICSC']);
Route::get("/read-item-by-icsc-file", [PswLiveProductController::class, 'readItemByICSC']);
Route::get("/sync-item-by-icsc-to-newsystem", [PswLiveProductController::class, 'syncItemByIcscToNewsystem']);

//Purchase Orders 
Route::get("/generate-purchase-orders-file", [PswLivePurchaseOrderController::class, 'makePurchaseOrderFile']);
Route::get("/read-purchase-orders-file", [PswLivePurchaseOrderController::class, 'readPurchaseOrdersFile']);
Route::get("/sync-purchase-orders-to-newsystem", [PswLivePurchaseOrderController::class, 'syncPurchaseOrderToNewsystem']);
Route::get("/sync-purchase-orders-by-lastmodified", [PswLivePurchaseOrderController::class, 'syncPurchaseOrderByLastModified']);

//suppliers
Route::get("/make-suppliers-file", [PswLiveSupplierController::class, 'makeSupplierFile']);
Route::get("/read-suppliers-file", [PswLiveSupplierController::class, 'readSupplierFile']);
Route::get("/sync-suppliers-to-newsystem", [PswLiveSupplierController::class, 'syncSuppliersToNewsystem']);
Route::get("/sync-suppliers-by-lastmodified", [PswLiveSupplierController::class, 'syncSupplierByLastModified']);

//Transfer Order Lines
Route::get("/make-transfer-order-line-file", [PswLiveTransferOrderLineController::class, 'makeTransferOrderFile']);
Route::get("/read-transfer-order-line-file", [PswLiveTransferOrderLineController::class, 'readTransferOrderFile']);
Route::get("/sync-transfer-order-to-newsystem", [PswLiveTransferOrderLineController::class, 'syncTransferOrderToNewsystem']);
Route::get("/sync-transfer-order-by-lastmodified", [PswLiveTransferOrderLineController::class, 'syncTransferOrderByLastModified']);

//Sales Order
Route::get("/make-sales-order-file", [PswLiveSalesOrderController::class, 'makeSalesOrderFile']);
Route::get("/read-sales-order-file", [PswLiveSalesOrderController::class, 'readSalesOrderFile']);
Route::get("/sync-sales-order-to-newsystem", [PswLiveSalesOrderController::class, 'syncSalesOrderToNewsystem']);

//delivery modes and discount codes 
Route::get("/sync-delivery-modes", [PswLiveGeneralController::class, 'syncDeliveryMode']);
Route::get("/sync-discount-codes", [PswLiveGeneralController::class, 'syncDiscountCodes']);

//Expenses Accounts
Route::get("/sync-expenses-accounts", [PswLiveExpensesController::class, 'syncExpensesAccount']);
Route::get("/sync-expenses-accounts-by-lastmodified", [PswLiveExpensesController::class, 'syncExpensesAccountByLastModified']);

Route::get("/sync-expenses-list", [PswLiveExpensesController::class, 'syncExpensesAccountList']);
Route::get("/sync-expenses-list-by-lastmodified", [PswLiveExpensesController::class, 'syncExpensesAccountListByLastmodified']);

/********* SYNC AX TO MIDDLEWARE BY LAST MODIFIED DATE TIME ****************/



//PRODUCT, MATRIX, VARIATION, PRODUCT GROUP, PRODUCT COLOUR, PRODUCT SIZE
Route::get("/sync-product-by-modified-date", [PswLiveProductController::class, 'syncProductAxtoMiddlewareByLastModified']);
//PRODUCT DESCRIPTION
Route::get("/sync-product-description-by-modified-date", [PswLiveProductController::class, 'syncProductDescriptionByLastModified']);
//Item Locations
Route::get("/sync-item-locations-by-modified-date", [PswLiveProductController::class, 'syncItemLocationByModifiedDateAndTime']);
//Warehouse Location
Route::get("/sync-warehouse-location-by-modified-date", [PswLiveStoreLocationController::class, 'syncItemLocationsByLastModified']);


//*********************************************** Resync By Product and School ********************************************/
Route::get("/resyncBySchool", [AxResyncController::class, 'resyncBySchool']);
Route::get("/resyncGenericProductBySku", [AxResyncController::class, 'resyncByWebSkuGenericProduct']);
Route::get("/getNotSynccedGenericProduct", [AxResyncController::class, 'getNotSynccedGenericProduct']);
Route::get("/resyncProductBySku", [AxResyncController::class, 'resyncByWebSkuProduct']);
//special cron
Route::get("/resync-special-ax-to-synccare", [AxResyncController::class, 'resyncFromAx']);

/********************************************************************** DETECT DELETED PRODUCT AX  *******************************/
Route::get("/ax-deleted-product-detector", [AxResyncController::class, 'detectDeletedProductAX']);
Route::get("/ax-deleted-generic-product-detector", [AxResyncController::class, 'detectGenericDeletedProductAX']);









