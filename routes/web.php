<?php
// die('ManuaPlly Killed by Lawa');

use App\Http\Controllers\AlertController;
use App\Http\Controllers\Alert\FailCronAlertController;

use App\Http\Controllers\DBConnectionController;
use App\Http\Controllers\EmailSMS\EmailController;
use App\Http\Controllers\EmailSMS\MessageMediaController;
use App\Http\Controllers\LivePushErply\ErplyBinbayController;
use App\Http\Controllers\LivePushErply\ErplyCustomerController;
use App\Http\Controllers\LivePushErply\ErplyProductStockController;
use App\Http\Controllers\LivePushErply\ErplyPurchaseOrderController;
use App\Http\Controllers\LivePushErply\ErplyReasonCodeController;
use App\Http\Controllers\LivePushErply\ErplySalesOrderController;
use App\Http\Controllers\LivePushErply\ErplySupplierController;
use App\Http\Controllers\LivePushErply\ProductAssortmentController;
use App\Http\Controllers\LivePushErply\ProductCategoryController;
use App\Http\Controllers\LivePushErply\ProductController;
use App\Http\Controllers\LivePushErply\ProductDimensionController;
use App\Http\Controllers\LivePushErply\ProductGenericController;
use App\Http\Controllers\LivePushErply\ProductGroupController;
use App\Http\Controllers\LivePushErply\StoreLocationController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\Paei\API\SchoolApiController;
use App\Http\Controllers\Paei\GetAddressController;
use App\Http\Controllers\Paei\GetAssortmentController;
use App\Http\Controllers\Paei\GetCampaignController;
use App\Http\Controllers\Paei\GetCashInsController;
use App\Http\Controllers\Paei\GetCouponController;
use App\Http\Controllers\Paei\GetCurrencyController;
use App\Http\Controllers\Paei\GetCustomerController;
use App\Http\Controllers\Paei\GetCustomerGroupController;
use App\Http\Controllers\Paei\GetDimensionController;
use App\Http\Controllers\Paei\GetEmployeeController;
use App\Http\Controllers\Paei\GetGeneralController;
use App\Http\Controllers\Paei\GetGiftCardController;
use App\Http\Controllers\Paei\GetInventoryRegistrationController;
use App\Http\Controllers\Paei\GetInventoryTransferController;
use App\Http\Controllers\Paei\GetInventoryWriteOffController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Paei\GetMatrixProductController;
use App\Http\Controllers\Paei\GetOpenningClosingController;
use App\Http\Controllers\Paei\GetPaymentController;
use App\Http\Controllers\Paei\GetPaymentTypeController;
use App\Http\Controllers\Paei\GetPricelistController;
use App\Http\Controllers\Paei\GetProductCategoryController;
use App\Http\Controllers\Paei\GetProductGroupController;
use App\Http\Controllers\Paei\GetProductPictureController;
use App\Http\Controllers\Paei\GetProductPictureV2Controller;
use App\Http\Controllers\Paei\GetProductStockController;
use App\Http\Controllers\Paei\GetPurchaseDocumentController;
use App\Http\Controllers\Paei\GetReasonCodeController;
use App\Http\Controllers\Paei\GetSalesDocumentController;
use App\Http\Controllers\Paei\GetSupplierController;
use App\Http\Controllers\Paei\GetUserOperationLogController;
use App\Http\Controllers\Paei\GetWarehouseController;
use App\Http\Controllers\PswClientLive\PswLiveProductController;
use App\Http\Controllers\ProductBulkDeleteController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\PswClientLive\AxCashInOutController;
use App\Http\Controllers\PswClientLive\AxCustomerController;
use App\Http\Controllers\PswClientLive\AxPurchaseController;
use App\Http\Controllers\PswClientLive\AxResyncController;
use App\Http\Controllers\PswClientLive\AxSalesController;
use App\Http\Controllers\PswClientLive\AxTransferOrderController;
use App\Http\Controllers\PswClientLive\PswImageController;
use App\Http\Controllers\PswClientLive\PswLiveCustomerController;
use App\Http\Controllers\PswClientLive\PswLiveExpensesController;
use App\Http\Controllers\PswClientLive\PswLiveGeneralController;
use App\Http\Controllers\PswClientLive\PswLiveProductGenericController;
use App\Http\Controllers\PswClientLive\PswLivePurchaseOrderController;
use App\Http\Controllers\PswClientLive\PswLiveSalesOrderController;
use App\Http\Controllers\PswClientLive\PswLiveStoreLocationController;
use App\Http\Controllers\PswClientLive\PswLiveSupplierController;
use App\Http\Controllers\PswClientLive\PswLiveTransferOrderLineController;
use App\Models\PAEI\VariationProduct;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\StockColorSize;
use App\Models\StockDetail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Request;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// die();
//Currently Disabled


