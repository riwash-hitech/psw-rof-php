<?php
namespace App\Http\Controllers\PswClientLive\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\ItemByICSC;
use App\Models\PswClientLive\ItemByLocation;
use App\Models\PswClientLive\ItemLocation;
use App\Models\PswClientLive\Local\AxSyncDatetime;
use App\Models\PswClientLive\Local\LiveItemByICSC;
use App\Models\PswClientLive\Local\LiveItemByLocation;
use App\Models\PswClientLive\Local\LiveItemLocation;
use App\Models\PswClientLive\Local\LiveOnHandInventory;
use App\Models\PswClientLive\Local\LiveProductCategory;
use App\Models\PswClientLive\Local\LiveProductColor;
use App\Models\PswClientLive\Local\LiveProductDescription;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductSize;
use App\Models\PswClientLive\Local\LiveProductSizeSortOrder;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\TempItemByICSC;
use App\Models\PswClientLive\Local\TempItemByLocation;
use App\Models\PswClientLive\Local\TempItemLocation;
use App\Models\PswClientLive\Local\TempOnHandInventory;
use App\Models\PswClientLive\Local\TempProduct;
use App\Models\PswClientLive\Local\TempProductDescription;
use App\Models\PswClientLive\OnHandInventory;
use App\Models\PswClientLive\Product;
use App\Models\PswClientLive\ProductDescription;
use App\Models\PswClientLive\ProductSizeSortOrder;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;
use App\Traits\ColourSizeTrait;
use Exception;
use App\Traits\DebugTrait;

class PswLiveProductService{

    use ResponseTrait, ColourSizeTrait, DebugTrait;
    protected $psw_live_product;
    protected $temp_product;
    protected $currentsystem_product_matrix_live;
    protected $currentsystem_product_variation_live;
    protected $api;

    public function __construct(Product $psw_product, TempProduct $temp_product, LiveProductMatrix $currentsystem_product_matrix, LiveProductVariation $currentsystem_product_variation, EAPIService $api){
        $this->psw_live_product = $psw_product;
        $this->temp_product = $temp_product;
        $this->currentsystem_product_matrix_live = $currentsystem_product_matrix;
        $this->currentsystem_product_variation_live = $currentsystem_product_variation;
        $this->api = $api;
    }