ini_set('max_execution_time', 200000);
ini_set('memory_limit', -1);


//WRITE SYNCCARE TO AX
require __DIR__ . '/read_from_ax.php';
//WRITE SYNCCARE TO AX
require __DIR__ . '/write_to_ax.php';


Route::get("/cache-clear", function (){
    Artisan::call("cache:clear");
    Artisan::call("route:cache");
    Artisan::call("config:cache");
    Artisan::call("optimize"); 

    return response("Cache Cleared Successfully.");
});

#Logs
Route::get("/clear-logs", [LogsController::class, 'clearLogs']);



Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::get("/set-duplicate-flag",function(){

    // $duplicate = VariationProduct::getDuplicateRows();
    // // dd($duplicate);
    // foreach($duplicate as $dup){
    //     VariationProduct::where("code", $dup['code'])->update(["duplicateFlag" => 1]);
    // }
    // echo "Flag set Successfully.";
    // dd($duplicate);

    //now getting duplicates rows 
    // $duplicates = VariationProduct::where("clientCode", "605325")->where("duplicateFlag", 1)->get();
    // // dd($duplicates);
    // foreach($duplicates as $dup){

    //     $axProduct = LiveProductVariation::where("erplyID", $dup->productID)->first();
    //     if($axProduct){
    //         //updated to 0 and remaining all 1 are duplicated
    //         VariationProduct::where("clientCode", "605325")->where('code', $dup->code)->where('productID', $dup->productID)->update(["duplicateFlag" => 0]);
    //     }
    // }
    // echo "Product Updated successfully";

    // $duplicates = VariationProduct::where("clientCode", "605325")->where("duplicateFlag", 1)->get();


});

// Route::get("/updateVariationPending", function(){
//     $vv = LiveProductVariation::where("erplyPending", 1)->groupBy("WEBSKU")->get();

//     foreach($vv as $v){
//         //now updating matrix product
//         LiveProductMatrix::where("WEBSKU", $v->WEBSKU)->update(["variationPending" => 1]);
//     }

//     echo "Flag Updated";
//     // dd($vv);
// });


/************************************************* EMAIL AND SMS *****************************************************/
Route::get('/sendEmail', [EmailController::class, 'sendEmail']);
Route::get('/sendSMS', [MessageMediaController::class, 'sendSMS']);
Route::post('/sms-callback', [MessageMediaController::class, 'callBack']);
Route::get('/check-sms-status', [MessageMediaController::class, 'checkSmsStatus']);
Route::get('/push-daily-sms', [MessageMediaController::class, 'pushDailyMessage']);
Route::get('/push-daily-email', [EmailController::class, 'pushDailyEmail']);



/************************************************* END ***************************************************************/


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get("/get-export", function(){
    return view('index');
});

//FOR ERPLY TO LOCAL DB
Route::get('/get-matrix-dimension', [GetDimensionController::class, 'getMatrixDimension']);
Route::get('/get-matrix-dimension-value', [GetDimensionController::class, 'getMatrixDimensionValue']);
Route::get('/get-product', [GetMatrixProductController::class, 'getProduct']);
Route::get('/get-product-v2', [GetMatrixProductController::class, 'getProductV2']);
Route::get('/get-product-pim', [GetMatrixProductController::class, 'getProductPIM']);
Route::get("/get-product-group", [GetProductGroupController::class, 'getProductGroup']);
Route::get("/get-product-category", [GetProductCategoryController::class, 'getProductCategory']);
Route::get("/get-customer", [GetCustomerController::class, 'getCustomer']);
Route::get("/get-customer-groups", [GetCustomerGroupController::class, 'getCustomerGroups']);
Route::get("/get-warehouse", [GetWarehouseController::class, 'getWarehouse']);
Route::get("/get-product-picture", [GetProductPictureController::class, 'getProductPictures']);
Route::get("/get-product-picture-v2", [GetProductPictureV2Controller::class, 'getProductPictures']);
Route::get("/get-inventory-registration", [GetInventoryRegistrationController::class, 'getInventoryRegistration']);
Route::get("/get-inventory-transfer", [GetInventoryTransferController::class, 'getInventoryTransfer']);
Route::get("/delete-inventory-transfer", [GetInventoryTransferController::class, 'deleteInventoryTransfer']);
Route::get("/get-inventory-write-offs", [GetInventoryWriteOffController::class, 'getInventoryWriteOffs']);
Route::get("/get-campaigns", [GetCampaignController::class, 'saveUpdateCampaigns']);

//server info
Route::get("/get-server-info", [GetGeneralController::class, 'syncServerInfo']);

//customer and supplier addresses getAddressesBySwagger
Route::get("/get-addresses", [GetAddressController::class, 'getAddresses']);
Route::get("/get-addresses-v2", [GetAddressController::class, 'getAddressesBySwagger']);

Route::get("/get-product-stock", [GetProductStockController::class, 'getStock']);

Route::get("/get-reason-codes", [GetReasonCodeController::class, 'getReasonCodes']);

Route::get("/get-currencies", [GetCurrencyController::class, 'getCurrencies']);

//Payment and Payment Types
Route::get("/get-payment-types", [GetPaymentTypeController::class, 'getTypes']);
Route::get("/get-payment", [GetPaymentController::class, 'getPayments']);

//get openning closing day
Route::get("/get-openning-closing-day", [GetOpenningClosingController::class, 'getOpenningClosing']);

//Get Price List
Route::get("/get-pricelist", [GetPricelistController::class, 'getPricelist']);

//get coupons
Route::get("/get-coupons", [GetCouponController::class, 'getCoupons']);

//get coupons
Route::get("/get-employees", [GetEmployeeController::class, 'getEmployees']);

//get gift cards
Route::get("/get-giftcards", [GetGiftCardController::class, 'getGiftCards']);

//Get Sales Documents
Route::get("/get-sales-documents", [GetSalesDocumentController::class, 'getSalesDocuments']);

Route::get("/get-purchase-documents", [GetPurchaseDocumentController::class, 'getPurchaseDocument']);
Route::get("/delete-purchase-documents", [GetPurchaseDocumentController::class, 'deletePurchaseDocument']);
Route::get("/get-cashins", [GetCashInsController::class, 'getCashins']);

Route::get("/get-suppliers", [GetSupplierController::class, 'getSuppliers']);

//for erply to local update product details
Route::get('/lets-update-matrix', [GetMatrixProductController::class, 'letsUpdateMatrix']);


//User Operations Log
Route::get('/get-user-operation-customer', [GetCustomerController::class, 'getOperationLogCustomer']);
Route::get('/get-user-operation-customer-group', [GetCustomerGroupController::class, 'getOperationLogCustomerGroup']);
Route::get('/get-user-operation-products', [GetMatrixProductController::class, 'getOperationLogProduct']);
Route::get('/get-user-operation-product-group', [GetProductGroupController::class, 'getOperationLog']);
Route::get('/get-user-operation-product-category', [GetProductCategoryController::class, 'getOperationLog']);
Route::get('/get-user-operation-warehouse', [GetWarehouseController::class, 'getOperationLog']);