    function escapeFunc($val){
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $val);
        // $val = str_replace("\",'\\',$val);
        // $val = str_replace("\\","\\\\",$val);
        // $val = str_replace("'","\'",$val);
        // $val = str_replace('"','\"',$val);
        // $val = trim($val);
        // return DB::getPdo()->quote($val);
        // return $val;
    }


    public function makeProductFile($req){

        return $this->makeProductFileV2($req);
        die;
        // if(date("H:i") >= "17:07" ){
        //     info("make product dev file cron killed");
        //     die;
        // }

        ini_set('memory_limit', -1);
        $limit = $req->limit ? $req->limit : 5000;
        info("************************************** Product Dev by Last Modified date : Ax to Synccare Cron Called.***************************************");

        $path = public_path('PswLiveTemp');

        File::delete($path . '/productDev.txt');

        if (!File::exists($path)) {

            File::makeDirectory($path);

        }

        // dd("hello product cron");

        $lastModified = AxSyncDatetime::where("id", 1)->first()->product_dev;
        // echo $lastModified;
        // die;
        if('0000-00-00 00:00' == $lastModified || '0000-00-00' == $lastModified || is_null($lastModified) == true || $lastModified == '0000-00-00 00:00:00.000'){
            if($this->api->isLiveEnv() == 1){
                $datas = Product::orderByRaw(
                    "CASE 
                        WHEN [Item Last Modified] <= [SOF Last Modified] AND [Item Last Modified] <= [School Last Modified] AND [Item Last Modified] <= [Price Last Modified] THEN [Item Last Modified]
                        WHEN [SOF Last Modified] <= [Item Last Modified] AND [SOF Last Modified] <= [School Last Modified] AND [SOF Last Modified] <= [Price Last Modified] THEN [SOF Last Modified]
                        WHEN [School Last Modified] <= [Item Last Modified] AND [School Last Modified] <= [SOF Last Modified] AND [School Last Modified] <= [Price Last Modified] THEN [School Last Modified]
                        ELSE [Price Last Modified]
                    END ASC" 
                )
                ->limit($limit)
                ->get();
            }
            if(env("isLive") == false){
                $datas = Product::orderByRaw(
                    "CASE 
                        WHEN [Item Last Modified] <= [SOF Last Modified] AND [Item Last Modified] <= [School Last Modified]  THEN [Item Last Modified]
                        WHEN [SOF Last Modified] <= [Item Last Modified] AND [SOF Last Modified] <= [School Last Modified]  THEN [SOF Last Modified]
                        ELSE [School Last Modified]
                    END ASC" 
                )
                ->limit($limit)
                ->get();
            }
            
            
                    // orderBy("Item Last Modified", "asc")->limit($limit)->get();
        }else{

            if($this->api->isLiveEnv() == 1){
                // $datas = DB::connection("sqlsrv_psw_live")->select("SELECT TOP '.$limit.' *
                // FROM ERPLY_ItemMaster_DEV
                // WHERE
                //     [Item Last Modified] >= "'.$lastModified.'" 
                //     OR [SOF Last Modified] >= "'.$lastModified.'"
                //     OR [School Last Modified] >= "'.$lastModified.'"
                //     OR [Price Last Modified] >= "'.$lastModified.'"
                // ORDER BY
                //     CASE
                //         WHEN [Item Last Modified] <= [SOF Last Modified] AND [Item Last Modified] <= [School Last Modified] AND [Item Last Modified] <= [Price Last Modified] THEN [Item Last Modified]
                //         WHEN [SOF Last Modified] <= [Item Last Modified] AND [SOF Last Modified] <= [School Last Modified] AND [SOF Last Modified] <= [Price Last Modified] THEN [SOF Last Modified]
                //         WHEN [School Last Modified] <= [Item Last Modified] AND [School Last Modified] <= [SOF Last Modified] AND [School Last Modified] <= [Price Last Modified] THEN [School Last Modified]
                //         ELSE [Price Last Modified]
                //     END ASC");

                $datas = DB::connection("sqlsrv_psw_live")->select("SELECT TOP '.$limit.' *
                            FROM (
                                SELECT TOP '.$limit.' *
                                FROM ERPLY_ItemMaster_DEV
                                WHERE [Item Last Modified] >= '".$lastModified."'
                                ORDER BY [Item Last Modified] ASC
                                UNION
                                SELECT TOP '.$limit.' *
                                FROM ERPLY_ItemMaster_DEV
                                WHERE [SOF Last Modified] >= '".$lastModified."'
                                ORDER BY [SOF Last Modified] ASC
                                UNION
                                SELECT TOP '.$limit.' *
                                FROM ERPLY_ItemMaster_DEV
                                WHERE [School Last Modified] >= '".$lastModified."'
                                ORDER BY [School Last Modified] ASC
                                UNION
                                SELECT TOP '.$limit.' *
                                FROM ERPLY_ItemMaster_DEV
                                WHERE [Price Last Modified] >= '".$lastModified."'
                                ORDER BY [Price Last Modified] ASC
                            ) AS Subquery
                            ORDER BY Subquery.[Item Last Modified] ASC");

                // dd($datas);
                // $datas = Product::where(function ($query) use ($lastModified) {
                //     $query->where("Item Last Modified", ">=", $lastModified)
                //         ->orWhere("SOF Last Modified", ">=", $lastModified)
                //         ->orWhere("School Last Modified", ">=", $lastModified)
                //         ->orWhere("Price Last Modified", ">=", $lastModified);
                //     })
                //     ->orderByRaw(
                //         "CASE 
                //             WHEN [Item Last Modified] <= [SOF Last Modified] AND [Item Last Modified] <= [School Last Modified] AND [Item Last Modified] <= [Price Last Modified]  THEN [Item Last Modified]
                //             WHEN [SOF Last Modified] <= [Item Last Modified] AND [SOF Last Modified] <= [School Last Modified] AND [SOF Last Modified] <= [Price Last Modified] THEN [SOF Last Modified]
                //             WHEN [School Last Modified] <= [Item Last Modified] AND [School Last Modified] <= [SOF Last Modified] AND [School Last Modified] <= [Price Last Modified] THEN [School Last Modified]
                //             ELSE [Price Last Modified]
                //         END ASC" 
                //     )
                //     ->limit($limit)
                //     ->get();
            }

            if($this->api->isLiveEnv() == 0){
                $datas = Product::where(function ($query) use ($lastModified) {
                    $query->where("Item Last Modified", ">=", $lastModified)
                        ->orWhere("SOF Last Modified", ">=", $lastModified)
                        ->orWhere("School Last Modified", ">=", $lastModified);
                        // ->orWhere("Price Last Modified", ">=", $lastModified);
                    })
                    ->orderByRaw(
                        "CASE 
                            WHEN [Item Last Modified] <= [SOF Last Modified] AND [Item Last Modified] <= [School Last Modified]  THEN [Item Last Modified]
                            WHEN [SOF Last Modified] <= [Item Last Modified] AND [SOF Last Modified] <= [School Last Modified]  THEN [SOF Last Modified]
                            ELSE [School Last Modified]
                        END ASC" 
                    )
                    ->limit($limit)
                    ->get();
            }
            
   
        }

        // info("********************************************************************".count($datas)." Products read from AX... ***********************************************");
         
        // echo count($products);
        // die;
        // dd($datas);
        $chunkProduct = $datas->chunk(500);

        foreach ($chunkProduct as $cpro) {

            $content = 'Insert into `temp_product_dev`(`SchoolID`,
                    `SchoolName`,
                    `CustomerGroup`,
                    `ERPLYSKU`,
                    `WEBSKU`,
                    `ITEMID`,
                    `ItemName`,
                    `ColourID`,
                    `ColourName`,
                    `SizeID`,
                    `CONFIGID`,
                    `ConfigName`,
                    `EANBarcode`,
                    `SOFTemplate`,
                    `SOFName`,
                    `SOFOrder`,
                    `SOFStatus`,
                    `PLMStatus`,
                    `ProductType`,
                    `ProductSubType`,
                    `Supplier`,
                    `Gender`,
                    `CategoryName`,
                    `ItemWeightGrams`,
                    `RetailSalesPrice`,
                    `RetailSalesPrice2`,
                    `RetailSalesPriceExclGST`,
                    `RetailSalesPriceExclGST2`,
                    `CostPrice`,
                    `DefaultStore`,
                    `SecondaryStore`,
                    `ERPLYFLAG`, 
                    `AvailableForPurchase`,
                    `WebEnabled`,
                    `SOFLastModified`,
                    `ItemLastModified`,
                    `SchoolLastModified`,
                    `PriceLastModified`,
                    `Category_Name`,
                    `PSWPRICELISTITEMCATEGORY`,
                    `ICSC`,
                    `genericFlag`,
                    `erplyEnabled`,
                    `customItemName`,
                    `receiptDescription`,
                    `pendingProcess`) VALUES ';
            
            $q = '';

            $count = 1; 
            foreach ($cpro as $key => $value) {
 

                $sep = ',';
                if($cpro->last() === $value){
                    $sep = ';';
                }
                if($chunkProduct->last() == $cpro){
                    if($cpro->last() === $value){
                        $sep = '';
                    }  
                }
                 

                // $sep = ',';
                // if($cpro->last() === $value){
                //     $sep = ';';
                // }
                // // $sep = $cpd->last() == $value ? ';' : ',';

                // if($chunkProduct->last() === $cpro) {
                //     // if($cpd->last() == $value){
                //     if($cpro->last() === $value){
                //         $sep = '';
                //     }
                // }

                $q .= '( "'. $value['School ID'] . '",
                        ' . $this->escapeFunc($value['School Name']) . ',
                        "' . $value['Customer Group'] . '",
                        "' . $value['ERPLY SKU'] . '",
                        "' . $value['WEB SKU'] . '",
                        "' . $value['ITEMID'] . '",
                        ' . $this->escapeFunc($value['Item Name']) . ',
                        "' . $value['ColourID'] . '",
                        ' . $this->escapeFunc($value['Colour Name']) . ',
                        "' . $value['SizeID'] . '",
                        "' . $value['CONFIGID'] . '",
                        ' . $this->escapeFunc($value['Config Name']) . ',
                        "' . $value['EAN Barcode'] . '",
                        "' . $value['SOF Template'] . '",
                        ' . $this->escapeFunc($value['SOF Name']) . ',
                        "' . $value['SOF Order'] . '",
                        "' . $value['SOF Status'] . '",
                        "' . $value['PLM Status'] . '",
                        "' . $value['Product Type'] . '",
                        "' . $value['Product Sub Type'] . '",
                        "' . $value['Supplier'] . '",
                        "' . $value['Gender'] . '",
                        "' . $value['Category Name'] . '",
                        "' . $value['Item Weight - grams'] . '",
                        "' . $value['Retail Sales Price'] . '",
                        "' . $value['Retail Sales Price2'] . '",
                        "' . $value['Retail Sales Price excl GST'] . '",
                        "' . $value['Retail Sales Price excl GST2'] . '",
                        "' . $value['Cost Price'] . '",
                        "' . $value['Default Store'] . '",
                        "' . $value['Secondary Store'] . '",
                        "' . $value['ERPLY Flag'] . '",
                        "' . $value['Available for Purchase'] . '",
                        "' . $value['Web Enabled'] . '", 
                        "' . $value['SOF Last Modified'] . '", 
                        "' . $this->makeNullDate($value['Item Last Modified']) . '", 
                        "' . $this->makeNullDate($value['School Last Modified']) . '", 
                        "' . $this->makeNullDate(@$value['Price Last Modified']) . '", 
                        "' . $value['CATEGORYNAME'] . '", 
                        "' . $value['PSW_PRICELISTITEMCATEGORY'] . '", 
                        "' . $value['ICSC'] . '", 
                        "' . $value['Generic Flag'] . '", 
                        "' . $value['ERPLY_Enabled'] . '", 
                        "' . $value['Custom Item Name'] . '", 
                        "' . $value['ReceiptDescription'] . '", 
                        "1")' . $sep;

                    $count++;

            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/productDev.txt', $content);

        }

        info("******************************************************************** Product Dev File Generated Successfully. ***********************************************");
        // "' . $value['ERPLY FLAG Modified'] . '",
        return $this->readProductFileAndStore();
        // return $this->successWithMessage("Product File Generated Successfully.");
    }
    public function makeProductFileV2($req){

        // if(date("H:i") >= "17:07" ){
        //     info("make product dev file cron killed");
        //     die;
        // }

        ini_set('memory_limit', -1);
        $limit = $req->limit ? $req->limit : 25000;
        info("************************************** Product Dev by Last Modified date : Ax to Synccare Cron Called.***************************************");

        $path = public_path('PswLiveTemp');

        File::delete($path . '/productDev.txt');

        if (!File::exists($path)) {

            File::makeDirectory($path);

        } 

        $lastModified = AxSyncDatetime::where("id", 1)->first()->product_dev;
       
        if('0000-00-00 00:00' == $lastModified || '0000-00-00' == $lastModified || is_null($lastModified) == true || $lastModified == '0000-00-00 00:00:00.000'){
            if($this->api->isLiveEnv() == 1){
                $datas = Product::orderByRaw(
                    "CASE 
                        WHEN [Item Last Modified] <= [SOF Last Modified] AND [Item Last Modified] <= [School Last Modified] AND [Item Last Modified] <= [Price Last Modified] THEN [Item Last Modified]
                        WHEN [SOF Last Modified] <= [Item Last Modified] AND [SOF Last Modified] <= [School Last Modified] AND [SOF Last Modified] <= [Price Last Modified] THEN [SOF Last Modified]
                        WHEN [School Last Modified] <= [Item Last Modified] AND [School Last Modified] <= [SOF Last Modified] AND [School Last Modified] <= [Price Last Modified] THEN [School Last Modified]
                        ELSE [Price Last Modified]
                    END ASC" 
                )
                ->limit($limit)
                ->get();
            }
            if(env("isLive") == false){
                $datas = Product::orderByRaw(
                    "CASE 
                        WHEN [Item Last Modified] <= [SOF Last Modified] AND [Item Last Modified] <= [School Last Modified]  THEN [Item Last Modified]
                        WHEN [SOF Last Modified] <= [Item Last Modified] AND [SOF Last Modified] <= [School Last Modified]  THEN [SOF Last Modified]
                        ELSE [School Last Modified]
                    END ASC" 
                )
                ->limit($limit)
                ->get();
            }
            
            
                    // orderBy("Item Last Modified", "asc")->limit($limit)->get();
        }else{

            if($this->api->isLiveEnv() == 1){
               
                // $datas = DB::connection("sqlsrv_psw_live")->select("SELECT TOP $limit *
                //             FROM (
                //                 SELECT TOP $limit *
                //                 FROM ERPLY_ItemMaster_DEV
                //                 WHERE [Item Last Modified] >= '".$lastModified."'
                //                 ORDER BY [Item Last Modified] ASC
                //                 UNION
                //                 SELECT TOP $limit *
                //                 FROM ERPLY_ItemMaster_DEV
                //                 WHERE [SOF Last Modified] >= '".$lastModified."'
                //                 ORDER BY [SOF Last Modified] ASC
                //                 UNION
                //                 SELECT TOP $limit *
                //                 FROM ERPLY_ItemMaster_DEV
                //                 WHERE [School Last Modified] >= '".$lastModified."'
                //                 ORDER BY [School Last Modified] ASC
                //                 UNION
                //                 SELECT TOP $limit *
                //                 FROM ERPLY_ItemMaster_DEV
                //                 WHERE [Price Last Modified] >= '".$lastModified."'
                //                 ORDER BY [Price Last Modified] ASC
                //             ) AS Subquery
                //             ORDER BY Subquery.[Item Last Modified] ASC");
                $datas = DB::connection("sqlsrv_psw_live")->select("SELECT top $limit  *
                FROM (
                    SELECT  *,
                        CASE
                            WHEN [Item Last Modified] >= [SOF Last Modified] 
                                 AND [Item Last Modified] >= [School Last Modified] 
                                 AND [Item Last Modified] >= [Price Last Modified] THEN [Item Last Modified]
                            WHEN [SOF Last Modified] >= [Item Last Modified] 
                                 AND [SOF Last Modified] >= [School Last Modified] 
                                 AND [SOF Last Modified] >= [Price Last Modified] THEN [SOF Last Modified]
                            WHEN [School Last Modified] >= [Item Last Modified] 
                                 AND [School Last Modified] >= [SOF Last Modified] 
                                 AND [School Last Modified] >= [Price Last Modified] THEN [School Last Modified]
                            WHEN [Price Last Modified] >= [Item Last Modified] 
                                AND [Price Last Modified] >= [SOF Last Modified] 
                                AND [Price Last Modified] >= [School Last Modified] THEN [Price Last Modified]
                        END AS MaxModifiedDate
                    FROM ERPLY_ItemMaster_DEV
                ) AS Subquery
                WHERE MaxModifiedDate >= '$lastModified' 
                order by MaxModifiedDate asc");
 
            }

            if($this->api->isLiveEnv() == 0){
                $datas = Product::where(function ($query) use ($lastModified) {
                    $query->where("Item Last Modified", ">=", $lastModified)
                        ->orWhere("SOF Last Modified", ">=", $lastModified)
                        ->orWhere("School Last Modified", ">=", $lastModified);
                        // ->orWhere("Price Last Modified", ">=", $lastModified);
                    })
                    ->orderByRaw(
                        "CASE 
                            WHEN [Item Last Modified] <= [SOF Last Modified] AND [Item Last Modified] <= [School Last Modified]  THEN [Item Last Modified]
                            WHEN [SOF Last Modified] <= [Item Last Modified] AND [SOF Last Modified] <= [School Last Modified]  THEN [SOF Last Modified]
                            ELSE [School Last Modified]
                        END ASC" 
                    )
                    ->limit($limit)
                    ->get();
            }
            
   
        }

        // info("********************************************************************".count($datas)." Products read from AX... ***********************************************");
         
        $datas = collect($datas);
        $chunkDatas = $datas->chunk(500);

        foreach ($chunkDatas as $index => $cpro) {
            $values = [];

            foreach ($cpro as $value) { 
                $values[] = sprintf(
                    '("%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s")',
                    $value->{'School ID'},
                    $this->escapeFunc($value->{'School Name'}),
                    $value->{'Customer Group'},
                    $value->{'ERPLY SKU'},
                    $value->{'WEB SKU'},
                    $value->{'ITEMID'},
                    $this->escapeFunc($value->{'Item Name'}),
                    $value->{'ColourID'},
                    $value->{'Colour Name'},
                    $value->{'SizeID'},
                    $value->{'CONFIGID'},
                    $this->escapeFunc($value->{'Config Name'}),
                    $value->{'EAN Barcode'},
                    $value->{'SOF Template'},
                    $this->escapeFunc($value->{'SOF Name'}),
                    $value->{'SOF Order'},
                    $value->{'SOF Status'},
                    $value->{'PLM Status'},
                    $value->{'Product Type'},
                    $value->{'Product Sub Type'},
                    $value->{'Supplier'},
                    $value->{'Gender'},
                    $value->{'Category Name'},
                    $value->{'Item Weight - grams'},
                    $value->{'Retail Sales Price'},
                    $value->{'Retail Sales Price2'},
                    $value->{'Retail Sales Price excl GST'},
                    $value->{'Retail Sales Price excl GST2'},
                    $value->{'Cost Price'},
                    $value->{'Default Store'},
                    $value->{'Secondary Store'},
                    $value->{'ERPLY Flag'},
                    $value->{'Available for Purchase'},
                    $value->{'Web Enabled'},
                    $value->{'SOF Last Modified'},
                    $this->makeNullDate($value->{'Item Last Modified'}),
                    $this->makeNullDate($value->{'School Last Modified'}),
                    $this->makeNullDate($value->{'Price Last Modified'}),
                    $value->{'CATEGORYNAME'},
                    $value->{'PSW_PRICELISTITEMCATEGORY'},
                    $value->{'ICSC'},
                    $value->{'Generic Flag'},
                    $value->{'ERPLY_Enabled'},
                    $this->escapeFunc($value->{'Custom Item Name'}),
                    $this->escapeFunc($value->{'ReceiptDescription'}),
                    $value->{'MaxModifiedDate'},
                    '1'
                );
            }

            $valuesString = implode(',', $values);

            $content = "INSERT INTO `temp_product_dev`(`SchoolID`, `SchoolName`, `CustomerGroup`, `ERPLYSKU`, `WEBSKU`, `ITEMID`, `ItemName`, `ColourID`, `ColourName`, `SizeID`, `CONFIGID`, `ConfigName`, `EANBarcode`, `SOFTemplate`, `SOFName`, `SOFOrder`, `SOFStatus`, `PLMStatus`, `ProductType`, `ProductSubType`, `Supplier`, `Gender`, `CategoryName`, `ItemWeightGrams`, `RetailSalesPrice`, `RetailSalesPrice2`, `RetailSalesPriceExclGST`, `RetailSalesPriceExclGST2`, `CostPrice`, `DefaultStore`, `SecondaryStore`, `ERPLYFLAG`, `AvailableForPurchase`, `WebEnabled`, `SOFLastModified`, `ItemLastModified`, `SchoolLastModified`, `PriceLastModified`, `Category_Name`, `PSWPRICELISTITEMCATEGORY`, `ICSC`, `genericFlag`, `erplyEnabled`, `customItemName`, `receiptDescription`, `maxModifiedDatetime`, `pendingProcess`) VALUES $valuesString;\n";

            File::append($path . '/productDev.txt', $content);
        }
        // dd("file generated successfully");
        $check = TempProduct::where("pendingProcess", 1)->orWhere("GCSC", 1)->first();
        // $check = TempProduct::where("pendingProcess", 1)->first();
        if(!$check){
            TempProduct::truncate();
        }

        info("******************************************************************** Product Dev File Generated Successfully. ***********************************************");
        $sqlFile = $path . '/productDev.txt';
        DB::connection('mysql2')->unprepared(file_get_contents($sqlFile));
        info("******************************************************************** Product Dev File Executed Successfully. ***********************************************");
        
        $latest = TempProduct::
            whereNull("created_at")
            ->orderBy("maxModifiedDatetime", 'desc')
            ->first();
         
        if ($latest) {
            AxSyncDatetime::where("id", 1)
                // ->where("product_dev", "<=", $latest->latest_date)
                ->update(["product_dev" => $latest->maxModifiedDatetime]);
        }

        info("******************************************************************** Product Dev File Sync Datetime Updated Successfully. ***********************************************");
        return response("******************************************************************** Product Dev File Executed Successfully. ***********************************************");
        // "' . $value['ERPLY FLAG Modified'] . '",
        // return $this->readProductFileAndStore();
        // return $this->successWithMessage("Product File Generated Successfully.");
    }

    private function makeNullDate($date){
        if($date == ''){
            return '0000-00-00';
        }
        return $date;
    }

    public function readProductFileAndStore(){
        $path = public_path('PswLiveTemp/productDev.txt');

        if (File::exists($path)) { 
             
            // $tempProduct = $this->temp_product->where('pendingProcess', 0)->count(); 
            $check = TempProduct::where("pendingProcess", 1)->orWhere("GCSC", 1)->first();
            // $check = TempProduct::where("pendingProcess", 1)->first();
            if(!$check){
                TempProduct::truncate();
            }
             
            return $this->processFile($path); 
            
        } else{
            echo "no file";
            die;
        }
    }

    protected function processFile($path){ 


        info("******************************************************************** Product Dev File Processing... ***********************************************");
        
        $file = File::get($path);
         
        $sqls = explode(";\n", $file); 
        
        foreach ($sqls as $sql) {
              
            if ($sql != '') { 
                DB::connection('mysql2')->select($sql); 
            }
            
           
        }   

        $latest = TempProduct::selectRaw(
                    "GREATEST(`ItemLastModified`, `SOFLastModified`, `SchoolLastModified`, `PriceLastModified`) AS latest_date"
                    )
                    ->orderByRaw(
                        "GREATEST(`ItemLastModified`, `SOFLastModified`, `SchoolLastModified`, `PriceLastModified`) DESC"
                    )
                    ->whereNull("created_at")
                    ->first();
        // dd($latest);
        if ($latest) {
            AxSyncDatetime::where("id", 1)
                ->where("product_dev", "<=", $latest->latest_date)
                ->update(["product_dev" => $latest->latest_date]);
        }
  
        info("******************************************************************** Product Dev File Executed Successfully. ***********************************************");

        return response("******************************************************************** Product Dev File Executed Successfully. ***********************************************");

        // return $this->successWithMessage("Product File Executed Successfully.");
  
    }

    // public function syncTempToCurrentsystemMatrix(){
    //     //get data from temp table
    //     $temp_product = $this->temp_product->where('matrixPending', '1')->groupBy('WEBSKU')->limit(500)->get();

    //     if($temp_product->isEmpty()){
    //         return response("All Matrix synced to current system");
    //     }
    //     foreach($temp_product as $temp_p){
    //         $flag = $this->temp_product->where('WEBSKU', $temp_p->WEBSKU)->where('WebEnabled', '1')->first();
    //         $isActive = 1;
    //         if(!$flag){
    //             $isActive = 0;
    //         }
    //         $this->currentsystem_product_matrix_live->updateOrcreate(
    //             [
    //                 "WEBSKU" => trim($temp_p->WEBSKU)
    //             ],
    //             [
    //                 "SchoolID" =>    trim($temp_p->SchoolID),
    //                 "SchoolName" =>  trim($temp_p->SchoolName),
    //                 "CustomerGroup" =>   trim($temp_p->CustomerGroup),
    //                 "ERPLYSKU" =>    trim($temp_p->ERPLYSKU),
    //                 "WEBSKU" =>  trim($temp_p->WEBSKU),
    //                 "ITEMID" =>  trim($temp_p->ITEMID),
    //                 "ItemName" =>    trim($temp_p->ItemName),
    //                 "ColourID" =>    trim($temp_p->ColourID),
    //                 "ColourName" =>  trim($temp_p->ColourName),
    //                 "SizeID" =>  trim($temp_p->SizeID),
    //                 "CONFIGID" =>    trim($temp_p->CONFIGID),
    //                 "ConfigName" =>  trim($temp_p->ConfigName),
    //                 "EANBarcode" =>  trim($temp_p->EANBarcode),
    //                 "SOFTemplate" => trim($temp_p->SOFTemplate),
    //                 "SOFName" => trim($temp_p->SOFName),
    //                 "SOFOrder" =>    trim($temp_p->SOFOrder),
    //                 "SOFStatus" =>   trim($temp_p->SOFStatus),
    //                 "PLMStatus" =>   trim($temp_p->PLMStatus),
    //                 "ProductType" => trim($temp_p->ProductType),
    //                 "ProductSubType" =>  trim($temp_p->ProductSubType),
    //                 "Supplier" =>    trim($temp_p->Supplier),
    //                 "Gender" =>  trim($temp_p->Gender),
    //                 "CategoryName" =>    trim($temp_p->CategoryName),
    //                 "ItemWeightGrams" => trim($temp_p->ItemWeightGrams),
    //                 "RetailSalesPrice" =>    trim($temp_p->RetailSalesPrice == '' ? '0.00' : $temp_p->RetailSalesPrice),
    //                 "RetailSalesPriceExclGST" => trim($temp_p->RetailSalesPriceExclGST == '' ? '0.00' : $temp_p->RetailSalesPriceExclGST),
    //                 "CostPrice" =>   trim($temp_p->CostPrice == '' ? '0.00' : $temp_p->CostPrice),
    //                 "DefaultStore" =>    trim($temp_p->DefaultStore),
    //                 "SecondaryStore" =>  trim($temp_p->SecondaryStore),
    //                 "ERPLYFLAG" =>   trim($temp_p->ERPLYFLAG),
    //                 "ERPLYFLAGModified" =>   trim($temp_p->ERPLYFLAGModified),
    //                 "AvailableForPurchase" =>    trim($temp_p->AvailableForPurchase),
    //                 "WebEnabled" => $isActive,
    //                 "SOFLastModified" => $temp_p->SOFLastModified,
    //                 "ItemLastModified" => $temp_p->ItemLastModified == '' ? "0000-00-00 00:00:00" : $temp_p->ItemLastModified,
    //                 "PSWPRICELISTITEMCATEGORY" => $temp_p->PSWPRICELISTITEMCATEGORY,
    //                 "genericProduct" => $temp_p->genericFlag,
    //                 "Category_Name" => $temp_p->Category_Name,
    //                 // "ICSC" => $temp_p->ICSC,
    //                 "erplyPending" => 1,
                    

    //             ]
    //         );

    //         $checkSchool = LiveProductGroup::where("SchoolID", trim($temp_p->SchoolID))->first();

    //         if(!$checkSchool){
    //             LiveProductGroup::updateOrcreate(
    //                 [
    //                     "SchoolID" => trim($temp_p->SchoolID) , 
    //                 ],
    //                 [
    //                     "SchoolID" => trim($temp_p->SchoolID), 
    //                     "SchoolName" => trim($temp_p->SchoolName) , 
    //                     "WebEnabled" => $temp_p->WebEnabled,
    //                     "pendingProcess" => 1
    //                 ]
    //             );
    //         }
            
             

    //         //updang flag
    //         $this->temp_product->where("WEBSKU", $this->escapeFunc($temp_p->WEBSKU))->update(["matrixPending" => 0]);

    //     }

    //     return $this->successWithMessage("Temp Product Sync Successfully.");
    // }


    // public function syncTempToCurrentsystem(){
    //     //get data from temp table
    //     // $temp_product = $this->temp_product->where('pendingProcess', '1')->limit(500)->get();
    //     $temp_product = TempProduct::where('pendingProcess', 1)->limit(200)->get();
    //     // dd($temp_product);
    //     if($temp_product->isEmpty()){
    //         return response("All Variation Product synced to current system");
    //     }

    //     // dd($temp_product);
    //     // foreach($temp_product as $tpv){

    //     //     $chk = $this->currentsystem_product_variation_live->where("ERPLYSKU", $tpv->ERPLYSKU)->first();
    //     //     if($chk){
    //     //         LiveProductVariation::where("ERPLYSKU", $tpv->ERPLYSKU)->update(["ICSC" => $tpv->ICSC]);
    //     //     }

    //     // }
    //     foreach($temp_product as $temp_p){


    //         // $chekcProduct = LiveProductVariation::where("ERPLYSKU", trim($temp_p->ERPLYSKU))->first();

    //         // if(!$chekcProduct){
    //             LiveProductVariation::updateOrcreate(
    //                 [
    //                     "ERPLYSKU" => trim($temp_p->ERPLYSKU)
    //                 ],
    //                 [
    //                     "SchoolName" => trim($temp_p->SchoolName),
    //                     "SchoolID" => trim($temp_p->SchoolID),
    //                     "CustomerGroup" => trim($temp_p->CustomerGroup),
    //                     "ERPLYSKU" => trim($temp_p->ERPLYSKU),
    //                     "WEBSKU" => trim($temp_p->WEBSKU),
    //                     "ITEMID" => trim($temp_p->ITEMID),
    //                     "ItemName" => trim($temp_p->ItemName),
    //                     "ColourID" => trim($temp_p->ColourID),
    //                     "ColourName" => trim($temp_p->ColourName),
    //                     "SizeID" => trim($temp_p->SizeID),
    //                     "CONFIGID" => trim($temp_p->CONFIGID),
    //                     "ConfigName" => trim($temp_p->ConfigName),
    //                     "EANBarcode" => trim($temp_p->EANBarcode),
    //                     "SOFTemplate" => trim($temp_p->SOFTemplate),
    //                     "SOFName" => trim($temp_p->SOFName),
    //                     "SOFOrder" => trim($temp_p->SOFOrder),
    //                     "SOFStatus" => trim($temp_p->SOFStatus),
    //                     "PLMStatus" => trim($temp_p->PLMStatus),
    //                     "ProductType" => trim($temp_p->ProductType),
    //                     "ProductSubType" => trim($temp_p->ProductSubType),
    //                     "Supplier" => trim($temp_p->Supplier),
    //                     "Gender" => trim($temp_p->Gender),
    //                     "CategoryName" => trim($temp_p->CategoryName),
    //                     "ItemWeightGrams" => trim($temp_p->ItemWeightGrams),
    //                     "RetailSalesPrice" =>trim($temp_p->RetailSalesPrice == '' ? '0.00' : $temp_p->RetailSalesPrice),
    //                     "RetailSalesPriceExclGST" => trim($temp_p->RetailSalesPriceExclGST == '' ? '0.00' : $temp_p->RetailSalesPriceExclGST),
    //                     "CostPrice" =>  trim($temp_p->CostPrice == '' ? '0.00' : $temp_p->CostPrice),
    //                     "DefaultStore" => trim($temp_p->DefaultStore),
    //                     "SecondaryStore" => trim($temp_p->SecondaryStore),
    //                     "ERPLYFLAG" => trim($temp_p->ERPLYFLAG),
    //                     // "ERPLYFLAGModified" => trim($temp_p->ERPLYFLAGModified),
    //                     "AvailableForPurchase" => trim($temp_p->AvailableForPurchase),
    //                     "WebEnabled" => trim($temp_p->WebEnabled),
    //                     "SOFLastModified" => $temp_p->SOFLastModified,
    //                     "ItemLastModified" => $temp_p->ItemLastModified == '' ? "0000-00-00 00:00:00" : $temp_p->ItemLastModified,
    //                     "PSWPRICELISTITEMCATEGORY" => $temp_p->PSWPRICELISTITEMCATEGORY,
    //                     "Category_Name" => $temp_p->Category_Name,
    //                     "ICSC" => $temp_p->ICSC,
    //                     "genericProduct" => $temp_p->genericFlag,
    //                     // "erplyPending" => 1
    //                 ]
    //             );
    //         // }
    //         LiveProductMatrix::where("WEBSKU", $temp_p->WEBSKU)->update(["variationPending" => 1]);

    //         //Now saving Product Color and Size
    //         $checkColor = LiveProductColor::where('name', trim($temp_p->ColourName))->first();
    //         if(!$checkColor){
    //             LiveProductColor::create(["name" => trim($temp_p->ColourName) ]);
    //         }

    //         $checkColorSize = LiveProductSize::where('name', trim($temp_p->SizeID))->first();
    //         if(!$checkColorSize){
    //             LiveProductSize::create(["name" => trim($temp_p->SizeID) ]);
    //         }

    //         //For Category
    //         $checkCat = LiveProductCategory::where('name', $temp_p->ProductType)->first();
    //         if($temp_p->ProductType != ''){
    //             if(!$checkCat){
    //                 LiveProductCategory::updateOrcreate(
    //                     [
    //                         'name' => $temp_p->ProductType
    //                     ],
    //                     [
    //                         'name' => $temp_p->ProductType,
    //                         'pendingProcess' => 1
    //                     ]
    //                 );
    //             }
    //         }

    //     //     //For Group
    //     //     // $checkGroup = LiveProductGroup::where('SchoolName', $temp_p->SchoolName)->first();
    //     //     // if(!$checkGroup){
                
    //     //     // }
    //     //     //updang flag
    //         $temp_p->pendingProcess = '0';
    //         $temp_p->save();

    //     }
    //     return response("Product synccing to newsystem");
    //     // info("Temp Product Sync to Live Product Variation");
    //     return $this->successWithMessage("Temp Product Variation Sync Successfully.");
    // }

    // private function compareVariation($new, $old) : bool{
    //     $newCP = $new->SchoolID.'_'.trim($new->ItemName).'_'.$new->ColourID.'_'.$new->SizeID.'_'.$new->CONFIGID.'_'.$new->EANBarcode.'_'.$new->RetailSalesPrice.'_'
    //             .$new->WebEnabled.'_'.$new->genericFlag.'_'.$new->erplyEnabled.'_'.$new->customItemName.'_'.$new->receiptDescription.'_';

    //     $oldCP = $old->SchoolID.'_'.trim($old->ItemName).'_'.$old->ColourID.'_'.$old->SizeID.'_'.$old->CONFIGID.'_'.$old->EANBarcode.'_'.$old->RetailSalesPrice.'_'
    //             .$old->WebEnabled.'_'.$old->genericProduct.'_'.$old->erplyEnabled.'_'.$old->customItemName.'_'.$old->receiptDescription.'_';
    //     if($newCP == $oldCP){
    //         return 0;
    //     }

    //     return 1;
    // }

    // private function compareMatrix($new, $old, $newErplyEnabled, $newWebEnabled) : bool{
    //     $newCP = $new->SchoolID.'_'.trim($new->ItemName).'_'.$old->DefaultStore.'_' 
    //             .$newWebEnabled.'_'.$new->genericFlag.'_'.$newErplyEnabled.'_'.$new->customItemName.'_'.$new->receiptDescription.'_';

    //     $oldCP = $old->SchoolID.'_'.trim($old->ItemName).'_'
    //             .$old->WebEnabled.'_'.$old->genericProduct.'_'.$old->erplyEnabled.'_'.$old->customItemName.'_'.$old->receiptDescription.'_';
    //     if($newCP == $oldCP){
    //         return 0;
    //     }

    //     return 1;
    // }

    public function syncTempToCurrentsystemProduct($req){

        $limit = $req->limit ? $req->limit : 100;

        $datas = TempProduct::where("pendingProcess", 1)
                ->orderBy("maxModifiedDatetime", 'asc')
                // ->orderByRaw(
                //     "LEAST(`ItemLastModified`, `SOFLastModified`, `SchoolLastModified`, `PriceLastModified`) ASC"
                // )
                ->limit($limit)
                ->get();

        if($datas->isEmpty()){
            info("Product Temp to Current System : All Syncced.");
            return response("Product Temp to Current System : All Syncced.");
        }

        foreach($datas as $key => $value){
            try{
                DB::transaction(function ()use( $value) {
                    //now update last modified date time if last
                    
                    //first check is this product exist
                    $vAassPending = 0;
                    $check = LiveProductVariation::where("ERPLYSKU", $value->ERPLYSKU)->first();
                    $varErplyPending = 0;

                    $vcompare = $value->ERPLYFLAG."_".$value->SchoolID.'_'.trim($value->ItemName).'_'.$value->ColourID.'_'.trim($value->SizeID).'_'.$value->CONFIGID.'_'.$value->EANBarcode.'_'.$value->RetailSalesPrice.'_'
                    .$value->DefaultStore.'_'.$value->SecondaryStore.'_'.$value->WebEnabled.'_'.$value->genericFlag.'_'.$value->erplyEnabled.'_'.trim($value->customItemName).'_'.trim($value->receiptDescription).'_'.$value->ItemLastModified.'_'.$value->PriceLastModified.'_';
                    $isVarExist = 0;
                    if($check){
                        $isVarExist = 1;
                        $old = $check->DefaultStore."_".$check->SecondaryStore;

                        $new = $value->SecondaryStore ? $value->SecondaryStore : '';
                        if($old != $value->DefaultStore."_".$new){
                            $vAassPending = 1;
                        } 

                        //if exist check compare field
                        $checkCompareFiled = LiveProductVariation::where("ERPLYSKU", $value->ERPLYSKU)->where("compareField", $vcompare)->first();
                        if(!$checkCompareFiled){
                            $varErplyPending = 1;
                        }else{
                            $varErplyPending = $check->erplyPending;    
                        }
                    }
 
                    $vdetails = array(
                        "SchoolName" => trim($value->SchoolName) ,
                        "SchoolID" => $value->SchoolID,
                        "CustomerGroup" => $value->CustomerGroup,
                        "ERPLYSKU" => $value->ERPLYSKU,
                        "WEBSKU" => $value->WEBSKU,
                        "ITEMID" => $value->ITEMID,
                        "ItemName" => trim($value->ItemName),
                        "ColourID" => $value->ColourID,
                        "ColourName" => trim($value->ColourName),
                        "SizeID" => $value->SizeID,
                        "CONFIGID" => $value->CONFIGID,
                        "ConfigName" => trim($value->ConfigName),
                        "EANBarcode" => $value->EANBarcode,
                        "SOFTemplate" => $value->SOFTemplate,
                        "SOFName" => trim($value->SOFName),
                        "SOFOrder" => $value->SOFOrder,
                        "SOFStatus" => $value->SOFStatus,
                        "PLMStatus" => $value->PLMStatus,
                        "ProductType" => $value->ProductType,
                        "ProductSubType" => $value->ProductSubType,
                        "Supplier" => $value->Supplier,
                        "Gender" => $value->Gender,
                        "CategoryName" => $value->CategoryName,
                        "ItemWeightGrams" => $value->ItemWeightGrams,
                        "RetailSalesPrice" => $value->RetailSalesPrice ? $value->RetailSalesPrice : "0.00",
                        "RetailSalesPrice2" => $value->RetailSalesPrice2 ? $value->RetailSalesPrice2 : "0.00",
                        "RetailSalesPriceExclGST" => $value->RetailSalesPriceExclGST ? $value->RetailSalesPriceExclGST : "0.00",
                        "RetailSalesPriceExclGST2" => $value->RetailSalesPriceExclGST2 ? $value->RetailSalesPriceExclGST2 : "0.00",
                        "CostPrice" => $value->CostPrice ? $value->CostPrice : "0.00",
                        "DefaultStore" => $value->DefaultStore,
                        "SecondaryStore" => $value->SecondaryStore,
                        "ERPLYFLAG" => $value->ERPLYFLAG,
                        "AvailableForPurchase" => $value->AvailableForPurchase,
                        "WebEnabled" => $value->WebEnabled,
                        "SOFLastModified" => $value->SOFLastModified,
                        "ItemLastModified" => $value->ItemLastModified,
                        "SchoolLastModified" => $value->SchoolLastModified,
                        "PriceLastModified" => $value->PriceLastModified,
                        "Category_Name" => $value->Category_Name,
                        "PSWPRICELISTITEMCATEGORY" => $value->PSWPRICELISTITEMCATEGORY,
                        "ICSC" => $value->ICSC,
                        "genericProduct" => $value->genericFlag,
                        "type" => $value->genericFlag == 0 ? "PRODUCT" : "BUNDLE",
                        "erplyEnabled" => $value->erplyEnabled,
                        "customItemName" => $value->customItemName,
                        "receiptDescription" => $value->receiptDescription,
                        "compareField" => $vcompare
                    );

                    if($vAassPending == 1){
                        $vdetails["assortmentPending"] = 1;
                    }

                    
                    $vdetails["erplyPending"] =  $varErplyPending;

                    //first update variation product
                    if($isVarExist == 1){
                        LiveProductVariation::where("id", $check->id)->update($vdetails);
                    }else{
                        LiveProductVariation::updateOrcreate(
                            [
                                "ERPLYSKU" => $value->ERPLYSKU
                            ],
                            $vdetails
                        );
                    }
                    

                    
                    //Now Update Matrix Product and variation pending 1 and matrix pending 1
                    $flag = LiveProductVariation::where('WEBSKU', $value->WEBSKU)->where('WebEnabled', '1')->first();
                    $isActive = 1;
                    if(!$flag){
                        $isActive = 0;
                    }
                    $flag2 = LiveProductVariation::where('WEBSKU', $value->WEBSKU)->where('erplyEnabled', 1)->first();
                    $erplyEnabled = 0;
                    if($flag2){
                        $erplyEnabled = 1;
                    }

                    $mAassPending = 0;
                    $mCheck = LiveProductMatrix::where("WEBSKU", $value->WEBSKU)->first();
                    $secondStore = LiveProductVariation::where("WEBSKU", $value->WEBSKU)->where("SecondaryStore",'<>','')->first();
                    $ss = '';
                    if($secondStore){
                        $ss = $secondStore->SecondaryStore;
                    }
                    $matrixErplyPending = 0;
                    $mCompare = $value->ERPLYFLAG."_".$value->SchoolID.'_'.trim($value->ItemName).'_'.$value->DefaultStore.'_'.$ss.'_'.$value->RetailSalesPrice.'_'
                    .$isActive.'_'.$value->genericFlag.'_'.$erplyEnabled.'_'.trim($value->customItemName).'_'.$value->ItemLastModified.'_'.$value->PriceLastModified;
                    $isMatExist = 0;
                    if($mCheck){
                        $isMatExist = 1;
                        $old = $mCheck->SecondaryStore ? $mCheck->SecondaryStore : '';
                        
                        $new = $value->DefaultStore."_".$ss;
                        // if($old == ''){
                        //     info($mCheck->WEBSKU." Empty Old Value");
                        // }
                        if($mCheck->DefaultStore."_".$old != $new){
                            $mAassPending = 1;
                            // info("******************************************************************************************************************** ".$old ." != " . $new);
                        }

                        $mCheckCompareField = LiveProductMatrix::where("WEBSKU", $value->WEBSKU)->where("compareField", $mCompare)->first();
                        if(!$mCheckCompareField){
                            $matrixErplyPending = 1;
                        }else{
                            $matrixErplyPending = $mCheck->erplyPending;
                        }
                    }
                    
                    $mdetails = array(  
                        "SchoolName" => trim($value->SchoolName) ,
                        "SchoolID" => $value->SchoolID,
                        "CustomerGroup" => $value->CustomerGroup,
                        // "ERPLYSKU" => $value->ERPLYSKU,
                        "WEBSKU" => $value->WEBSKU,
                        "ITEMID" => $value->ITEMID,
                        "ItemName" => trim($value->ItemName),
                        "ColourID" => $value->ColourID,
                        "ColourName" => trim($value->ColourName),
                        "SizeID" => $value->SizeID,
                        "CONFIGID" => $value->CONFIGID,
                        "ConfigName" => trim($value->ConfigName),
                        // "EANBarcode" => $value->EANBarcode,
                        "SOFTemplate" => $value->SOFTemplate,
                        "SOFName" => trim($value->SOFName),
                        "SOFOrder" => $value->SOFOrder,
                        "SOFStatus" => $value->SOFStatus,
                        "PLMStatus" => $value->PLMStatus,
                        "ProductType" => $value->ProductType,
                        "ProductSubType" => $value->ProductSubType,
                        "Supplier" => $value->Supplier,
                        "Gender" => $value->Gender,
                        "CategoryName" => $value->CategoryName,
                        "ItemWeightGrams" => $value->ItemWeightGrams,
                        "RetailSalesPrice" => $value->RetailSalesPrice ? $value->RetailSalesPrice : "0.00",
                        "RetailSalesPrice2" => $value->RetailSalesPrice2 ? $value->RetailSalesPrice2 : "0.00",
                        "RetailSalesPriceExclGST" => $value->RetailSalesPriceExclGST ? $value->RetailSalesPriceExclGST : "0.00",
                        "RetailSalesPriceExclGST2" => $value->RetailSalesPriceExclGST2 ? $value->RetailSalesPriceExclGST2 : "0.00",
                        "CostPrice" => $value->CostPrice ? $value->CostPrice : "0.00",
                        "DefaultStore" => $value->DefaultStore,
                        "SecondaryStore" => $ss,
                        "ERPLYFLAG" => $value->ERPLYFLAG,
                        "AvailableForPurchase" => $value->AvailableForPurchase,
                        "WebEnabled" => $isActive,
                        "SOFLastModified" => $value->SOFLastModified,
                        "ItemLastModified" => $value->ItemLastModified,
                        "SchoolLastModified" => $value->SchoolLastModified,
                        "PriceLastModified" => $value->PriceLastModified ? $value->PriceLastModified : "0000-00-00",
                        "Category_Name" => $value->Category_Name,
                        "PSWPRICELISTITEMCATEGORY" => $value->PSWPRICELISTITEMCATEGORY,
                        // "ICSC" => $value->ICSC,
                        "genericProduct" => $value->genericFlag,
                        "erplyEnabled" => $erplyEnabled,
                        "customItemName" => $value->customItemName,
                        "receiptDescription" => $value->receiptDescription,
                        "compareField" => $mCompare,
                        
                    );

                    if($mAassPending == 1){
                        $mdetails["assortmentPending"] = 1;
                    }

                    $mdetails["erplyPending"] =  $matrixErplyPending;
                    $matrixVarPending = 0;
                    if($varErplyPending == 1){
                        $matrixVarPending = 1;
                    }
                    if($matrixErplyPending == 1){
                        $matrixVarPending = 1;
                    }
                    if($matrixVarPending == 1){
                        $mdetails["variationPending"] = 1;    
                    }
                    // $mdetails["variationPending"] =  $matrixErplyPending == 1 ? 1 : varErplyPending;
                    if($isMatExist == 1){
                        LiveProductMatrix::where("id", $mCheck->id)->update($mdetails);
                    }else{
                        LiveProductMatrix::updateOrcreate(
                            [
                                "WEBSKU" => trim($value->WEBSKU) 
                            ],
                            $mdetails
                        );
                    }
                    

                     

                    $value->pendingProcess = 0;
                    $value->save();
                });
            }catch(Exception $e){
                info("An error occurred while Product Temp to Current System :  ". $e->getMessage()." SKU ". $value->ERPLYSKU);
                if($key < 3){
                    info($e);
                }
            }
 
        }

        info("Temp to Current System Product Dev. Synccing...");
        return response("Temp to Current System Product Dev. Synccing...");
    }

    public function syncTempToCurrentsystemProductGCSC(){

        $limit = 100;

        $datas = TempProduct::where("pendingProcess", 0)->where("GCSC", 1)->orderBy("ItemLastModified", 'asc')->limit($limit)->get();

        if($datas->isEmpty()){
            info("Product Temp to Current System : All Syncced.");
            return response("Product Temp to Current System : All Syncced.");
        }

        foreach($datas as $value){
            try{
                DB::transaction(function ()use($value) {
                      

                    //for group
                    $this->saveUpdateGroup($value->SchoolID,trim($value->SchoolName),$value->DefaultStore);

                    //For Colour
                    $this->saveUpdateColour($value->ColourName, $value->ColourID);
        

                    //Now product size
                    $this->saveUpdateSize($value->SizeID);
                    

                    //For Category
                    $this->saveUpdateCategory($value->ProductType);
  
                    $value->GCSC = 0;
                    $value->save();
                });
            }catch(Exception $e){
                info("An error occurred while Product Temp to Current System :  ". $e->getMessage()." SKU ". $value->ERPLYSKU);
            }
 
        }

        info("Temp to Current System Product Dev Group Colour Size Category. Synccing...");
        return response("Temp to Current System Product Dev. Synccing...");
    }

    //SYNCING AX TO MIDDLEWARE
    public function syncProductAxtoMiddlewareByLastModified(){

        info("********************************** AX To Synccare : PRODUCT By Last Modified Cron Called ******************************************");

        //first get last modifeid date by descending
        $latest = AxSyncDatetime::where("id", 1)->first();
        // LiveProductVariation::orderBy('ItemLastModified', 'desc')->first();
        // dd($latest);
        if(!$latest){
            return $this->successWithMessage("Product not found");
        }
 
        //now getting product from AX by last modification

        $datas = Product::where("Item Last Modified",">=", $latest->product_master)->orderBy("Item Last Modified", "asc")->limit(2000)->get();
        // dd($datas);
        // info($datas);
        if($datas->isEmpty()){
            info("AX to SYNCCARE : All Product Synced.");
            return $this->successWithMessage("AX to SYNCCARE All Product Synced.");
        }
        // dd($datas);
        foreach($datas as $value){

            //now update last modified date time if last
            
            //first check is this product exist
            $vAassPending = 0;
            $check = LiveProductVariation::where("ERPLYSKU", $value['ERPLY SKU'])->first();
            if($check){
                $old = $check->DefaultStore."_".$check->SecondaryStore;
                $new = $value['Default Store']."_".$value['Secondary Store'] ? $value['Secondary Store'] : '';
                if($old != $new){
                    $vAassPending = 1;
                }
            }
            $vdetails = array(
                "SchoolName" => trim($value['School Name']) ,
                "SchoolID" => $value['School ID'],
                "CustomerGroup" => $value['Customer Group'] ,
                "ERPLYSKU" => $value['ERPLY SKU'] ,
                "WEBSKU" => $value['WEB SKU'] ,
                "ITEMID" => $value['ITEMID'] ,
                "ItemName" => trim($value['Item Name']),
                "ColourID" => $value['ColourID'],
                "ColourName" => trim($value['Colour Name']),
                "SizeID" => $value['SizeID'] ,
                "CONFIGID" => $value['CONFIGID'] ,
                "ConfigName" => trim($value['Config Name']),
                "EANBarcode" => $value['EAN Barcode'] ,
                "SOFTemplate" => $value['SOF Template'] ,
                "SOFName" => trim($value['SOF Name']),
                "SOFOrder" => $value['SOF Order'] ,
                "SOFStatus" => $value['SOF Status'] ,
                "PLMStatus" => $value['PLM Status'] ,
                "ProductType" => $value['Product Type'] ,
                "ProductSubType" => $value['Product Sub Type'],
                "Supplier" => $value['Supplier']  ,
                "Gender" => $value['Gender']  ,
                "CategoryName" => $value['Category Name'] ,
                "ItemWeightGrams" => $value['Item Weight - grams'],
                "RetailSalesPrice" => $value['Retail Sales Price'] ? $value['Retail Sales Price'] : "0.00",
                "RetailSalesPriceExclGST" => $value['Retail Sales Price excl GST'] ? $value['Retail Sales Price excl GST'] : "0.00",
                "CostPrice" => $value['Cost Price'] ,
                "DefaultStore" => $value['Default Store'] ,
                "SecondaryStore" => $value['Secondary Store'] ,
                "ERPLYFLAG" => $value['ERPLY FLAG'] ,
                // "ERPLYFLAGModified" => $value['ERPLY FLAG Modified'],
                "AvailableForPurchase" => $value['Available for Purchase'],
                "WebEnabled" => $value['Web Enabled'] ,
                "SOFLastModified" => $value['SOF Last Modified'],
                "ItemLastModified" => $value['Item Last Modified'],
                "PSWPRICELISTITEMCATEGORY" => $value['PSW_PRICELISTITEMCATEGORY'] ,
                "Category_Name" => $value['CATEGORYNAME'],
                "erplyPending" => 1,
                // "assortmentPending" => 1,
                // "erplyPending" => 1,
                "ICSC" => $value['ICSC'],
                "genericProduct" => $value['Generic Flag'],
                "erplyEnabled" => $value['ERPLY_Enabled'],
                "customItemName" => $value['Custom Item Name'],
                "receiptDescription" => $value['ReceiptDescription'],
            );
            if($vAassPending == 1){
                $vdetails["assortmentPending"] = 1;
            }
            //first update variation product
            LiveProductVariation::updateOrcreate(
                [
                    "ERPLYSKU" => $value['ERPLY SKU']
                ],
                
            );

            
            //Now Update Matrix Product and variation pending 1 and matrix pending 1
            $flag = LiveProductVariation::where('WEBSKU', $value['WEB SKU'])->where('WebEnabled', '1')->first();
            $isActive = 1;
            if(!$flag){
                $isActive = 0;
            }
            $flag2 = LiveProductVariation::where('WEBSKU', $value['WEB SKU'])->where('erplyEnabled', 1)->first();
            $erplyEnabled = 0;
            if($flag2){
                $erplyEnabled = 1;
            }

            $mAassPending = 0;
            $mCheck = LiveProductMatrix::where("WEBSKU", $value['WEB SKU'])->first();
            $secondStore = LiveProductVariation::where("WEBSKU", $value['WEB SKU'])->where("SecondaryStore",'<>','')->first();
            $ss = '';
            if($mCheck){
                $old = $mCheck->SecondaryStore ? $mCheck->SecondaryStore : '';
                
                if($secondStore){
                    $ss = $secondStore->SecondaryStore;
                }
                $new = $value['Default Store']."_".$ss;
                // if($old == ''){
                //     info($mCheck->WEBSKU." Empty Old Value");
                // }
                if($mCheck->DefaultStore."_".$old != $new){
                    $mAassPending = 1;
                    info("******************************************************************************************************************** ".$old ." != " . $new);
                }
            }
            
            $mdetails = array(
                "SchoolName" => trim($value['School Name']) ,
                "SchoolID" => $value['School ID'],
                "CustomerGroup" => $value['Customer Group'] ,
                "ERPLYSKU" => "",
                "WEBSKU" => $value['WEB SKU'] ,
                "ITEMID" => $value['ITEMID'] ,
                "ItemName" => trim($value['Item Name']),
                "ColourID" => $value['ColourID'],
                "ColourName" => trim($value['Colour Name']),
                "SizeID" => $value['SizeID'] ,
                "CONFIGID" => $value['CONFIGID'] ,
                "ConfigName" => trim($value['Config Name']),
                // "EANBarcode" => $value['EAN Barcode'] ,
                "SOFTemplate" => $value['SOF Template'] ,
                "SOFName" => trim($value['SOF Name']),
                "SOFOrder" => $value['SOF Order'] ,
                "SOFStatus" => $value['SOF Status'] ,
                // "PLMStatus" => $value['PLM Status'] ,
                "ProductType" => $value['Product Type'] ,
                "ProductSubType" => $value['Product Sub Type'],
                "Supplier" => $value['Supplier'] ,
                "Gender" => $value['Gender'] ,
                "CategoryName" => $value['Category Name'] ,
                "ItemWeightGrams" => $value['Item Weight - grams'],
                "RetailSalesPrice" => $value['Retail Sales Price'] ? $value['Retail Sales Price'] : "0.00",
                "RetailSalesPriceExclGST" => $value['Retail Sales Price excl GST'] ? $value['Retail Sales Price excl GST'] : "0.00",
                "CostPrice" => $value['Cost Price'] ,
                "DefaultStore" => $value['Default Store'] ,
                "SecondaryStore" => $ss,
                "ERPLYFLAG" => $value['ERPLY FLAG'] ,
                // "ERPLYFLAGModified" => $value['ERPLY FLAG Modified'],
                "AvailableForPurchase" => $value['Available for Purchase'],
                "WebEnabled" => $isActive,
                "SOFLastModified" => $value['SOF Last Modified'],
                "ItemLastModified" => $value['Item Last Modified'],
                "PSWPRICELISTITEMCATEGORY" => $value['PSW_PRICELISTITEMCATEGORY'] ,
                "Category_Name" => $value['CATEGORYNAME'],
                "erplyPending" => 1, 
                // "assortmentPending" => 1,
                "variationPending" => 1, 
                "genericProduct" => $value['Generic Flag'],
                "erplyEnabled" => $erplyEnabled,
                "customItemName" => $value['Custom Item Name'],
                "receiptDescription" => $value['ReceiptDescription'],
                
            );

            if($mAassPending == 1){
                $mdetails["assortmentPending"] = 1;
            }

            LiveProductMatrix::updateOrcreate(
                [
                    "WEBSKU" => trim($value['WEB SKU']) 
                ],
                $mdetails
            );

            //now checking product group

            // if(!LiveProductGroup::where("SchoolID", trim($value['School ID']))->first()){
            //first check product group exist 
            $group = LiveProductGroup::where("SchoolID", trim($value['School ID']))->first();
            $groupPending = 1;
            if($group){
                $old = trim($group->SchoolName)."_";
                $new = trim($value['School Name'])."_";
                if($old != $new){
                    $groupPending = 1;    
                }else{
                    $groupPending = 0;    
                }
            } 

            LiveProductGroup::updateOrcreate(
                [
                    "SchoolID" => trim($value['School ID']), 
                ],
                [
                    "SchoolID" => $value['School ID'],
                    "SchoolName" => trim($value['School Name']), 
                    "WebEnabled" => $isActive,
                    "parentSchoolGroup" => $value["Default Store"],
                    "pendingProcess" => $groupPending
                ]
            );
            
            // }

            //Now checking product colour
            $checkColor = LiveProductColor::where('name', trim($value['Colour Name']))->first();
            // if(!$checkColor){
            // if($value['Colour Name'] != ''){
            //     LiveProductColor::create(["name" => trim($value['Colour Name']),"colourID" => $value['ColourID'], "pendingProcess" => $checkColor ? 0 : 1 ]);
            // }
            if($checkColor){
                if($value['Colour Name'] != ''){
                    LiveProductColor::where("id", $checkColor->id)->update(["name" => trim($value['Colour Name']),"colourID" => $value['ColourID'], "pendingProcess" => 0 ]);
                }
            }else{
                if($value['Colour Name'] != ''){
                    LiveProductColor::create(["name" => trim($value['Colour Name']),"colourID" => $value['ColourID'], "pendingProcess" => 1 ]);
                }
            }

            // }

            //Now checking product size
            $checkSize = LiveProductSize::where('name', trim($value['SizeID']))->first();
            if(!$checkSize){
                if(trim($value['SizeID']) != ''){
                    LiveProductSize::create(["name" => trim($value['SizeID']), "pendingProcess" => 1 ]);
                }
            }

            //For Category
            $checkCat = LiveProductCategory::where('name', trim($value['Product Type']))->first();
            if(!$checkCat){
                if(trim($value['Product Type'])){
                    LiveProductCategory::updateOrcreate(
                        [
                            'name' => trim($value['Product Type'])
                        ],
                        [
                            'name' => trim($value['Product Type']),
                            'pendingProcess' => 1
                        ]
                    );
                }
            }

            if($datas->last()){
                $info = $datas->last();
                AxSyncDatetime::where("id", 1)->update(["product_master" => $info["Item Last Modified"]]);
            }
 
        }


        return $this->successWithDataAndMessage("AX to Middleware Product Synced Successfully.",$datas);


    }

    //Updating ERPLY SKU ANND ICSC
    public function updateErplySkuIcsc(){
        $mps = $this->currentsystem_product_matrix_live->where("vUpdate", 1)->limit(100)->get();
        // dd($mps);
        foreach($mps as $m){

            //now first getting data from temp product dev
            $tps = $this->temp_product->where("WEBSKU", $m->WEBSKU)->get();

            foreach($tps as $tp){
                $this->currentsystem_product_variation_live->where("WEBSKU", $m->WEBSKU)
                    ->where("ColourID", $tp->ColourID)
                    ->where("ColourName", $tp->ColourName)
                    ->where("SizeID", $tp->SizeID)
                    ->update([
                        "ERPLYSKU" => $tp->ERPLYSKU,
                        "ICSC" => $tp->ICSC
                    ]);
            }

            //nwo updating matrix flag
            $m->vUpdate = 0;
            $m->save();

        }

        return response("ERPLYSKU Updated Successfully");
    }

    //For Product Size Sort
    public function syncPswLivetoMiddleware(){
        //PSW SQL Server
        $pswLiveSql = ProductSizeSortOrder::get();

        // dd($pswLiveSql);
        foreach($pswLiveSql as $sizeSort){

            //first getting size sort

            $sort = LiveProductSizeSortOrder::where("recid", $sizeSort["RECID"])->first();

            if($sort){

                //now checking compare fields
                if($sort->compareField != $sizeSort["SIZE_"]."_".$sizeSort["SORTORDER"]."_".$sizeSort["RECID"]){
                    LiveProductSizeSortOrder::updateOrcreate(
                        [
                            "size" => $sizeSort["SIZE_"]
                        ],
                        [
                            "size" => $sizeSort["SIZE_"],
                            "sort_order" => $sizeSort["SORTORDER"],
                            "dmx_sort_order" => $sizeSort["DMX_SORTORDER"],
                            "compareField" => $sizeSort["SIZE_"]."_".$sizeSort["SORTORDER"]."_".$sizeSort["RECID"],
                            "recid" => $sizeSort["RECID"]
                        ]
                    );
                    // info("Updated");
                }
                
            }else{
                LiveProductSizeSortOrder::updateOrcreate(
                    [
                        "size" => $sizeSort["SIZE_"]
                    ],
                    [
                        "size" => $sizeSort["SIZE_"],
                        "sort_order" => $sizeSort["SORTORDER"],
                        "dmx_sort_order" => $sizeSort["DMX_SORTORDER"],
                        "compareField" => $sizeSort["SIZE_"]."_".$sizeSort["SORTORDER"]."_".$sizeSort["RECID"],
                        "recid" => $sizeSort["RECID"]
                    ]
                );
            }
        }
        info("Product Size Sort Synced Successfully.");
        return response()->json(["status" => "success"]);
    }

    public function makeDescriptionFile(){
        $path = public_path('PswLiveTemp');

        File::delete($path . '/productDescription.txt');

        if (!File::exists($path)) { 
            File::makeDirectory($path); 
        }

        $des = ProductDescription::all();
        // dd($products);
        $chunkDes = $des->chunk(500);

        foreach ($chunkDes as $cpd) {

            $content = 'Insert into `temp_product_description`(`WEBSKU`,
                    `ITEMID`,
                    `LongDescription`,
                    `ModifiedDateTime`,
                    `pendingProcess`
                    ) VALUES ';
            
            $q = null;

            foreach ($cpd as $key => $value) {

                //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                $key = $cpd->last() == $value ? ';' : ',';

                $q .= '( "'. $value['WEB SKU'] . '",
                        "' . $value['ITEMID'] . '",
                        "' . $this->escapeFunc($value['Long Description']) . '",
                        "' . $value['Description ModifiedDateTime'] . '",
                        "1")' . $key;

            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/productDescription.txt', $content);

        }

        return $this->successWithMessage("Product Description File Generated Successfully.");

    }

    public function readProductDescriptionAndStore(){
        $path = public_path('PswLiveTemp/productDescription.txt');

        if (File::exists($path)) {  
            // $tempProDes = TempProductDescription::where('pendingProcess', 0)->count(); 
            // TempProductDescription::turncate();
            
            $file = File::get($path);
         
            $sqls = explode(";\n", $file); 
            
            foreach ($sqls as $sql) { 
                if ($sql != '') { 
                    DB::connection('mysql2')->select($sql); 
                } 
            }   

            return $this->successWithMessage("Product Description File Executed Successfully.");
            
        } else{
            echo "no file";
            die;
        }
    }

    public function syncDescriptionNewsystem(){
        $des = TempProductDescription::where('pendingProcess', 1)->limit(500)->get();

        foreach($des as $d){
            LiveProductDescription::updateOrcreate(
                [
                    "WEBSKU" => $d->WEBSKU,
                ],
                [
                    "WEBSKU" => $d->WEBSKU,
                    "ITEMID" => $d->ITEMID,
                    "LongDescription" => trim($d->LongDescription),
                    "ModifiedDateTime" => $d->ModifiedDateTime,
                ]
            );
            $d->pendingProcess = 0;
            $d->save();
        }

        return $this->successWithMessage("Product Description Sync to Newsystem Successfully.");

    }

    //SYNCING PRODUCT DESCRIPTION BY LAST MODIFIED DATE
    public function syncProductDescriptionByLastModified(){
        
        //getting lastest modified datetime
        $latest = LiveProductDescription::orderBy("ModifiedDateTime", "desc")->first();
        // dd($latest);

        $datas = ProductDescription::where("Description ModifiedDateTime",">", $latest->ModifiedDateTime)->limit(50)->get();

        // dd($datas);
        if($datas->isEmpty()){
            info("AX to SYNCCARE : All Product Description Up-to-date");
            return $this->successWithMessage("AX to SYNCCARE : All Product Description Up-to-date");
        }

        foreach($datas as $data){

            $details = array(
                "WEBSKU" => $data["WEB SKU"],
                "ITEMID" => $data["ITEMID"], 
                "LongDescription" => trim($data["Long Description"]), 
                "ModifiedDateTime" => $data["Description ModifiedDateTime"] 
            );
            LiveProductDescription::updateOrcreate(
                [
                    "WEBSKU" => $data["WEB SKU"]
                ],
                $details
            );

            //now updating matrix and variation erply pending 1
            $matrix = LiveProductMatrix::where("WEBSKU", trim($data["WEB SKU"]))->first();
            if($matrix){
                LiveProductMatrix::where("WEBSKU", trim($data["WEB SKU"]))->update(["erplyPending" => 1, "variationPending" => 1]);
            }

            $matrix = LiveProductVariation::where("WEBSKU", trim($data["WEB SKU"]))->first();
            if($matrix){
                LiveProductVariation::where("WEBSKU", trim($data["WEB SKU"]))->update(["erplyPending" => 1]);
            }

        }

        return $this->successWithDataAndMessage("AX to SYNCCARE : Product Description Updated", $datas);

    }


    //Product Item Location Relation by ICSC 
    public function makeItemLocationFile(){
        ini_set('memory_limit', -1);
        $limit = 35000;
        $path = public_path('PswLiveTemp');

        File::delete($path . '/itemLocations.txt');

        if (!File::exists($path)) { 
            File::makeDirectory($path); 
        }

        $this->setInfo("AX to Synccare : Item Location by LastModified API Called ", 0, "********************************************", "********************************************");
        // dd("hello");
        $lastModified = AxSyncDatetime::where("id", 1)->first()->item_location;
        // echo $lastModified;
        // die;
        if('0000-00-00 00:00' == $lastModified || '0000-00-00' == $lastModified || is_null($lastModified) == true || $lastModified == '0000-00-00 00:00:00.000'){
            $items = ItemLocation::whereNotNull("ERPLY SKU")->orderBy("ModifiedInventItem", "asc")->limit($limit)->get();
        }else{
            // $items = ItemLocation::whereNotNull("ERPLY SKU")->where("ModifiedInventItem" , ">=", $lastModified)->orderBy("ModifiedInventItem", "asc")->limit($limit)->get();
            $datas = DB::connection("sqlsrv_psw_live")->select("select top $limit * from ERPLY_ItemLocations where [ERPLY SKU] IS NOT NULL and [ModifiedInventItem] >= '$lastModified' ORDER BY [ModifiedInventItem] ASC");
        }

        if(count($datas) < 1){
            info("All Item Location Syncced.");
            $checkTruncate = TempItemLocation::where("pendingProcess", 1)->count();
            if($checkTruncate < 1){
                
                TempItemLocation::truncate();
                info("Item Location All Data Processed and Table Truncating...");
            }
            return response("All Item Location Syncced.");
        }

        $this->setInfo( "AX to Synccare : Item Location by LastModified ". count($datas). " Item Fetched ", 0, "********************************************", "********************************************");
        
        $datas = collect($datas);
         
        $chunkDes = $datas->chunk(500); 
        foreach ($chunkDes as $cpd) { 
            // $q = null;
            $values = [];
            foreach ($cpd as $key => $value) {
                 
                $values[] = sprintf(
                    '("%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s")',
                    $this->escapeFunc($value->{'Item SKU'}),
                    $this->escapeFunc($value->{'ERPLY SKU'}),
                    $this->escapeFunc($value->Item),
                    $this->escapeFunc($value->Configuration),
                    $this->escapeFunc($value->Size),
                    $this->escapeFunc($value->Colour),
                    $value->Warehouse,
                    $this->escapeFunc($value->{'Picking Location'}),
                    $this->escapeFunc($value->{'Issue Location'}),
                    $this->escapeFunc($value->{'Receipt Location'}),
                    $this->makeNullDate($value->ModifiedInventDim),
                    $this->makeNullDate($value->ModifiedInventItem),
                    $this->escapeFunc($value->ICSC)
                    
                );
            } 

            $valuesString = implode(',', $values);
            // $content = 'Insert into `temp_item_locations`(`itemSKU`,`ERPLYSKU`,`item`,`configuration`,`size`,`colour`,`warehouse`,`pickingLocation`,`issueLocation`,`receiptLocation`,`modifiedInventDim`,`modifiedInventItem`,`ICSC`) VALUES ';
            $content = "INSERT INTO `temp_item_locations`(`itemSKU`,`ERPLYSKU`,`item`,`configuration`,`size`,`colour`,`warehouse`,`pickingLocation`,`issueLocation`,`receiptLocation`,`modifiedInventDim`,`modifiedInventItem`,`ICSC`) VALUES $valuesString;\n";
             
            File::append($path . '/itemLocations.txt', $content);
                
        }

        $this->setInfo( "AX to Synccare : Item Location by LastModified : File Generated Successfully.", 0, "********************************************", "********************************************");
        
        $sqlFile = $path . '/itemLocations.txt';

        $checkTruncate = TempItemLocation::where("pendingProcess", 1)->count();
        if($checkTruncate < 1){   
            TempItemLocation::truncate();
            info("Item Location All Data Processed and Table Truncating...");
        }

        DB::connection('mysql2')->unprepared(file_get_contents($sqlFile));

        $latest = TempItemLocation::whereNull("created_at")->orderBy("ModifiedInventItem", "desc")->first(); 
             
        if ($latest) {
            AxSyncDatetime::where("id", 1)->update(["item_location" => $latest->ModifiedInventItem]);
        }

        return $this->successWithMessage("Item Location File Executed Successfully.");

        // return $this->readItemLocationFile();
            // return $this->successWithMessage("Item Locations File Generated Successfully.");
    }
        
    public function readItemLocationFile(){
            
            $this->setInfo( "AX to Synccare : Reading Item Location File...", 0, "********************************************", "********************************************");
            
        //before inserting new datas first check if all processed in temp table then clean all tables
        $chkTemp = TempItemLocation::where("pendingProcess", 1)->first();
        if(!$chkTemp){
            TempItemLocation::truncate();
        }


        $path = public_path('PswLiveTemp/itemLocations.txt');

        if (File::exists($path)) {  
            // $tempProDes = TempProductDescription::where('pendingProcess', 0)->count(); 
            // TempProductDescription::turncate();
            
            $file = File::get($path);
             
            $sqls = explode(";\n", $file); 
            // dd($sqls);
            foreach ($sqls as $sql) { 
                // echo $sql;
                // die;
                if ($sql != '') { 
                    DB::connection('mysql2')->select($sql); 
                } 
               
            }
            
            $this->setInfo( "AX to Synccare : Item Location File Read Successfully.", 0, "********************************************", "********************************************");
            
            //now getting latest modidfied datetime and update to ax resync table
            $latest = TempItemLocation::orderBy("ModifiedInventItem", "desc")->first(); 
             
            if ($latest) {
                AxSyncDatetime::where("id", 1)->update(["item_location" => $latest->ModifiedInventItem]);
            }

            return $this->successWithMessage("Item Location File Executed Successfully.");
            
        } else{
            echo "no file";
            die;
        }
    }

    public function syncItemLocationNewsystem(){
        
        // DB::disableQueryLog();
        $datas = TempItemLocation::where('pendingProcess', 1)->orderBy("ModifiedInventItem", "asc")->limit(500)->get();

        if($datas->isEmpty()){
            info("All Assortment Syncced to Current System");
            return response("All Assortment Syncced to Current System.");
        }

        $bulk = array();
        foreach($datas as $data){

            $payload = array(
                    "itemSKU" => $data["itemSKU"],
                    "item" => $data["item"],
                    "ERPLYSKU" => $data["ERPLYSKU"],
                    "configuration" => $data["configuration"],
                    "size" => $data["size"],
                    "colour" => $data["colour"],
                    "warehouse" => $data["warehouse"],
                    "pickingLocation" => $data["pickingLocation"],
                    "issueLocation" => $data["issueLocation"],
                    "receiptLocation" => $data["receiptLocation"],
                    "modifiedInventDim" => $data["modifiedInventDim"],
                    "ModifiedInventItem" => $data["ModifiedInventItem"],
                    "ICSC" => $data["ICSC"]
            );

            //first check and update 
            LiveItemLocation::updateOrcreate(
                [
                    "itemSKU" => $data["itemSKU"], 
                    "ERPLYSKU" => $data["ERPLYSKU"],   
                    "warehouse" => $data["warehouse"],
                ],

                $payload

            );
            
            $data->pendingProcess = 0;
            $data->save();
        }
        // LiveItemLocation::insert($bulk);
        // foreach($datas as $data){
        //     $data->pendingProcess = 0;
        //     $data->save();
        // }

        return response("Item Location Synced.");
    }

    //Syncing Item Location by Last Modified Date and Time

    public function syncItemLocationByModifiedDateAndTime(){
        $latest = LiveItemLocation::orderBy("ModifiedInventItem", "desc")->first();
        // dd($latest);
        
        $datas = ItemLocation::where("ModifiedInventItem", ">", $latest->ModifiedInventItem)->where("ERPLY SKU", '<>', '')->orderBy("ModifiedInventItem",'asc')->limit(50)->get();
         
        // dd($datas);
        if($datas->isEmpty()){
            info("AX TO SYNCCARE : All Items Locations Up-to-date");
            return $this->successWithMessage("AX TO SYNCCARE : All Items Locations Up-to-date");
        }
        foreach($datas as $data){
            LiveItemLocation::updateOrcreate(
                [
                    "itemSKU" => $data["Item SKU"],
                    "warehouse" => $data["Warehouse"],
                    "ERPLYSKU" => $data["ERPLYSKU"],
                ],
                [
                    "itemSKU" => $data["Item SKU"],
                    "ERPLYSKU" => $data["ERPLYSKU"],
                    "item" => $data["Item"],
                    "configuration" => $data["configuration"],
                    "size" => $data["Size"],
                    "colour" => $data["Colour"],
                    "warehouse" => $data["Warehouse"],
                    "pickingLocation" => $data["Picking Location"],
                    "issueLocation" => $data["Issue Location"],
                    "receiptLocation" => $data["Receipt Location"],
                    "modifiedInventDim" => $data["ModifiedInventDim"],
                    "ModifiedInventItem" => $data["ModifiedInventItem"],
                    "aPending" => 1,
                    "ICSC" => $data["ICSC"]
                ]
            );
        }

        return $this->successWithDataAndMessage("Items Locations Updated Successfully", $datas);

    }


    //make file and read to temp for item by location and icsc

    public function makeItemByLocationFile($req){
        return $this->makeItemByLocationFileV2($req);
        ini_set('memory_limit', -1);
        info("**************************************SOH by Last Modified date : Ax to Synccare Cron Called.***************************************");
        $limit = 25000;

        $path = public_path('PswLiveTemp');

        File::delete($path . '/itemByLocations.txt');

        if (!File::exists($path)) { 
            File::makeDirectory($path); 
        }
        $lastModified = AxSyncDatetime::where("id", 1)->first()->soh_by_location;
        // echo $lastModified;
        // die;
        if('0000-00-00 00:00' == $lastModified || '0000-00-00' == $lastModified || is_null($lastModified) == true || $lastModified == '0000-00-00 00:00:00.000'){
            $datas = ItemByLocation::all();
        }else{

            // $datas = ItemByLocation::where("Modified DateTime", '>=', $lastModified)->limit($limit)->get();
            $datas = DB::connection("sqlsrv_psw_live")->select("select top $limit * from ERPLY_ItemsByLocation where [Modified DateTime] >= '$lastModified' ORDER BY [Modified DateTime] ASC");
        }

        info("******************".count($datas)." SOH read from AX...*********************");

        if(count($datas) < 1){
            info("All Item By Location Syncced.");
            $checkTruncate = TempItemByLocation::where("pendingProcess", 1)->count();
            if($checkTruncate < 1){
                
                TempItemByLocation::truncate();
                info("Item By Location All Data Processed and Table Truncating...");
            }
            return response("All Item By Location Syncced.");
        }

        // echo count($datas);
        // die;
        // $datas = ItemByLocation::all();
        // dd($items);
        // dd($products);
        $datas = collect($datas);
        $chunkDatas = $datas->chunk(500);
        // $chunkDatas = array_chunk($datas, 500);

        // foreach ($chunkDatas as $data) {

        //     $content = 'Insert into `temp_item_by_locations`(`ICSC`,`Item`,`Configuration`,`Colour`,`Size`,`Warehouse`,`PhysicalInventory`,`PhysicalReserved`,`AvailablePhysical`,`OrderedInTotal`,`OnOrder`,`ModifiedDateTime`) VALUES ';
            
        //     $q = null;

        //     foreach ($data as $key => $value) { 
        //         $sep = ',';
        //         // $data->last() == $value ? ';' : ',';
        //         if(last($data) === $value){
        //             $sep = ';';
        //         }
        //         if($chunkDatas->last() === $data) {
        //             if(last($data) === $value){
        //                 $sep = '';
        //             }
        //         }

        //         $q .= '( "'. $value['ICSC'] . '","' . $value['Item'] . '","' . $value['Configuration'] . '","' . $value['Colour'] . '","' . $value['Size'] . '","' . $value['Warehouse'] . '","' . $value['Physical Inventory'] . '","' . $value['Physical Reserved'] . '","' . $value['Available Physical'] . '","' . $value['Ordered in Total'] . '","' . $value['On Order'] . '","' . $value['Modified DateTime'] . '")' . $sep;

        //     }

        //     $content = $content . '' . $q . '' . "\n";

        //     File::append($path . '/itemByLocations.txt', $content);

        // }

        //v2 code 
        foreach ($chunkDatas as $index => $data) {
            $values = [];
        
            foreach ($data as $value) {
                $values[] = sprintf(
                    '("%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s")',
                    $value->ICSC,
                    $value->Item,
                    $value->Configuration,
                    $value->Colour,
                    $value->Size,
                    $value->Warehouse,
                    $value->{'Physical Inventory'},
                    $value->{'Physical Reserved'},
                    $value->{'Available Physical'},
                    $value->{'Ordered in Total'},
                    $value->{'On Order'},
                    $value->{'Modified DateTime'}
                );
            }
        
            $valuesString = implode(',', $values);
        
            $content = "INSERT INTO `temp_item_by_locations`(`ICSC`,`Item`,`Configuration`,`Colour`,`Size`,`Warehouse`,`PhysicalInventory`,`PhysicalReserved`,`AvailablePhysical`,`OrderedInTotal`,`OnOrder`,`ModifiedDateTime`) VALUES $valuesString;\n";
        
            File::append($path . '/itemByLocations.txt', $content);
        }

        $sqlFile = $path . '/itemByLocations.txt';

        $checkTruncate = TempItemByLocation::where("pendingProcess", 1)->count();
        if($checkTruncate < 1){   
            TempItemByLocation::truncate();
            info("Item By Location All Data Processed and Table Truncating...");
        }

        DB::connection('mysql2')->unprepared(file_get_contents($sqlFile));

        $lastSyncDatetime = TempItemByLocation::whereNull("created_at")->orderBy("ModifiedDateTime", 'desc')->first()->ModifiedDateTime;
        AxSyncDatetime::where("id", 1)->update([ "soh_by_location" => $lastSyncDatetime ]);
        info("*************************************Item by Locations File Executed Successfully.*****************************************");
        return $this->successWithMessage("Item by Locations File Executed Successfully.");

        // die(" Done");
        // info("Item By Location File Generated Successfully.");
        // info("Now Adding Txt Data to Temp Table of Item By Location File.");
        // return $this->readItemByLocationFile();
        // return $this->successWithMessage("Item By Locations File Generated Successfully.");
    }

    public function makeItemByLocationFileV2($req)
    {
        $debug = $req->debug  ?? 0;

        ini_set('memory_limit', -1);
        info("**************************************SOH by Last Modified date : Ax to Synccare Cron Called.***************************************");
        $limit = 25000;

        $path = public_path('PswLiveTemp');

        File::delete($path . '/itemByLocations.txt');

        if (!File::exists($path)) { 
            File::makeDirectory($path); 
        }
        $lastModifiedStart = AxSyncDatetime::where("id", 1)->first()->soh_by_location;
        $endDate = date("Y-m-d H:i:s");
        $datas = DB::connection("sqlsrv_psw_live")->select("select top $limit * from [AX2009_DEV].dbo.ERPLY_GetInventoryDataFiltered('$lastModifiedStart', '$endDate')");
        
        if($debug == 1){
            dd($datas, $lastModifiedStart , $endDate );
        }
        info("******************".count($datas)." SOH read from AX...*********************");

        if(count($datas) < 1){
            info("All Item By Location Syncced.");
            $checkTruncate = TempItemByLocation::where("pendingProcess", 1)->count();
            if($checkTruncate < 1){
                
                TempItemByLocation::truncate();
                info("Item By Location All Data Processed and Table Truncating...");
            }
            return response("All Item By Location Syncced.");
        } 
        $datas = collect($datas);
        $chunkDatas = $datas->chunk(500);
        

        //v2 code 
        foreach ($chunkDatas as $index => $data) {
            $values = [];
        
            foreach ($data as $value) {
                $values[] = sprintf(
                    '("%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s")',
                    $value->ICSC,
                    $value->Item,
                    $value->Configuration,
                    $value->Colour,
                    $value->Size,
                    $value->Warehouse,
                    $value->{'Physical Inventory'},
                    $value->{'Physical Reserved'},
                    $value->{'Available Physical'},
                    $value->{'Ordered in Total'},
                    $value->{'On Order'},
                    $value->{'Modified DateTime'}
                );
            }
        
            $valuesString = implode(',', $values);
        
            $content = "INSERT INTO `temp_item_by_locations`(`ICSC`,`Item`,`Configuration`,`Colour`,`Size`,`Warehouse`,`PhysicalInventory`,`PhysicalReserved`,`AvailablePhysical`,`OrderedInTotal`,`OnOrder`,`ModifiedDateTime`) VALUES $valuesString;\n";
        
            File::append($path . '/itemByLocations.txt', $content);
        }

        $sqlFile = $path . '/itemByLocations.txt';

        $checkTruncate = TempItemByLocation::where("pendingProcess", 1)->count();
        if($checkTruncate < 1){   
            TempItemByLocation::truncate();
            info("Item By Location All Data Processed and Table Truncating...");
        }

        DB::connection('mysql2')->unprepared(file_get_contents($sqlFile));

        $lastSyncDatetime = TempItemByLocation::whereNull("created_at")->orderBy("ModifiedDateTime", 'desc')->first()->ModifiedDateTime;
        AxSyncDatetime::where("id", 1)->update([ "soh_by_location" => $lastSyncDatetime ]);
        info("*************************************Item by Locations File Executed Successfully.*****************************************");
        return $this->successWithMessage("Item by Locations File Executed Successfully.");

        
    }

    public function readItemByLocationFile(){
        info("**********************************Read Txt File for SOH Function Called************************");
        $path = public_path('PswLiveTemp/itemByLocations.txt');

        //checking if all data processed then truncating table
        $checkTruncate = TempItemByLocation::where("pendingProcess", 1)->count();
        if($checkTruncate < 1){
            
            TempItemByLocation::truncate();
            info("Item By Location All Data Processed and Table Truncating...");
        }

        if (File::exists($path)) {

            // $count = TempItemByLocation::where('pendingProcess', 0)->count();

            // if ($count < 1) {
                // TempItemByLocation::truncate();
                $file = File::get($path);

                $sqls = explode(";\n", $file);

                foreach ($sqls as $sql) {

                    if ($sql != '') {
                        DB::connection('mysql2')->select($sql);
                    }
                }

                /**
                 * NOW UPDATING LAST SYNC DATETIME
                 */
                $lastSyncDatetime = TempItemByLocation::orderBy("ModifiedDateTime", 'desc')->first()->ModifiedDateTime;
                AxSyncDatetime::where("id", 1)->update([ "soh_by_location" => $lastSyncDatetime ]);
                info("*************************************Item by Locations File Executed Successfully.*****************************************");
                return $this->successWithMessage("Item by Locations File Executed Successfully.");
            // }
        } else {
            echo "no data";
            die;
        }
    }

    public function syncItemByLocationtoNewsystem(){

        /**
         * 
         * SYNC TO CURRENT SYSTEM OR NEWSYSTEM
         * REMOVE ALL PENDING PROCESS 0 DATA IF EXIST 
         * 
         */
        // TempItemByLocation::where("pendingProcess", 0)->delete();   //uncomment if required

        $datas = TempItemByLocation::where("pendingProcess", 1)->orderBy("ModifiedDateTime", 'asc')->limit(500)->get();
        if($datas->isEmpty()){
            info("All Item By Location Syncced to Newsystem");
            return response("All Item By Location Syncced to Newsystem");
        }
        $bulk = array();
        foreach($datas as $data){
            $details = array(
                "ICSC" => $data->ICSC,
                "Item" => $data->Item,
                "Configuration" => $data->Configuration,
                "Colour" => $data->Colour,
                "Size" => $data->Size,
                "Warehouse" => $data->Warehouse,
                "PhysicalInventory" => $data->PhysicalInventory,
                "PhysicalReserved" => $data->PhysicalReserved,
                "AvailablePhysical" => $data->AvailablePhysical,
                "OrderedInTotal" => $data->OrderedInTotal,
                "OnOrder" => $data->OnOrder,
                "ModifiedDateTime" => $data->ModifiedDateTime,
                "sohPending" => 1,
                "pswSohPending" => 1
	        );

            $checkSohExist = LiveItemByLocation::where("ICSC", $data->ICSC)->where("Warehouse", $data->Warehouse)->first();
            if($checkSohExist){
                //now data exist in current system table
                //now checking compare field ICSC + WAREHOUSE + AVAILABLE PHYSICAL
                //IF SAME NO NEED TO UPDATE
                //IF NOT UPDATE
                $old = $checkSohExist->ICSC.'_'.$checkSohExist->Warehouse.'_'.$checkSohExist->AvailablePhysical;
                $new = $data->ICSC.'_'.$data->Warehouse.'_'.$data->AvailablePhysical;
                if("$old" != "$new"){
                    //if not equal then update
                    // LiveItemByLocation::updateOrcreate(
                    //     [
                    //         "ICSC" => $data->ICSC,
                    //         "Warehouse" => $data->Warehouse		
                    //     ],
                    //     $details
                    // );
                    LiveItemByLocation::where("id", $checkSohExist->id)->update($details);
                }
            }else{
                LiveItemByLocation::updateOrcreate(
                    [
                        "ICSC" => $data->ICSC,
                        "Warehouse" => $data->Warehouse		
                    ],
                    $details
                );
            }

            //$bulk[] = $details;
            
	        $data->pendingProcess = 0;
            $data->save();

        }
       
        info("Item By Location Synccing to Newsystem...");
        return response("Item By Location Synccing...");
        // return $this->successWithMessage("Item By Locations Synced to Newsystem Table Successfully.");
    }

    public function syncItemByLocationtoByLastModified(){

        $latest = LiveItemByLocation::orderBy("ModifiedDateTime", "desc")->first();
        // dd($latest);
        $datas = ItemByLocation::where("Modified DateTime",'>', $latest->ModifiedDateTime)->orderBy("Modified DateTime","asc")->limit(100)->get();
        // dd($datas);
        if($datas->isEmpty()){
            info("Ax to Synccare : All Items By Locations Synced");
            return $this->successWithMessage("All Items By Locations Synced");
        }
        foreach($datas as $data){
            $details = array(
                "ICSC" => $data["ICSC"],
                "Item" => $data["Item"],
                "Configuration" => $data["Configuration"],
                "Colour" => $data["Colour"],
                "Size" => $data["Size"],
                "Warehouse" => $data["Warehouse"],
                "PhysicalInventory" => $data["Physical Inventory"],
                "PhysicalReserved" => $data["Physical Reserved"],
                "AvailablePhysical" => $data["Available Physical"],
                "OrderedInTotal" => $data["Ordered in Total"],
                "OnOrder" => $data["On Order"],
                "ModifiedDateTime" => $data["Modified DateTime"],
                "sohPending" => 1
            );

            LiveItemByLocation::updateOrcreate(
                [
                    "ICSC" => $data["ICSC"],
                    "Warehouse" => $data["Warehouse"],
                ],
                $details
            );

            LiveWarehouseLocation::where("LocationID", $data["Warehouse"])->update(["sohPending" => 1]);

            // $data->pendingProcess = 0;
            // $data->save();
        }
        info("Item By Location Synced to Synccare Successfully");
        return response()->json($datas);
        return $this->successWithMessage("Item By Locations Synced to Newsystem Table Successfully.");
    }

    public function makeItemByICSC(){
        $path = public_path('PswLiveTemp/Live');

        File::delete($path . '/itemByICSC.txt');

        if (!File::exists($path)) { 
            File::makeDirectory($path); 
        }

        $datas = ItemByICSC::get();
        // dd($items);
        // dd($products);
        $chunkDatas = $datas->chunk(500);

        foreach ($chunkDatas as $data) {

            $content = 'Insert into `temp_item_by_icsc`(`ICSC`,`Item`,`Configuration`,`Colour`,`Size`,`PhysicalInventory`,`PhysicalReserved`,`AvailablePhysical`,`OrderedInTotal`,`OnOrder`,`ModifiedDateTime`) VALUES ';
            
            $q = '';
            $count = 1;
            foreach ($data as $key => $value) {

                //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                $sep = $data->last() == $value ? ';' : ',';
                if($chunkDatas->last() == $data){
                    if($data->last() == $value){
                        $sep = '';
                    }
                }
                $q .= '( "'. $value['ICSC'] . '","' . $value['Item'] . '","' . $value['Configuration'] . '","' . $value['Colour'] . '","' . $value['Size'] . '","' . $value['Physical Inventory'] . '","' . $value['Physical Reserved'] . '","' . $value['Available Physical'] . '","' . $value['Ordered in Total'] . '","' . $value['On Order'] . '","' . $value['Modified DateTime'] . '")' . $sep;
                $count++;
            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/itemByICSC.txt', $content);

        }

        return $this->successWithMessage("Item By ICSC File Generated Successfully.");
    }

    public function readItemByICSC(){
        $path = public_path('PswLiveTemp/Live/itemByICSC.txt');

        if (File::exists($path)) {

            $count = TempItemByICSC::where('pendingProcess', 0)->count();

            if ($count < 1) {
                TempItemByICSC::truncate();
                $file = File::get($path);

                $sqls = explode(";\n", $file);

                foreach ($sqls as $sql) {

                    if ($sql != '') {
                        DB::connection('mysql2')->select($sql);
                    }
                }

                return $this->successWithMessage("Item by ICSC File Executed Successfully.");
            }
        } else {
            echo "no data";
            die;
        }
    }

    public function syncItemByIcscToNewsystem(){

        $datas = TempItemByICSC::where("pendingProcess", 1)->limit(150)->get();

        $bulk = array();
        foreach($datas as $data){
            $details = array(
                "ICSC" => $data->ICSC,
                "Item" => $data->Item,
                "Configuration" => $data->Configuration,
                "Colour" => $data->Colour,
                "Size" => $data->Size,
                "PhysicalInventory" => $data->PhysicalInventory,
                "PhysicalReserved" => $data->PhysicalReserved,
                "AvailablePhysical" => $data->AvailablePhysical,
                "OrderedInTotal" => $data->OrderedInTotal,
                "OnOrder" => $data->OnOrder,
                "ModifiedDateTime" => $data->ModifiedDateTime
            );
            $bulk[] = $details;
            
        }

        LiveItemByICSC::insert($bulk);
        foreach($datas as $data){
            $data->pendingProcess = 0;
            $data->save();
        }
        return $this->successWithMessage("Item By ICSC Synced to Newsystem Table Successfully.");
    }

    //Stock On Hand Inventory

    public function makeOnHandInventoryBySKU(){
        // $path = public_path('PswLiveTemp');

        // File::delete($path . '/onHandInventory.txt');

        // if (!File::exists($path)) { 
        //     File::makeDirectory($path); 
        // }
        $products = TempProduct::where("synccareSOHPending", 1)->limit(30)->get();//->toArray();
        $sku = [];
        foreach($products as $p){
            $sku[] = $p->ERPLYSKU;
        }
        // dd($sku);
        $datas = OnHandInventory::whereIn("ERPLY SKU", $sku)->get();
        
        $bulkData = array();

        foreach($datas as $data){
            
            $param = array(
                "Item" => $data['Item'],	
                "Configuration" => $data['Configuration'],
                "Size" => $data['Size'],
                "Colour" => $data['Colour'],
                "Warehouse" => $data['Warehouse'],
                "Location" => $data['Location'],
                "PhysicalInventory" => $data['Physical Inventory'],
                "PhysicalReserved" => $data['Physical Reserved'],
                "AvailablePhysical" => $data['Available Physical'],
                "OrderInTotal" => $data['Order In Total'],
                "OnOrder" => $data['On Order'],
                "ModifiedDateTime" => $data['Modified DateTime'],
                "ERPLYSKU" => $data['ERPLY SKU'],
            );

            $bulkData[] = $param;
        }

        // dd($bulkData);
        if(count($bulkData) > 0){
            TempOnHandInventory::insert($bulkData);
        }
        foreach($products as $p){
            $p->synccareSOHPending = 0;
            $p->save();
        }

        return $this->successWithMessage("On Hand Inventory Synced Successfully.");
    }

    public function makeOnHandInventoryFile($req){
        ini_set('memory_limit', -1);
        info("**************************************** AX To Synccare : On Hand Inventory By Last Modified Cron Called ********************************");
        $limit = $req->limit ? $req->limit : 5000;

        $path = public_path('PswLiveTemp');

        File::delete($path . '/onHandInventory.txt');

        if (!File::exists($path)) { 
            File::makeDirectory($path); 
        }

        $lastModified = AxSyncDatetime::where("id", 1)->first()->on_hand_inventory;
        // echo $lastModified;
        // die;
        if('0000-00-00 00:00' == $lastModified || '0000-00-00' == $lastModified || is_null($lastModified) == true || $lastModified == '0000-00-00 00:00:00.000'){
            $datas = OnHandInventory::where("ERPLY SKU", '<>', '')->orderBy("Modified DateTime", 'asc')->limit($limit)->get();
        }else{
            $datas = OnHandInventory::where("ERPLY SKU", '<>', '')->where("Modified DateTime", '>=', $lastModified)->orderBy("Modified DateTime", 'asc')->limit($limit)->get();
        }

        info("**************************************** AX To Synccare : ".count($datas)." On Hand Inventory Data Fetched. ********************************");

        // $datas = OnHandInventory::where("ERPLY SKU", '<>', '')->get();
        // dd($items);
        // dd($products);
        $chunkDatas = $datas->chunk(500);

        foreach ($chunkDatas as $data) {

            $content = 'Insert into `temp_on_hand_inventory`(`ERPLYSKU`,`Item`,`Configuration`,`Colour`,`Size`,`Warehouse`,`Location`,`PhysicalInventory`,`PhysicalReserved`,`AvailablePhysical`,`OrderInTotal`,`OnOrder`,`ModifiedDateTime`) VALUES ';
            
            $q = null;
            $flag = 0;
            foreach ($data as $key => $value) {
                $flag = $flag + 1;
                //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                $sep = ',';
                //  $data->last() == $value ? ';' : ',';
                if($data->last() === $value){
                    $sep = ';';
                }
                // $sep = $flag == 500 ? ';' : ',';
                if($chunkDatas->last() === $data) {
                    if($data->last() === $value){
                        $sep = '';
                    }
                }
                $q .= '( "'. $value['ERPLY SKU'] . '","' . $value['Item'] . '","' . $value['Configuration'] . '","' . $value['Colour'] . '","' . $value['Size'] . '","' . $value['Warehouse'] . '","' . $value['Location'] . '","' . $value['Physical Inventory'] . '","' . $value['Physical Reserved'] . '","' . $value['Available Physical'] . '","' . $value['Ordered in Total'] . '","' . $value['On Order'] . '","' . $value['Modified DateTime'] . '")' . $sep;

            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/onHandInventory.txt', $content);

        }

        info("**************************************** AX To Synccare : On Hand Inventory File Generated Successfully ********************************");

        return $this->readOnHandInventoryFile();
        // return $this->successWithMessage("On Hand Inventory File Generated Successfully.");
    }

    public function readOnHandInventoryFile(){

        info("**************************************** AX To Synccare : Preparing Reading On Hand Inventory File  ********************************");

        $path = public_path('PswLiveTemp/onHandInventory.txt');

        if (File::exists($path)) {

            $count = TempOnHandInventory::where('pendingProcess', 1)->first();
            if(!$count){
                TempOnHandInventory::truncate();
            }
 
            $file = File::get($path);

            $sqls = explode(";\n", $file);

            foreach ($sqls as $sql) {

                if ($sql != '') {
                    DB::connection('mysql2')->select($sql);
                }
            }

            $lastSyncDatetime = TempOnHandInventory::orderBy("ModifiedDateTime", 'desc')->first()->ModifiedDateTime;
            AxSyncDatetime::where("id", 1)->update([ "on_hand_inventory" => $lastSyncDatetime ]);

            info("**************************************** AX To Synccare :  On Hand Inventory File Executed Successfully. ********************************");
            return $this->successWithMessage("On Hand Inventory File Executed Successfully.");
             
        } else {
            echo "no data";
            die;
        }
    }


    public function syncOnHandInventoryToNewsystem(){
        // die;
        $datas = TempOnHandInventory::where("pendingProcess", 1)->limit(400)->get();

        if($datas->isEmpty()){
            info("All OnHand Syncced to New System or Current System");
            return response("All OnHand Syncced to New System");
        }

        // dd($datas);
        // $bulk = array();
        foreach($datas as $data){
            $details = array(
                "ERPLYSKU" => trim($data->ERPLYSKU),
                "Item" => $data->Item,
                "Configuration" => $data->Configuration,
                "Colour" => $data->Colour,
                "Size" => $data->Size,
                "Warehouse" => trim($data->Warehouse),
                "Location" => trim($data->Location),
                "PhysicalInventory" => $data->PhysicalInventory,
                "PhysicalReserved" => $data->PhysicalReserved,
                "AvailablePhysical" => $data->AvailablePhysical,
                "OrderInTotal" => $data->OrderInTotal,
                "OnOrder" => $data->OnOrder,
                "ModifiedDateTime" => $data->ModifiedDateTime,
                // "pendingProcess" => 1,
                "binSOHPending" => 1,
                "binSOHAdjust" => 1,

            );

            $compareField = $data->ERPLYSKU."-".$data->Item.'-'.$data->Configuration.'-'.$data->Colour.'-'.$data->Size.'-'.$data->AvailablePhysical;
            $details["compareField"] = $compareField;
             
            // $bulk[] = $details;
            // first getting data from newsystem and check 

            try{
                $check = LiveOnHandInventory::where("ERPLYSKU", trim($data->ERPLYSKU))->where("Warehouse", trim($data->Warehouse))->where("Location", trim($data->Location))->first();
                 
                if($check){

                    //check if comparefield matched or not
                    $comCheck = LiveOnHandInventory::where("id", $check->id)->where("compareField", $compareField)->first();
                    if(!$comCheck){
                        LiveOnHandInventory::updateOrcreate(
                            [
                                "ERPLYSKU" => trim($data->ERPLYSKU),
                                "Warehouse" => trim($data->Warehouse),
                                "Location" => trim($data->Location),
                            ],
                            $details
                        );
                    }
                     
                }
                else{
                    LiveOnHandInventory::updateOrcreate(
                        [
                            "ERPLYSKU" => trim($data->ERPLYSKU),
                            "Warehouse" => trim($data->Warehouse),
                            "Location" => trim($data->Location),
                        ],
                        $details
                    );
                }
                $data->pendingProcess = 0;
                $data->save();
            }catch(Exception $e){
                $data->pendingProcess = 0;
                $data->save();
                info(" Duplicate Error ".$data->id);
                // info($e);
            }
            
            
        }

        // LiveOnHandInventory::insert($bulk);
        // foreach($datas as $data){
        //     $data->pendingProcess = 0;
        //     $data->save();
        // }
        info("On hand Inventory Synccing...");
        return response("On hand Inventory Synccing...");
        return $this->successWithMessage("On Hand Inventory Synced to Newsystem Table Successfully.");
    }

    public function syncOnHandInventoryByLastModified(){

        info("Hello im from on hand inventory last modified");

        $latest = LiveOnHandInventory::orderBy("ModifiedDateTime", "desc")->first();

        $datas = OnHandInventory::where("Modified DateTime",'>', $latest->ModifiedDateTime)->where("ERPLY SKU",'<>', '')->orderBy("Modified DateTime",'asc')->limit(100)->get();
        if($datas->isEmpty()){
            info("Ax to Synccare : All On Hand Inventory Synced");
            return $this->successWithMessage("All Items By Locations Synced");
        }
        foreach($datas as $data){
            $details = array(
                "ERPLYSKU" => $data["ERPLY SKU"],
                "Item" => $data["Item"],
                "Configuration" => $data["Configuration"],
                "Colour" => $data["Colour"],
                "Size" => $data["Size"],
                "Warehouse" => $data["Warehouse"],
                "Location" => $data["Location"],
                "PhysicalInventory" => $data["Physical Inventory"],
                "PhysicalReserved" => $data["Physical Reserved"],
                "AvailablePhysical" => $data["Available Physical"],
                "OrderInTotal" => $data["Ordered in Total"],
                "OnOrder" => $data["On Order"],
                "ModifiedDateTime" => $data["Modified DateTime"],
                // "pendingProcess" => 1,
                // "binSOHPending" => 1,

            );

            $check = LiveOnHandInventory::where("ERPLYSKU", $data["ERPLY SKU"])
                    ->where("Location", $data["Location"])
                    ->where("Warehouse", $data["Warehouse"])
                    ->first();
            if($check){
                if($check->pendingProcess == 0){
                    $details["pendingProcess"] = 1;    
                } 

                if($check->binSOHPending == 0){
                    $details["binSOHAdjust"] = 1;    
                } 
                
            }else{
                $details["pendingProcess"] = 1;
                $details["binSOHPending"] = 1;
            }

            LiveOnHandInventory::updateOrcreate(
                [
                    "ERPLYSKU" => $data["ERPLY SKU"],
                    "Warehouse" => $data["Warehouse"],
                    "Location" => $data["Location"],
                ],
                $details
            ); 

            // LiveWarehouseLocation::where("LocationID", $data["Warehouse"])->update(["binbayPending" => 1, "binbaySOHPending" => 1]);

        }
        info("Ax to Synccare : On Hand Inventory Synced Successfully");
        return response()->json($datas);
        return $this->successWithMessage("On Hand Inventory Synced to Newsystem Table Successfully.");
    }




    //temp code
    

 

}