//Get Assortment
Route::get('/get-assortments', [GetAssortmentController::class, 'getAssortment']);


//print receipt
Route::get('/getPickingSlip', [SchoolApiController::class, 'getReceipt']);


//generate product image urls checkImage
Route::get('/update-product-image', [PswImageController::class, 'checkImage']);
Route::get('/update-product-matrix-image', [PswImageController::class, 'checkMatrixImage']);

//check product exist in erply checkProductExistInErply
Route::get('/checkProductExistInErply', [ProductController::class, 'checkProductExistInErply']);


/***************************************************PSW PUSH DATA MIDDLE SERVER TO ERPLY STAGING, TEST, LIVE ******************************************/

//PRODUCT APIS
Route::get('/push-dimension-color', [ProductDimensionController::class, 'syncDimensionColor']);
Route::get('/push-dimension-size', [ProductDimensionController::class, 'syncDimensionSize']);
Route::get('/push-product-group', [ProductGroupController::class, 'syncProductGroup']);
Route::get('/delete-product-group', [ProductGroupController::class, 'deleteProductGroup']);
Route::get('/update-product-group-parent', [ProductGroupController::class, 'updateParentGroup']);
Route::get('/push-product-category', [ProductCategoryController::class, 'syncProductCategory']);
Route::get('/push-product-matrix', [ProductController::class, 'syncMatrixProduct']);
Route::get('/archive-matrix-product', [ProductController::class, 'archiveMatrixProduct']);
Route::get('/push-product-variation', [ProductController::class, 'syncVariationProduct']);
Route::get('/check-null-pending-product-variation', [ProductController::class, 'nullPendingProducts']);



Route::get("/update-erply-sku", [ProductController::class, 'updateErplySkuIcsc']);

//internal product group 
Route::get('/check-secondary-school', [ProductGroupController::class, 'checkSecondarySchool']);


//Product Assortment
//Type Product and Bundle
Route::get("/sync-product-assortment", [ProductAssortmentController::class, 'syncProductAssortment']);
Route::get("/remove-product-assortment", [ProductAssortmentController::class, 'removeProductAssortment']);

//For Generic Product and matrix
Route::get("/sync-generic-product-assortment", [ProductAssortmentController::class, 'genericAssortment']);


//generic matrix product 
Route::get("/push-generic-product-matrix", [ProductGenericController::class, 'syncMatrixProduct']);
Route::get("/check-generic-product-matrix", [ProductGenericController::class, 'checkDuplicateProduct']);
Route::get("/push-generic-product-variation", [ProductGenericController::class, 'syncVariationProduct']);
Route::get("/check-null-pending-generic-product-variation", [ProductGenericController::class, 'checkNullPendingProduct']);

//Product Stock
Route::get("/push-product-stock", [ErplyProductStockController::class, 'syncStock']);
Route::get("/update-product-stock", [ErplyProductStockController::class, 'updateSOH']);
// Route::get("/update-product-stock-v2", [ErplyProductStockController::class, 'updateSOH']);
//Transfer Order Lines
Route::get("/push-transfer-order-lines", [ErplyProductStockController::class, 'syncTransferOrder']);

//Customer
Route::get("/push-customer", [ErplyCustomerController::class, 'syncCustomerToErply']);
Route::get("/push-customer-address", [ErplyCustomerController::class, 'syncCustomerAddress']);

//Warehouse 
Route::get('/push-warehouse', [StoreLocationController::class, 'syncWarehouse']);

//Bin bay location
Route::get('/push-binbay-location', [ErplyBinbayController::class, 'syncBinBayLocations']);
Route::get('/push-binbay-soh', [ErplyBinbayController::class, 'saveBinRecords']);
Route::get('/push-binbay-soh-adjust', [ErplyBinbayController::class, 'adjustBinRecord']);

//Suppliers
Route::get('/push-suppliers', [ErplySupplierController::class, 'syncSupplier']);

//Purchase Orders
Route::get('/push-purchase-orders', [ErplyPurchaseOrderController::class, 'syncPurchaseOrder']);

//Sales Orders
Route::get('/push-sales-orders', [ErplySalesOrderController::class, 'pushSalesOrders']);
Route::get('/push-sales-orders-delivery', [ErplySalesOrderController::class, 'pushSalesDeliveryAddress']);

//Reason Codes
Route::get('/push-reason-codes', [ErplyReasonCodeController::class, 'syncReasonCode']);

 

//shift to write to ax route

// /********************************* SYNCCARE TO AX *********************************************************************************************************/

// //Route::get("/sync-customer-to-ax", [AxCustomerController::class, 'syncMiddlewareToAx']);
// Route::get("/sync-single-customer-to-ax", [AxCustomerController::class, 'syncSingleCustomerMiddleServerToAX']);
// //Sales Orders
// Route::get("/sync-sales-order-to-ax", [AxSalesController::class, 'syncMiddlewareToAx']);
// Route::get("/check-sales-payment-flag", [AxSalesController::class, 'checkPaymentFlag']);
// Route::get("/check-no-sales-line-flag", [AxSalesController::class, 'handleNoLineFlagDocuments']);

// //purchase orders
// Route::get("/sync-purchase-order-to-ax", [AxPurchaseController::class, 'syncPurchaseOrder']);


// //Transfer Orders
// Route::get("/sync-transfer-order-to-ax", [AxTransferOrderController::class, 'syncTransferOrder']);
// Route::get("/sync-transfer-order-invent-trans-id-from-ax", [AxTransferOrderController::class, 'syncTOInventTransID']);

// //CashInOut 
// Route::get("/sync-cashinout-to-ax", [AxCashInOutController::class, 'syncCashInOut']);




// Route::get("/sync-customer-to-ax", function(){
//     echo "hello";
//     die;
// });

/***************************************  END SYNCCARE TO AX ******************************************************************************   */


//updating variation sku
Route::get("/update-variation-sku", function(){
    // $limit = 5000;
    // $variation = StockColorSize::where('product_sku_2','')->limit($limit)->get();
    // // dd($variation);
    // foreach($variation as $v){
    //     // echo $v->product_sku."<br>";
    //     $split = explode("PSW_",$v->product_sku);
    //     $v->product_sku_2 = $split[1];
    //     $v->save();
    //     // die;
    // }
    // echo "Success";
});

Route::get("/update-variation-pos-sku", function(){

    $matrix = StockDetail::where('erplyPending', 0)->where('')->get();
    foreach($matrix as $m){
        $v = StockColorSize::where('web_sku', $m->web_sku)->where('newSystemInternetActive',1)->first();
        if(isset($v->pos_sku) == 0){
            echo $m->web_sku."<br>";
        }
    }
    echo "Success";	
    die;

    $limit = 1000;
    $variation = StockColorSize::join('current_customer_product_relation', function($rel){
                $rel->on("current_customer_product_relation.product_sku", '=' ,'newsystem_stock_colour_size.product_sku_2');
                $rel->on("current_customer_product_relation.web_sku",'=','newsystem_stock_colour_size.web_sku');
            })
            // ->where('newsystem_stock_colour_size.newSystemInternetActive', 1)
            ->where('newsystem_stock_colour_size.pos_sku', '')
            ->select(["current_customer_product_relation.softCode","current_customer_product_relation.product_sku", "newsystem_stock_colour_size.newSystemColourSizeID", "newsystem_stock_colour_size.web_sku"])
            ->limit(1000)
            ->get();
    dd($variation);
    foreach($variation as $v){
        // echo $v->product_sku."<br>";
        // $split = explode("PSW_",$v->product_sku);
        $v->pos_sku = $v['product_sku']."_".$v['softCode'];
        $v->save();
        // die;
    }
    echo "Success pos sku updated";
});


Route::get("/json-query", function(){
$results = DB::table('newsystem_inventory_registrations') ->whereJsonContains('rows', [['productID' => 8158]]) ->get();
dd($results);
});


Route::get("/delete-bulk-products", [ProductBulkDeleteController::class, 'deleteProductUsingTable']);
// Route::get("/get-bulk-archive-products", [ProductBulkDeleteController::class, 'getArchive']);


//updating warehosue erply id
// Route::get("/update-warehouse-erplyid", function(){
//     $limit = 1000;
//     $variation = PAEIWarehouse::where('code','<>', '')->get();

//     // dd($variation);
//     foreach($variation as $v){
//         Warehouse::where('locationid', $v->code)->update(['erplyWarehouseID'=> $v->warehouseID, 'erplyPending' => 0]);
       
//     }
//     echo "Warehouse location updated successfully";
// });
//END

//updating variation inventory registration ID
// Route::get("/update-variation-inventory-id", function(){
//     $limit = 1000;
//     $variation = InventoryRegistration::where('synToVariation', 1)->limit($limit)->get();
//     foreach($variation as $v){
//         // echo $v->product_sku."<br>";
//         StockColorSize::where('product_sku', $v->productSKU)
//         $v->product_sku_2 = $split[1];
//         $v->save();
//         // die;
//     }
//     echo "Success";
// });
//END



//shift to seperate file read_from_ax.php file

// /***************************** PSW CLIENT LIVE DB TO SYNCCARE SERVER *****************************/ 
// /****PRODUCT**********/
// Route::get("/generate-product-dev-file", [PswLiveProductController::class, 'makeProductFile']);
// Route::get("/read-product-dev-file", [PswLiveProductController::class, 'handleProductFile']);
// Route::get("/read-product-size-sort", [PswLiveProductController::class, 'pswToMiddlewareSizeSort']);
// Route::get("/update-erplysku-icsc", [PswLiveProductController::class, 'updateErplySkuIcsc']);

// //sync temp product to current product table
Route::get("/sync-temp-product-to-matrix-product", [PswLiveProductController::class, 'syncTempToCurrentsystemMatrix']);
Route::get("/sync-temp-product-to-currentsystem-product", [PswLiveProductController::class, 'syncTempToCurrentsystem']);
// //productDescription
// Route::get("/make-product-des-file", [PswLiveProductController::class, 'makeDescriptionFile']);
// Route::get("/read-product-des-file", [PswLiveProductController::class, 'readDescriptionFile']);
// Route::get("/sync-product-des-newsystem", [PswLiveProductController::class, 'syncDescriptionNewsystem']);

// //product generic sync
// Route::get("/make-product-generic-file", [PswLiveProductGenericController::class, 'makeProductFile']);
// Route::get("/read-product-generic-file", [PswLiveProductGenericController::class, 'handleProductFile']);
Route::get("/sync-product-generic-newsystem-matrix", [PswLiveProductGenericController::class, 'syncProductGenericNewsystemMatrix']);
// Route::get("/sync-product-generic-newsystem-variation", [PswLiveProductGenericController::class, 'syncProductGenericNewsystemVariation']);
// Route::get("/sync-product-generic-by-lastmodified", [PswLiveProductGenericController::class, 'syncProductAxtoMiddlewareByLastModified']);


// //Item Locations
// Route::get("/make-item-locations-file", [PswLiveProductController::class, 'makeItemLocationFile']);
// Route::get("/read-item-locations-file", [PswLiveProductController::class, 'readItemLocationFile']);
// Route::get("/sync-item-locations", [PswLiveProductController::class, 'syncItemLocationNewsystem']);

// //On Hand Inventory
// Route::get("/make-on-hand-inventory-file", [PswLiveProductController::class, 'makeOnHandInventoryFile']);
// Route::get("/read-on-hand-inventory-file", [PswLiveProductController::class, 'readOnHandInventoryFile']);
// Route::get("/sync-on-hand-inventory-to-newsystem", [PswLiveProductController::class, 'syncOnHandInventoryToNewsystem']);
// Route::get("/sync-on-hand-inventory-by-lastmodified", [PswLiveProductController::class, 'syncOnHandInventoryByLastModified']);


// //Store Location
// Route::get("/generate-store-location", [PswLiveStoreLocationController::class, 'makeStoreLocationFile']);
// Route::get("/read-store-location", [PswLiveStoreLocationController::class, 'handleStoreLocationFile']);
// Route::get("/temp-location-to-live", [PswLiveStoreLocationController::class, 'syncToLive']);

// //Customers 
// Route::get("/generate-customer-flag-file", [PswLiveCustomerController::class, 'makeCustomerFlagFile']);
// Route::get("/read-customer-flag-file", [PswLiveCustomerController::class, 'readAndStoreCustomerFlagFile']);
// Route::get("/sync-customer-flag-to-newsystem", [PswLiveCustomerController::class, 'syncCustomerFlagToNewsystemTable']);
// Route::get("/generate-customer-relation-file", [PswLiveCustomerController::class, 'makeCustomerRelationFile']);
// Route::get("/read-customer-relation-file", [PswLiveCustomerController::class, 'readCustomerRelationFile']);
// Route::get("/sync-customer-relation-to-newsystem", [PswLiveCustomerController::class, 'syncCustomerRelationToNewsystemTable']);
// Route::get("/sync-business-customer-by-lastmodified", [PswLiveCustomerController::class, 'syncBusinessCustomerByLastModified']);

// //Product Item Relation by Warehouse Location and ICSC
// Route::get("/generate-item-by-locations-file", [PswLiveProductController::class, 'makeItemByLocationFile']);
// Route::get("/read-item-by-locations-file", [PswLiveProductController::class, 'readItemByLocationFile']);
Route::get("/sync-item-by-locations-to-newsystem", [PswLiveProductController::class, 'syncItemByLocationtoNewsystem']);
// Route::get("/sync-item-by-locations-by-lastmodified", [PswLiveProductController::class, 'syncItemByLocationtoByLastModified']);

// //Item By ICSC
// Route::get("/generate-item-by-icsc-file", [PswLiveProductController::class, 'makeItemByICSC']);
// Route::get("/read-item-by-icsc-file", [PswLiveProductController::class, 'readItemByICSC']);
// Route::get("/sync-item-by-icsc-to-newsystem", [PswLiveProductController::class, 'syncItemByIcscToNewsystem']);

// //Purchase Orders 
// Route::get("/generate-purchase-orders-file", [PswLivePurchaseOrderController::class, 'makePurchaseOrderFile']);
// Route::get("/read-purchase-orders-file", [PswLivePurchaseOrderController::class, 'readPurchaseOrdersFile']);
// Route::get("/sync-purchase-orders-to-newsystem", [PswLivePurchaseOrderController::class, 'syncPurchaseOrderToNewsystem']);
// Route::get("/sync-purchase-orders-by-lastmodified", [PswLivePurchaseOrderController::class, 'syncPurchaseOrderByLastModified']);

// //suppliers
// Route::get("/make-suppliers-file", [PswLiveSupplierController::class, 'makeSupplierFile']);
// Route::get("/read-suppliers-file", [PswLiveSupplierController::class, 'readSupplierFile']);
// Route::get("/sync-suppliers-to-newsystem", [PswLiveSupplierController::class, 'syncSuppliersToNewsystem']);
// Route::get("/sync-suppliers-by-lastmodified", [PswLiveSupplierController::class, 'syncSupplierByLastModified']);

// //Transfer Order Lines
// Route::get("/make-transfer-order-line-file", [PswLiveTransferOrderLineController::class, 'makeTransferOrderFile']);
// Route::get("/read-transfer-order-line-file", [PswLiveTransferOrderLineController::class, 'readTransferOrderFile']);
// Route::get("/sync-transfer-order-to-newsystem", [PswLiveTransferOrderLineController::class, 'syncTransferOrderToNewsystem']);
// Route::get("/sync-transfer-order-by-lastmodified", [PswLiveTransferOrderLineController::class, 'syncTransferOrderByLastModified']);

// //Sales Order
// Route::get("/make-sales-order-file", [PswLiveSalesOrderController::class, 'makeSalesOrderFile']);
// Route::get("/read-sales-order-file", [PswLiveSalesOrderController::class, 'readSalesOrderFile']);
// Route::get("/sync-sales-order-to-newsystem", [PswLiveSalesOrderController::class, 'syncSalesOrderToNewsystem']);

// //delivery modes and discount codes 
// Route::get("/sync-delivery-modes", [PswLiveGeneralController::class, 'syncDeliveryMode']);
// Route::get("/sync-discount-codes", [PswLiveGeneralController::class, 'syncDiscountCodes']);

// //Expenses Accounts
// Route::get("/sync-expenses-accounts", [PswLiveExpensesController::class, 'syncExpensesAccount']);
// Route::get("/sync-expenses-accounts-by-lastmodified", [PswLiveExpensesController::class, 'syncExpensesAccountByLastModified']);

// Route::get("/sync-expenses-list", [PswLiveExpensesController::class, 'syncExpensesAccountList']);
// Route::get("/sync-expenses-list-by-lastmodified", [PswLiveExpensesController::class, 'syncExpensesAccountListByLastmodified']);

// /********* SYNC AX TO MIDDLEWARE BY LAST MODIFIED DATE TIME ****************/



// //PRODUCT, MATRIX, VARIATION, PRODUCT GROUP, PRODUCT COLOUR, PRODUCT SIZE
// Route::get("/sync-product-by-modified-date", [PswLiveProductController::class, 'syncProductAxtoMiddlewareByLastModified']);
// //PRODUCT DESCRIPTION
// Route::get("/sync-product-description-by-modified-date", [PswLiveProductController::class, 'syncProductDescriptionByLastModified']);
// //Item Locations
// Route::get("/sync-item-locations-by-modified-date", [PswLiveProductController::class, 'syncItemLocationByModifiedDateAndTime']);
// //Warehouse Location
// Route::get("/sync-warehouse-location-by-modified-date", [PswLiveStoreLocationController::class, 'syncItemLocationsByLastModified']);


// //*********************************************** Resync By Product and School ********************************************/
Route::get("/resyncBySchool", [AxResyncController::class, 'resyncBySchool']);
Route::get("/resyncGenericProductBySku", [AxResyncController::class, 'resyncByWebSkuGenericProduct']);
Route::get("/getNotSynccedGenericProduct", [AxResyncController::class, 'getNotSynccedGenericProduct']);
Route::get("/resyncProductBySku", [AxResyncController::class, 'resyncByWebSkuProduct']);
// //special cron
// Route::get("/resync-special-ax-to-synccare", [AxResyncController::class, 'resyncFromAx']);

// /********************************************************************** DETECT DELETED PRODUCT AX  *******************************/
// Route::get("/ax-deleted-product-detector", [AxResyncController::class, 'detectDeletedProductAX']);
// Route::get("/ax-deleted-generic-product-detector", [AxResyncController::class, 'detectGenericDeletedProductAX']);

/********************************END SYNC AX TO MIDDLEWARE BY LAST MODIFIED DATE TIME ****************/




Route::get('/db-connection', [DBConnectionController::class, 'index']);
Route::post('/db-connection-check', [DBConnectionController::class, 'deConnectioncheck'])->name('dbConnectionCheck');

Route::get('/get-product-bulk-swagger-post', [ProductStockController::class, 'getProductSwaggerPost']);

// temp
Route::get('/updateErplyFlag', [PswLiveProductController::class, 'syncErplyFlag']);

Route::get("phpinfo", function(){
    phpinfo();
});





// require __DIR__.'/auth.php';

//test process

Route::get("/process", function(){
    
    $res = Process::path(app_path())->run("mkdir testidir");
    dd($res);
});


//Alert Notification routes
Route::get('/alert-sales-document', [AlertController::class, 'salesOrder']);


/**
 * Send mail if erply to database not sync
 */

Route::get('/send-alert-mail', [FailCronAlertController::class, 'sendMail']);