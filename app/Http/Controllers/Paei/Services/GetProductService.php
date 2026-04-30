<?php

namespace App\Http\Controllers\Paei\Services;

use App\Classes\UserLogger;
use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Services\EAPIService;
use App\Models\ErplySyncDate;
use App\Models\PAEI\{MatrixProduct, ProductGroup, Supplier, TempDate, UserOperationLog, VariationProduct, Warehouse};
use App\Models\PswClientLive\Local\{LiveItemByLocation, LiveProductMatrix, LiveProductVariation};
use App\Traits\UserOperationTrait;
use Carbon\Carbon;

class GetProductService implements UserOperationInterface
{

    protected $matrix;
    protected $liveProductMatrix;
    protected $variation;
    protected $variationLive;
    protected $letsLog;
    protected $api;
    // protected $userOperationService;
    use UserOperationTrait;

    public function __construct(MatrixProduct $mp, LiveProductMatrix $liveProductMatrix, VariationProduct $vp, LiveProductVariation $vpLive, UserLogger $logger, EAPIService $api)
    {
        $this->matrix = $mp;
        $this->liveProductMatrix = $liveProductMatrix;
        $this->variation = $vp;
        $this->variationLive = $vpLive;
        $this->letsLog = $logger;
        $this->api = $api;
        // $this->userOperationService = $userOperationService;
    }

    public function saveUpdate($products)
    {
        $responseData = [];
        foreach ($products as $key =>  $p) {

            if ($p['type'] == "MATRIX") {

              $this->matrixSaveUpdate($p, $this->api->client->clientCode);
            } else {
                // dd($p);

                // continue;
                // dd('variation',$p);
                if (!str_contains($p["code"], "PSW") && $this->api->client->clientCode == 603303) {
                    $this->variationSaveUpdate($p, $this->api->client->clientCode);
                } else {
                    $this->variationSaveUpdate($p, $this->api->client->clientCode);
                }
            }

            $responseData[] = [
                'key ' => $key,
                'type ' => $p['type'] ?? 'Unknown Type',
                'id ' => $p['productID'] ?? null,
                'message' => "Product with ID {$p['productID']} processed as " . ($p['type'] ?? 'Unknown Type'),
                'attribute' => $p['attributes'] ?? null

            ];
        }

        return response()->json (['status' => 200, 'message' => "Products fetched and processed successfully.", 'data' => $responseData]);


    }

    public function saveUpdateByWebhook($product, $clientCode)
    {

        // foreach($products as $p){
        if ($product['type'] == "MATRIX") {

            $this->matrixSaveUpdate($product, $clientCode);
        } else {

            // if(!str_contains($p["code"], "PSW") && $this->api->client->clientCode == 603303){
            //     $this->variationSaveUpdate($p);
            // }else{
            $this->variationSaveUpdate($product, $clientCode);
            // }

        }
        // }

        // return response()->json(['status'=>200, 'message'=>"Product fetched Successfully."]);
    }


    public function saveUpdateV2($products)
    {

        info("Updated product : " . count($products));
        if (count($products) >= 1) {
            info("PID : " . $products[0]["productID"]);
        }

        foreach ($products as $p) {
            if ($p['type'] == "MATRIX") {

                $this->matrixSaveUpdate($p, $this->api->client->clientCode);
            } else {

                // if(!str_contains($p["code"], "PSW") && $this->api->client->clientCode == 603303){
                //     $this->variationSaveUpdate($p);
                // }else{
                $this->variationSaveUpdate($p, $this->api->client->clientCode);
                // }

            }
        }

        $pp = collect($products);
        $forUpdate = $pp->last();
        TempDate::where("id", 1)->update(["datetime" => date('Y-m-d H:i:s', $forUpdate['added'])]);

        return response()->json(['status' => 200, 'message' => "Product fetched Successfully."]);
    }

    public function saveUpdatePIM($products)
    {

        foreach ($products as $p) {
            if ($p['type'] == "MATRIX") {

                $this->matrixSaveUpdatePIM($p);
            } else {

                if (!str_contains($p["code"], "PSW")) {
                    $this->variationSaveUpdatePIM($p);
                }
            }
        }

        return response()->json(['status' => 200, 'message' => "Product fetched Successfully."]);
    }


    protected function matrixSaveUpdate(array $product, int $clientCode)
    {
        // Determine ERPLY flag
        $erplyFlag = ($clientCode == 607655) ? null : 'PSW';

        // Old record for logging
        $old = $this->liveProductMatrix
            ->where('erplyFlag', $erplyFlag)
            ->where('erplyID', $product['productID'])
            ->first();

        // Get school info
        $school = ProductGroup::where('clientCode', $clientCode)
            ->where('productGroupID', $product['groupID'])
            ->first();

        $attr = [];

        $attributes = $product['attributes'] ?? [];


        if (isset($product['attributes']) && is_array($product['attributes'])) {
            // Extract attributes from matrix
            $attr = array_column($product['attributes'], 'attributeValue', 'attributeName');
        }

        $webEnabled = 1;
        $erplyEnabled = 1;
        $erplyDeleted = 0;

        // Normalize status
        $status = $product['status'] ?? null;

        if ($status === 'not_for_sale') {
            $webEnabled = 0;
            $erplyEnabled = 0;
        } elseif ($status === 'active') {
            $webEnabled = 1;
            $erplyEnabled = 1;
        } elseif ($status === 'archived') {
            // $erplyDeleted = 1;
            $webEnabled = 0;
        }

        $sumSOH = function ($json) {
            $total = 0;


            $data = json_decode($json ?? '', true);


            if (is_array($data)) {
                foreach ($data as $row) {
                    $total += (float) ($row['SOH'] ?? 0);
                }
            }

            return $this->nullIfEmpty($total);
        };

        // Assign all values safely
        $schoolId             = $this->nullIfEmpty($attr['SchoolID'] ?? ($product['groupID'] ?? null));
        $schoolName           = $this->nullIfEmpty($product['groupName'] ?? ($school ? $school->name : null));
        $customerGroup        = $this->nullIfEmpty($attr['CustomerGroup'] ?? null);
        $erplySKU             = $this->nullIfEmpty($attr['ERPLYSKU'] ?? ($product['code'] ?? null));
        $webSKU               = $this->nullIfEmpty($attr['WEBSKU'] ?? $erplySKU);
        $itemId               = $this->nullIfEmpty($attr['ITEMID'] ?? ($product['productID'] ?? null));
        $productId               = $product['productID'] ?? null;
        $itemName             = $this->nullIfEmpty($attr['Matrix_Product_Name'] ?? ($product['name'] ?? null));
        $colourId             = $this->nullIfEmpty($attr['ColourID'] ?? null);
        $colourName           = $this->nullIfEmpty($attr['ColourName'] ?? null);
        $sizeId               = $this->nullIfEmpty($attr['SizeID'] ?? null);
        $configId             = $this->nullIfEmpty($attr['CONFIGID'] ?? null);
        $configName           = $this->nullIfEmpty($attr['ConfigName'] ?? null);
        $eanBarcode           = $this->nullIfEmpty($attr['EANBarcode'] ?? null);
        $sofTemplate          = $this->nullIfEmpty($attr['SOFTemplate'] ?? null);
        $sofName              = $this->nullIfEmpty($attr['SOFName'] ?? null);
        $sofOrder             = $this->nullIfEmpty($attr['SOFOrder'] ?? null);
        $sofStatus            = $this->nullIfEmpty($attr['SOFStatus'] ?? null);
        $plmStatus            = $this->nullIfEmpty($attr['PLMStatus'] ?? null);
        $productType          = $this->nullIfEmpty($attr['ProductType'] ?? null);
        $productSubType       = $this->nullIfEmpty($attr['ProductSubType'] ?? null);
        $supplier             = $this->nullIfEmpty($attr['Supplier'] ?? ($product['supplierName'] ?? null));
        $gender               = $this->nullIfEmpty($attr['Gender'] ?? null);
        $categoryName         = $this->nullIfEmpty($attr['CategoryName'] ?? null);
        $itemWeightGrams      = $this->nullIfEmpty($attr['ItemWeightGrams'] ?? null);

        $decodeStoreLocation = function ($json) {
            $data = json_decode($json ?? '', true);
            return $this->nullIfEmpty($data[0]['location'] ?? null);
        };

        $attrLower = array_change_key_case($attr, CASE_LOWER);

        $primaryJson   = $attrLower['primaryjson'] ?? null;
        $secondaryJson = $attrLower['secondaryjson'] ?? null;
        // Default Store
        // $defaultStore = $attr['DefaultStore'] ?? null;
        // $secondaryStore = $attr['SecondaryStore'] ?? null;
        // Default Store
        $defaultStore = $decodeStoreLocation($primaryJson ?? null);
        // Secondary Store
        $secondaryStore = $decodeStoreLocation($secondaryJson ?? null);



        // SOH (Default)
        $sohDefault = $sumSOH($primaryJson ?? null);
        // SOH (Secondary)
        $sohSecondary = $sumSOH($secondaryJson ?? null);

        // dd($defaultStore, $secondaryStore);

        $erplyFlagModified    = $this->nullIfEmpty($attr['ERPLYFLAGModified'] ?? null);
        $pswPriceListItemCategory = $this->nullIfEmpty(trim(explode(':', $attr['PSWPRICELISTITEMCATEGORY'] ?? '')[0] ?? null));
        $attCateName = $this->nullIfEmpty(trim(explode(':', $attr['PSWPRICELISTITEMCATEGORY'] ?? '')[1] ?? null));
        $category_Name = $this->nullIfEmpty($attr['Category_Name'] ?? $attCateName ?? null);

        $itemLastModified     = !empty($attr['ItemLastModified'])
            ? date('Y-m-d H:i:s', strtotime($attr['ItemLastModified']))
            : null;

        $sofLastModified      = !empty($attr['SOFLastModified'])
            ? date('Y-m-d H:i:s', strtotime($attr['SOFLastModified']))
            : null;

        $schoolLastModified   = !empty($attr['SchoolLastModified'])
            ? date('Y-m-d H:i:s', strtotime($attr['SchoolLastModified']))
            : null;

        $priceLastModified    = !empty($attr['PriceLastModified'])
            ? date('Y-m-d H:i:s', strtotime($attr['PriceLastModified']))
            : null;

        $availableForPurchase = $attr['AvailableForPurchase'] ?? 1;
        $customItemName       = $this->nullIfEmpty($attr['customItemName'] ?? null);
        $receiptDescription   = $this->nullIfEmpty($attr['receiptDescription'] ?? null);
        $barcodeDuplicate     = $attr['barcodeDuplicate'] ?? 0;
        $colorFlag            = $attr['colorFlag'] ?? 0;
        $genericProduct       = $attr['genericProduct'] ?? 0;
        $erplyError           = $this->nullIfEmpty($attr['erplyError'] ?? null);
        $vUpdate              = $attr['vUpdate'] ?? 1;
        $mUpdate              = $attr['mUpdate'] ?? 1;
        $pushCount            = $attr['pushCount'] ?? 0;
        $axCheckFlag          = $attr['axCheckFlag'] ?? 1;
        $assortmentPending    = $attr['assortmentPending'] ?? 1;
        $assortmentRemovePending = $attr['assortmentRemovePending'] ?? 1;
        $imagePending         = $attr['imagePending'] ?? 1;
        $imageUrl             = $this->nullIfEmpty($attr['imageUrl'] ?? null);
        $variationPending     = $attr['variationPending'] ?? 1;
        $checkErply           = $attr['checkErply'] ?? 1;
        $icsc                 = $this->nullIfEmpty($attr['ICSC'] ?? null);

        /* Numeric fields (keep 0 default, don't convert to null) */
        $retailSalesPrice         = $product['priceWithVat'] ?? 00.00;
        $retailSalesPrice2        = $this->nullIfEmpty($attr['RetailSalesPrice2'] ?? 00.00);
        $retailSalesPriceExclGST  = $this->nullIfEmpty($attr['RetailSalesPriceExclGST'] ?? 00.00);
        $retailSalesPriceExclGST2 = $this->nullIfEmpty($attr['RetailSalesPriceExclGST2'] ?? 00.00);
        $costPrice                = $this->nullIfEmpty($attr['CostPrice'] ?? 00.00);
        $fields = [
            'erplyID' => $productId,
            'type' => $product['type'] ?? 'MATRIX',
            'productAdded' => date('Y-m-d H:i:s', $product['added']),
            'SchoolID' => $schoolId,
            'SchoolName' => $schoolName,
            'CustomerGroup' => $customerGroup,
            'ERPLYSKU' => $erplySKU,
            'WEBSKU' => $productId,
            'ITEMID' => $itemId,
            'ItemName' => $itemName,
            'ColourID' => $colourId,
            'ColourName' => $colourName,
            'SizeID' => $sizeId,
            'CONFIGID' => $configId,
            'ConfigName' => $configName,
            'EANBarcode' => $eanBarcode,
            'SOFTemplate' => $sofTemplate,
            'SOFName' => $sofName,
            'SOFOrder' => $sofOrder,
            'SOFStatus' => $sofStatus,
            'PLMStatus' => $plmStatus,
            'ProductType' => $productType,
            'ProductSubType' => $productSubType,
            'Supplier' => $supplier,
            'Gender' => $gender,
            'CategoryName' => $categoryName,
            'ItemWeightGrams' => $itemWeightGrams,
            'RetailSalesPrice' => $retailSalesPrice,
            'RetailSalesPrice2' => $retailSalesPrice2,
            'RetailSalesPriceExclGST' => $retailSalesPriceExclGST,
            'RetailSalesPriceExclGST2' => $retailSalesPriceExclGST2,
            'CostPrice' => $costPrice,
            'DefaultStore' => $defaultStore,
            'SecondaryStore' => $secondaryStore,
            'ERPLYFLAG' => $erplyFlag,
            'ERPLYFLAGModified' => $erplyFlagModified,
            'Category_Name' => $category_Name,
            'PSWPRICELISTITEMCATEGORY' => $pswPriceListItemCategory,
            'ItemLastModified' => $itemLastModified,
            'SOFLastModified' => $sofLastModified,
            'SchoolLastModified' => $schoolLastModified,
            'PriceLastModified' => $priceLastModified,
            'AvailableForPurchase' => $availableForPurchase,
            'WebEnabled' => $webEnabled,
            'customItemName' => $customItemName,
            'receiptDescription' => $receiptDescription,
            'barcodeDuplicate' => $barcodeDuplicate,
            'colorFlag' => $colorFlag,
            'genericProduct' => $genericProduct,
            'erplyEnabled' => $erplyEnabled,
            'erplyError' => $erplyError,
            'vUpdate' => $vUpdate,
            'mUpdate' => $mUpdate,
            'pushCount' => $pushCount,
            'axCheckFlag' => $axCheckFlag,
            'assortmentPending' => $assortmentPending,
            'assortmentRemovePending' => $assortmentRemovePending,
            'imagePending' => $imagePending,
            'imageUrl' => $imageUrl,
            'variationPending' => $variationPending,
            'checkErply' => $checkErply,
            'erplyDeleted' => $erplyDeleted,
            'erplyAttributes' => json_encode($attributes ?? []),
            'erplyStatus' => $status
        ];
// dd(LiveProductMatrix::where('websku', '19855_4400004_0')->first());
        // Update or create
        $change = $this->liveProductMatrix->updateOrCreate(
            ['ERPLYFLAG' => $erplyFlag, 'erplyID' => $itemId],
            $fields
        );


        // Log
        $this->letsLog->setChronLog(
            $old ? json_encode($old, true) : '',
            json_encode($change, true),
            $old ? "Matrix Product Updated" : "Matrix Product Created"
        );
        $sohDefData = [
            'icsc' => $icsc,
            'AvailablePhysical' => $sohDefault,
            'warehouse' => $defaultStore,
            'item' => $product['productID'] ?? null
        ];

        $sohSecData = [
            'icsc' => $icsc,
            'AvailablePhysical' => $sohSecondary,
            'warehouse' => $secondaryStore,
            'item' => $product['productID'] ?? null

        ];

        $soh = LiveItemByLocation::updateOrCreate(
            ['icsc' => $icsc, 'warehouse' => $defaultStore],
            $sohDefData
        );


        $soh = LiveItemByLocation::updateOrCreate(
            ['icsc' => $icsc, 'warehouse' => $secondaryStore],
            $sohSecData
        );

        $this->setSyncDate($product, 'MATRIX');


        return $change;
    }

    public function setSyncDate($product, $type)
    {

        $erplySyncDate = ErplySyncDate::first();

        if (!$erplySyncDate) {
            $erplySyncDate = new ErplySyncDate();
        }

        if ($type == 'MATRIX') {
            $erplySyncDate->matrix_product_added = Carbon::createFromTimestamp($product['added'])->toDateTimeString();
            $erplySyncDate->matrix_product_last_modified = Carbon::createFromTimestamp($product['lastModified'])->toDateTimeString();
        } else {
            $erplySyncDate->variation_product_added = Carbon::createFromTimestamp($product['added'])->toDateTimeString();
            $erplySyncDate->variation_product_last_modified = Carbon::createFromTimestamp($product['lastModified'])->toDateTimeString();
        }

        $erplySyncDate->save();
        // dump($erplySyncDate->matrix_product_added, $product['added']);
        // dump($erplySyncDate);

    }

   private function nullIfEmpty($value)
    {
        return $value === '' ? null : $value;
    }
    protected function variationSaveUpdate($product, $clientCode)
    {

    dd($product);

        $erplyFlag = ($clientCode == 607655) ? '' : 'PSW';

        $school = ProductGroup::where('clientCode', $clientCode)
            ->whereNotNull('subGroups')
            ->where('subGroups', '!=', '')
            ->whereRaw('JSON_VALID(subGroups)')
            ->whereRaw(
                "JSON_SEARCH(subGroups, 'one', ?, NULL, '$[*].productGroupID') IS NOT NULL",
                [(int) $product['groupID']]
            )
            ->first();
            // dd($school);

        // Extract attributes as key => value
        $attr = [];
        $attributes = $product['attributes'] ?? [];


        if (isset($product['attributes']) && is_array($product['attributes'])) {
            // Extract attributes from matrix
            $attr = array_column($product['attributes'], 'attributeValue', 'attributeName');
        }


        $webEnabled = 1;
        $erplyEnabled = 1;
        $erplyDeleted = 0;

        // Normalize status
        $status = $product['status'] ?? null;


        if ($status === 'not_for_sale') {
            $webEnabled = 0;
            $erplyEnabled = 0;
        } elseif ($status === 'active') {
            $webEnabled = 1;
            $erplyEnabled = 1;
        } elseif ($status === 'archived') {
            $erplyEnabled = 0;
        }

        // ✅ ALL VARIABLES WITH SAFE FALLBACKS
        $schoolId             = $this->nullIfEmpty($attr['SchoolID'] ?? ($product['groupID'] ?? null));
        $schoolName           = $this->nullIfEmpty($product['groupName'] ?? ($school ? $school->name : null));
        $customerGroup        = $this->nullIfEmpty($attr['CustomerGroup'] ?? null);
        $erplySKU             = $this->nullIfEmpty($attr['ERPLYSKU'] ?? ($product['code'] ?? null));
        $webSKU               = $this->nullIfEmpty($attr['WEBSKU'] ?? ($product['code2'] ?? null));
        $itemId               = $this->nullIfEmpty($attr['ITEMID']  ?? null);
        $itemName             = $this->nullIfEmpty($attr['Matrix_Product_Name'] ?? ($product['name'] ?? null));
        $configId             = $this->nullIfEmpty($attr['CONFIGID'] ?? null);
        $configName           = $this->nullIfEmpty($attr['CONFIGNAME'] ?? null);
        $eanBarcode           = $this->nullIfEmpty($attr['EANBarcode'] ?? ($product['code'] ?? null));
        $sofTemplate          = $this->nullIfEmpty($attr['SOFTemplate'] ?? null);
        $sofName              = $this->nullIfEmpty($attr['SOFName'] ?? null);
        $sofOrder             = $this->nullIfEmpty($attr['SOFOrder'] ?? null);
        $sofStatus            = $this->nullIfEmpty($attr['SOFStatus'] ?? null);
        $plmStatus            = $this->nullIfEmpty($attr['PLMStatus'] ?? null);
        $productType          = $this->nullIfEmpty($attr['ProductType'] ?? ($product['type'] ?? null));
        $productSubType       = $this->nullIfEmpty($attr['ProductSubType'] ?? ($product['seriesName'] ?? null));
        $supplier             = $this->nullIfEmpty($attr['Supplier'] ?? ($product['supplierName'] ?? null));
        $gender               = $this->nullIfEmpty($attr['Gender'] ?? null);
        $categoryName         = $this->nullIfEmpty($attr['CategoryName'] ?? ($product['categoryName'] ?? null));
        $itemWeightGrams      = $this->nullIfEmpty($attr['ItemWeightGrams'] ?? ($product['netWeight'] ?? null));
        $receiptDescription   = $this->nullIfEmpty($attr['receiptDescription'] ?? null);



        $decodeStoreLocation = function ($json) {
            $data = json_decode($json ?? '', true);
            return $this->nullIfEmpty($data[0]['location'] ?? null);
        };

        $sumSOH = function ($json) {
            $total = 0;


            $data = json_decode($json ?? '', true);


            if (is_array($data)) {
                foreach ($data as $row) {
                    $total += (float) ($row['SOH'] ?? 0);
                }
            }

            return $this->nullIfEmpty($total);
        };


        $attrLower = array_change_key_case($attr, CASE_LOWER);

        $primaryJson   = $attrLower['primaryjson'] ?? null;
        $secondaryJson = $attrLower['secondaryjson'] ?? null;
        // Default Store
        $defaultStore = $decodeStoreLocation($primaryJson ?? null);
        // Secondary Store
        $secondaryStore = $decodeStoreLocation($secondaryJson ?? null);
        // $defaultStore = $attr['DefaultStore'] ?? null;
        // $secondaryStore = $attr['SecondaryStore'] ?? null;

        // SOH (Default)
        $sohDefault = $sumSOH($primaryJson ?? null);
        // SOH (Secondary)
        $sohSecondary = $sumSOH($secondaryJson ?? null);

        // dd($sohSecondary, $sohDefault);



        $erplyFlagModified    = $this->nullIfEmpty($attr['ERPLYFLAGModified'] ?? null);
        $sofLastModified      = !empty($attr['SOFLastModified'])
            ? date('Y-m-d H:i:s', strtotime($attr['SOFLastModified']))
            : null;

        $availableForPurchase = $attr['AvailableForPurchase'] ?? ($product['active'] ?? 0);

        $itemLastModified     = !empty($attr['ItemLastModified'])
            ? date('Y-m-d H:i:s', strtotime($attr['ItemLastModified']))
            : (isset($product['lastModified']) ? date('Y-m-d H:i:s', $product['lastModified']) : null);

        $schoolLastModified   = !empty($attr['SchoolLastModified'])
            ? date('Y-m-d H:i:s', strtotime($attr['SchoolLastModified']))
            : null;

        $priceLastModified    = !empty($attr['PriceLastModified'])
            ? date('Y-m-d H:i:s', strtotime($attr['PriceLastModified']))
            : (isset($product['lastModified']) ? date('Y-m-d H:i:s', $product['lastModified']) : null);
        $pswPriceListItemCategory = $this->nullIfEmpty(trim(explode(':', $attr['PSWPRICELISTITEMCATEGORY'] ?? '')[0] ?? null));
        $attCateName = $this->nullIfEmpty(trim(explode(':', $attr['PSWPRICELISTITEMCATEGORY'] ?? '')[1] ?? null));
        $category_Name = !empty($attr['Category_Name'])
            ? $attr['Category_Name']
            : $attCateName;




        $icsc                 = $this->nullIfEmpty($attr['ICSC'] ?? null);
        $customItemName       = $this->nullIfEmpty($attr['customItemName'] ?? null);

        /* Numeric / flags (keep defaults) */
        $retailSalesPrice         = $product['priceWithVat'] ?? 00.00;
        $retailSalesPrice2        = $this->nullIfEmpty($attr['RetailSalesPrice2'] ?? 00.00);
        $retailSalesPriceExclGST  = $this->nullIfEmpty($attr['RetailSalesPriceExclGST'] ?? 00.00);
        $retailSalesPriceExclGST2 = $this->nullIfEmpty($attr['RetailSalesPriceExclGST2'] ?? 00.00);
        $costPrice                = $this->nullIfEmpty($attr['CostPrice'] ?? 00.00);

        $barcodeDuplicate     = $attr['barcodeDuplicate'] ?? 0;
        $colorFlag            = $attr['colorFlag'] ?? 0;
        $vUpdate              = $attr['vUpdate'] ?? 1;
        $desUpdated           = $attr['desUpdated'] ?? 0;
        $stockPending         = $attr['stockPending'] ?? 1;
        $genericProduct       = $attr['genericProduct'] ?? 0;
        $checkErply           = $attr['checkErply'] ?? 1;
        $assortmentPending    = $attr['assortmentPending'] ?? 1;
        $assortmentRemovePending = $attr['assortmentRemovePending'] ?? 1;
        $imagePending         = $attr['imagePending'] ?? 1;
        //get color id and size id name

        $variationDescription = $product['variationDescription'] ?? [];

        $attributes = [];

        foreach ($variationDescription as $variation) {
            $key = strtolower(trim($variation['name'] ?? ''));

            $attributes[$key] = [
                'id'    => $variation['variationID'] ?? null,
                'value' => $variation['value'] ?? null,
            ];
        }

        // Access easily
        $colorId   = $attributes['color']['id'] ?? null;
        $colorName = $attributes['color']['value'] ?? null;

        $sizeId    = $attributes['size']['id'] ?? null;
        $sizeName  = $attributes['size']['value'] ?? null;


        // ✅ OLD RECORD
        $old = $this->variationLive
            ->where('ERPLYFLAG', $erplyFlag)
            ->where('erplyID', $product['productID'])
            ->first();

        // ✅ BUILD COMPARE STRING
        $compareField = implode('_', [
            $product['productID'] ?? '',
            $product['code'] ?? '',
            $product['code2'] ?? '',
            $product['code3'] ?? '',
            $product['supplierCode'] ?? '',
            $product['groupID'] ?? '',
            $product['categoryID'] ?? '',
            $product['name'] ?? '',
            $product['price'] ?? 0,
            $product['priceWithVat'] ?? 0,
            $product['status'] ?? '',
            $product['type'] ?? '',
            $product['groupName'] ?? '',
            $product['categoryName'] ?? '',
            $product['netWeight'] ?? 0,
            isset($product['lastModified']) ? date('Y-m-d H:i:s', $product['lastModified']) : '',
        ]);


        // ✅ UPDATE OR CREATE ALL COLUMNS
        $change = $this->variationLive->updateOrCreate(
            [
                "ERPLYFLAG" => $erplyFlag,
                "erplyID"   => $product['productID']
            ],
            [
                "ERPLYFLAG" => $erplyFlag,
                "erplyID"   => $product['productID'],

                // BASIC
                "ItemName"          => trim($itemName),
                "ERPLYSKU"          => $erplySKU,
                "WEBSKU"            => $product['parentProductID'] ?? null,
                "ITEMID"            => $itemId,
                "schoolID"          => $schoolId,
                "schoolName"        => $schoolName,
                "CustomerGroup"     => $customerGroup,

                // CATEGORY
                "Category_Name"     => $category_Name,
                "CategoryName"     => $category_Name,
                "ICSC"              => $icsc,

                // PRICE
                "RetailSalesPrice"  => $retailSalesPrice,
                "RetailSalesPrice2" => $retailSalesPrice2,
                "RetailSalesPriceExclGST" => $retailSalesPriceExclGST,
                "RetailSalesPriceExclGST2" => $retailSalesPriceExclGST2,
                "CostPrice"         => $costPrice,
                "PSWPRICELISTITEMCATEGORY" => $pswPriceListItemCategory ?? null,
                'productAdded' => date('Y-m-d H:i:s', $product['added']),

                // STATUS
                "AvailableForPurchase" => $availableForPurchase,
                "WebEnabled"          => $webEnabled,
                "deleted"             => $erplyDeleted,

                // SUPPLIER
                "Supplier"            => $supplier,

                // TYPE
                "ProductType"         => $productType,
                "ProductSubType"      => $productSubType,
                "Gender"              => $gender,

                // WEIGHT
                "ItemWeightGrams"     => $itemWeightGrams,

                // BARCODE
                "EANBarcode"          => $eanBarcode,
                "barcodeDuplicate"    => $barcodeDuplicate,
                "colorFlag"           => $colorFlag,

                // SOF
                "SOFTemplate"         => $sofTemplate,
                "SOFName"             => $category_Name,
                "SOFOrder"            => $sofOrder,
                "SOFStatus"           => $sofStatus,
                "PLMStatus"           => $plmStatus,
                "SOFLastModified"     => $sofLastModified,

                // ITEM DETAILS
                "ConfigName"          => $configName,
                "CONFIGID"            => $configId,
                "ColourName"          => $colorName,
                "ColourID"            => $colorId,
                "SizeID"              => $sizeName,
                "customItemName"      => $customItemName,
                'receiptDescription' => $receiptDescription,


                // DATES
                "ItemLastModified"     => $itemLastModified,
                "SchoolLastModified"   => $schoolLastModified,
                "PriceLastModified"    => $priceLastModified,

                // STORES
                "DefaultStore"         => $defaultStore,
                "SecondaryStore"       => $secondaryStore,

                // FLAGS
                "desUpdated"           => $desUpdated,
                "stockPending"         => $stockPending,
                "genericProduct"       => $genericProduct,
                "checkErply"           => $checkErply,
                "erplyDeleted"         => $erplyDeleted,

                "assortmentPending"    => $assortmentPending,
                "assortmentRemovePending" => $assortmentRemovePending,
                "erplyEnabled"         => $erplyEnabled,
                "imagePending"         => $imagePending,
                "axResync"             => 1,

                // RAW DATA
                "compareField"         => $compareField,
                "primaryJson"         => $primaryJson,
                "secondaryJson"        => $secondaryJson,
                "erplyAttributes" => json_encode($attributes ?? []),
                "erplyStatus" => $status ?? null,

            ]
        );

        $sohDefData = [
            'icsc' => $icsc,
            'AvailablePhysical' => $sohDefault,
            'warehouse' => $defaultStore,
            'item' => $product['productID'] ?? null
        ];

        $sohSecData = [
            'icsc' => $icsc,
            'AvailablePhysical' => $sohSecondary,
            'warehouse' => $secondaryStore,
            'item' => $product['productID'] ?? null

        ];
//  dd($sohDefData,$sohSecData);
        $soh = LiveItemByLocation::updateOrCreate(
            ['icsc' => $icsc, 'warehouse' => $defaultStore],
            $sohDefData
        );


        $soh = LiveItemByLocation::updateOrCreate(
            ['icsc' => $icsc, 'warehouse' => $secondaryStore],
            $sohSecData
        );

        $this->setSyncDate($product, 'VARIATION');


        // ✅ LOG
        $this->letsLog->setChronLog(
            $old ? json_encode($old, true) : '',
            json_encode($change, true),
            $old ? "Variation Product Updated" : "Variation Product Created"
        );
    }

    protected function matrixSaveUpdatePIM($product)
    {
        $old = $this->matrix->where('productID', $product['id'])->first();


        $change = $this->matrix->updateOrCreate(
            [
                "productID"  =>  $product['id']
            ],
            [
                "productID" => $product['id'],
                "type" => $product['type'],
                "active" => $product['active'],
                "status" => $product['status'],
                "name"  => $product['name']['en'],
                "code"  => $product['code'],
                "code2"  => @$product['code2'],
                "code3"  => @$product['code3'],
                "supplierCode"  => @$product['supplierCode'],
                "code5"  =>  @$product['code5'],
                "code6"  =>  @$product['code6'],
                "code7"  =>  @$product['code7'],
                "code8"  =>  @$product['code8'],
                "groupID"  => $product['group_id'],
                "groupName"  => $product['groupName'],
                "price"  => @$product['price'],
                "priceWithVat"  => @$product['priceWithVat'],
                "displayedInWebshop"  => @$product['displayedInWebshop'],
                "categoryID"  => @$product['categoryID'],
                "categoryName"  => @$product['categoryName'],
                "supplierID"  => @$product['supplierID'],
                "supplierName"  => @$product['supplierName'],
                "unitID"  => @$product['unit_id'],
                "unitName"  => @$product['unitName'],
                "taxFree"  => @$product['taxFree'],
                "deliveryTime"  => @$product['deliveryTime'],
                "vatrateID"  => @$product['vatrateID'],
                "vatrate"  => @$product['vatrate'],
                "hasQuickSelectButton"  => @$product['hasQuickSelectButton'],
                "isGiftCard"  => @$product['isGiftCard'],
                "isRegularGiftCard"  => @$product['isRegularGiftCard'],
                "nonDiscountable"  => @$product['nonDiscountable'],
                "nonRefundable"  => @$product['nonRefundable'],
                "manufacturerName"  => @$product['manufacturerName'],
                "priorityGroupID"  => @$product['priorityGroupID'],
                "countryOfOriginID"  => @$product['countryOfOriginID'],
                "brandID"  => @$product['brandID'],
                "brandName"  => @$product['brandName'], //today date time
                "width"  => @$product['width'],
                "height"  => @$product['height'],
                "length"  => @$product['length'], // today date
                "lengthInMinutes"  => @$product['lengthInMinutes'],
                "setupTimeInMinutes"  => @$product['setupTimeInMinutes'],
                "cleanupTimeInMinutes"  => @$product['cleanupTimeInMinutes'],
                "walkInService"  => @$product['walkInService'],
                "rewardPointsNotAllowed"  => @$product['rewardPointsNotAllowed'],
                "nonStockProduct"  => @$product['nonStockProduct'],
                "hasSerialNumbers"  => @$product['hasSerialNumbers'],
                "soldInPackages"  => @$product['soldInPackages'],
                "cashierMustEnterPrice"  => @$product['cashierMustEnterPrice'],
                "netWeight"  => $product['netWeight'] == '' ? 0 : $product['netWeight'],
                "grossWeight"  => $product['grossWeight'] == '' ? 0 : $product['grossWeight'],
                "volume"  => @$product['volume'],
                "description"  => $product['description'],
                "longdesc"  => $product['longdesc'],
                "descriptionENG"  => $product['description'],
                "longdescENG"  => $product['longdescENG'],
                "descriptionRUS"  => $product['descriptionRUS'],
                "longdescRUS"  => $product['longdescRUS'],
                "descriptionFIN"  => $product['descriptionFIN'],
                "longdescFIN"  => $product['longdescFIN'],
                "cost"  => $product['cost'],
                "FIFOCost"  => @$product['FIFOCost'],
                "purchasePrice"  => @$product['purchasePrice'],
                "backbarCharges"  => @$product['backbarCharges'],
                "added"  => date('Y-m-d H:i:s', $product['added']),
                "addedByUsername"  => $product['addedByUsername'],
                "lastModified"  => date('Y-m-d H:i:s', $product['lastModified']),
                "lastModifiedByUsername"  => $product['lastModifiedByUsername'],
                "images"  => !empty($product['images']) ? json_encode($product['images'], 1) : '',
                "warehouses"  => !empty($product['warehouses']) ? json_encode($product['warehouses'], 1) : '',
                "variationDescription"  => !empty($product['variationDescription']) ? json_encode($product['variationDescription'], 1) : '',
                "productVariations"  => !empty($product['productVariations']) ? json_encode($product['productVariations'], 1) : '',
                "variationList"  => !empty($product['variationList']) ? json_encode($product['variationList'], 1) : '',
                "parentProductID"  => @$product['parentProductID'],
                "containerID"  => @$product['containerID'],
                "containerName"  => @$product['containerName'],
                "containerCode"  => @$product['containerCode'],
                "containerAmount"  => @$product['containerAmount'],
                "packagingType"  => $product['packagingType'],
                "packages"  => !empty($product['packages']) ? json_encode($product['packages'], 1) : '',
                "productPackages"  => !empty($product['productPackages']) ? json_encode($product['productPackages'], 1) : '',
                "replacementProducts"  => !empty($product['replacementProducts']) ? json_encode($product['replacementProducts'], 1) : '',
                "relatedProducts"  => !empty($product['relatedProducts']) ? json_encode($product['relatedProducts'], 1) : '',
                "relatedFiles"  => !empty($product['relatedFiles']) ? json_encode($product['relatedFiles'], 1) : '',
                "productComponents"  => !empty($product['productComponents']) ? json_encode($product['productComponents'], 1) : '',
                "priceListPrice"  => @$product['priceListPrice'],
                "priceListPriceWithVat"  => @$product['priceListPriceWithVat'],
                "priceCalculationSteps"  => !empty($product['priceCalculationSteps']) ? json_encode($product['priceCalculationSteps'], 1) : '',
                "locationInWarehouse"  => @$product['locationInWarehouse'],
                "locationInWarehouseID"  => @$product['locationInWarehouseID'],
                "locationInWarehouseName"  => @$product['locationInWarehouseName'],
                "locationInWarehouseText"  => @$product['locationInWarehouseText'],
                "reorderMultiple"  => $product['reorderMultiple'],
                "extraField1Title"  => @$product['extraField1Title'],
                "extraField1ID"  => @$product['extraField1ID'],
                "extraField1Code"  => @$product['extra_field1_id'],
                "extraField1Name"  => @$product['extraField1Name'],
                "extraField2Title"  => @$product['extraField2Title'],
                "extraField2ID"  => @$product['extra_field2_id'],
                "extraField2Code"  => @$product['extraField2Code'],
                "extraField2Name"  => @$product['extraField2Name'],
                "extraField3Title"  => @$product['extraField3Title'],
                "extraField3ID"  => @$product['extra_field3_id'],
                "extraField3Code"  => @$product['extraField3Code'],
                "extraField3Name"  => @$product['extraField3Name'],
                "extraField4Title"  => @$product['extraField4Title'],
                "extraField4ID"  => @$product['extra_field4_id'],
                "extraField4Code"  => @$product['extraField4Code'],
                "extraField4Name"  => @$product['extraField4Name'],
                "salesPackageClearBrownGlass"  => @$product['salesPackageClearBrownGlass'],
                "salesPackageGreenOtherGlass"  => @$product['salesPackageGreenOtherGlass'],
                "salesPackagePlasticPpPe"  => @$product['salesPackagePlasticPpPe'],
                "salesPackagePlasticPet"  => @$product['salesPackagePlasticPet'],
                "salesPackageMetalFe"  => @$product['salesPackageMetalFe'],
                "salesPackageMetalAl"  => @$product['salesPackageMetalAl'],
                "salesPackageOtherMetal"  => @$product['salesPackageOtherMetal'],
                "salesPackageCardboard"  => @$product['salesPackageCardboard'],
                "salesPackageWood"  => @$product['salesPackageWood'],
                "groupPackagePaper"  => @$product['groupPackagePaper'],
                "groupPackagePlastic"  => @$product['groupPackagePlastic'],
                "groupPackageMetal"  => @$product['groupPackageMetal'],
                "groupPackageWood"  => @$product['groupPackageWood'],
                "transportPackageWood"  => @$product['transportPackageWood'],
                "transportPackagePlastic"  => @$product['transportPackagePlastic'],
                "transportPackageCardboard"  => @$product['transportPackageCardboard'],
                "registryNumber"  => $product['registryNumber'],
                "alcoholPercentage"  => isset($product['alcoholPercentage']) ? ($product['alcoholPercentage'] == '' ? 0 : $product['alcoholPercentage']) : 0,
                "batches"  => $product['batches'],
                "exciseDeclaration"  => $product['exciseDeclaration'],
                "exciseFermentedProductUnder6"  => $product['exciseFermentedProductUnder6'] == '' ? 0.0 : $product['exciseFermentedProductUnder6'],
                "exciseWineOver6"  => @$product['exciseWineOver6'] == '' ? 0.0 : $product['exciseWineOver6'],
                "exciseFermentedProductOver6"  => @$product['exciseFermentedProductOver6'] == '' ? 0.0 : $product['exciseFermentedProductOver6'],
                "exciseIntermediateProduct"  => @$product['exciseIntermediateProduct'] == '' ? 0.0 : $product['exciseIntermediateProduct'],
                "exciseOtherAlcohol"  => @$product['exciseOtherAlcohol'] == '' ? 0.0 : $product['exciseOtherAlcohol'],
                "excisePackaging"  => @$product['excisePackaging'] == '' ? 0.0 : $product['excisePackaging'],
                "attributes"  => !empty($product['attributes']) ? json_encode($product['attributes'], 1) : '',
                "longAttributes"  => !empty($product['longAttributes']) ? json_encode($product['longAttributes'], 1) : '',
                "parameters"  => !empty($product['parameters']) ? json_encode($product['parameters'], 1) : '',
                "productReplacementHistory"  => !empty($product['productReplacementHistory']) ? json_encode($product['productReplacementHistory'], 1) : '',
            ]
        );
        $this->letsLog->setChronLog($old ? json_encode($old, true) : '', json_encode($change, true), $old  ? "Matrix Product Updated" : "Matrix Product Created");
    }

    protected function variationSaveUpdatePIM($product)
    {

        $old = $this->variation->where('productID', $product['productID'])->first();

        $change = $this->variation->updateOrCreate(
            [
                "productID"  =>  $product['productID']
            ],
            [
                "productID" => $product['productID'],
                "type" => $product['type'],
                "active" => $product['active'],
                "status" => $product['status'],
                "name"  => $product['name'],
                "code"  => $product['code'],
                "code2"  => @$product['code2'],
                "code3"  => @$product['code3'],
                "supplierCode"  => @$product['supplierCode'],
                "code5"  =>  @$product['code5'],
                "code6"  =>  @$product['code6'],
                "code7"  =>  @$product['code7'],
                "code8"  =>  @$product['code8'],
                "groupID"  => $product['groupID'],
                "groupName"  => $product['groupName'],
                "price"  => @$product['price'],
                "priceWithVat"  => @$product['priceWithVat'],
                "displayedInWebshop"  => @$product['displayedInWebshop'],
                "categoryID"  => @$product['categoryID'],
                "categoryName"  => @$product['categoryName'],
                "supplierID"  => @$product['supplierID'],
                "supplierName"  => @$product['supplierName'],
                "unitID"  => @$product['unitID'],
                "unitName"  => @$product['unitName'],
                "taxFree"  => @$product['taxFree'],
                "deliveryTime"  => @$product['deliveryTime'],
                "vatrateID"  => @$product['vatrateID'],
                "vatrate"  => @$product['vatrate'],
                "hasQuickSelectButton"  => @$product['hasQuickSelectButton'],
                "isGiftCard"  => @$product['isGiftCard'],
                "isRegularGiftCard"  => @$product['isRegularGiftCard'],
                "nonDiscountable"  => @$product['nonDiscountable'],
                "nonRefundable"  => @$product['nonRefundable'],
                "manufacturerName"  => @$product['manufacturerName'],
                "priorityGroupID"  => @$product['priorityGroupID'],
                "countryOfOriginID"  => @$product['countryOfOriginID'],
                "brandID"  => @$product['brandID'],
                "brandName"  => @$product['brandName'], //today date time
                "width"  => @$product['width'],
                "height"  => @$product['height'],
                "length"  => @$product['length'], // today date
                "lengthInMinutes"  => @$product['lengthInMinutes'],
                "setupTimeInMinutes"  => @$product['setupTimeInMinutes'],
                "cleanupTimeInMinutes"  => @$product['cleanupTimeInMinutes'],
                "walkInService"  => @$product['walkInService'],
                "rewardPointsNotAllowed"  => @$product['rewardPointsNotAllowed'],
                "nonStockProduct"  => @$product['nonStockProduct'],
                "hasSerialNumbers"  => @$product['hasSerialNumbers'],
                "soldInPackages"  => @$product['soldInPackages'],
                "cashierMustEnterPrice"  => @$product['cashierMustEnterPrice'],
                "netWeight"  => $product['netWeight'] == '' ? 0 : $product['netWeight'],
                "grossWeight"  => $product['grossWeight'] == '' ? 0 : $product['grossWeight'],
                "volume"  => @$product['volume'],
                "description"  => $product['description'],
                "longdesc"  => $product['longdesc'],
                "descriptionENG"  => $product['descriptionENG'],
                "longdescENG"  => $product['longdescENG'],
                "descriptionRUS"  => $product['descriptionRUS'],
                "longdescRUS"  => $product['longdescRUS'],
                "descriptionFIN"  => $product['descriptionFIN'],
                "longdescFIN"  => $product['longdescFIN'],
                "cost"  => $product['cost'],
                "FIFOCost"  => @$product['FIFOCost'],
                "purchasePrice"  => @$product['purchasePrice'],
                "backbarCharges"  => @$product['backbarCharges'],
                "added"  => date('Y-m-d H:i:s', $product['added']),
                "addedByUsername"  => $product['addedByUsername'],
                "lastModified"  => date('Y-m-d H:i:s', $product['lastModified']),
                "lastModifiedByUsername"  => $product['lastModifiedByUsername'],
                "images"  => !empty($product['images']) ? json_encode($product['images'], 1) : '',
                "warehouses"  => !empty($product['warehouses']) ? json_encode($product['warehouses'], 1) : '',
                "variationDescription"  => !empty($product['variationDescription']) ? json_encode($product['variationDescription'], 1) : '',
                "productVariations"  => !empty($product['productVariations']) ? json_encode($product['productVariations'], 1) : '',
                "variationList"  => !empty($product['variationList']) ? json_encode($product['variationList'], 1) : '',
                "parentProductID"  => @$product['parentProductID'],
                "containerID"  => @$product['containerID'],
                "containerName"  => @$product['containerName'],
                "containerCode"  => @$product['containerCode'],
                "containerAmount"  => @$product['containerAmount'],
                "packagingType"  => $product['packagingType'],
                "packages"  => !empty($product['packages']) ? json_encode($product['packages'], 1) : '',
                "productPackages"  => !empty($product['productPackages']) ? json_encode($product['productPackages'], 1) : '',
                "replacementProducts"  => !empty($product['replacementProducts']) ? json_encode($product['replacementProducts'], 1) : '',
                "relatedProducts"  => !empty($product['relatedProducts']) ? json_encode($product['relatedProducts'], 1) : '',
                "relatedFiles"  => !empty($product['relatedFiles']) ? json_encode($product['relatedFiles'], 1) : '',
                "productComponents"  => !empty($product['productComponents']) ? json_encode($product['productComponents'], 1) : '',
                "priceListPrice"  => @$product['priceListPrice'],
                "priceListPriceWithVat"  => @$product['priceListPriceWithVat'],
                "priceCalculationSteps"  => !empty($product['priceCalculationSteps']) ? json_encode($product['priceCalculationSteps'], 1) : '',
                "locationInWarehouse"  => @$product['locationInWarehouse'],
                "locationInWarehouseID"  => @$product['locationInWarehouseID'],
                "locationInWarehouseName"  => @$product['locationInWarehouseName'],
                "locationInWarehouseText"  => @$product['locationInWarehouseText'],
                "reorderMultiple"  => $product['reorderMultiple'],
                "extraField1Title"  => @$product['extraField1Title'],
                "extraField1ID"  => @$product['extraField1ID'],
                "extraField1Code"  => @$product['extraField1Code'],
                "extraField1Name"  => @$product['extraField1Name'],
                "extraField2Title"  => @$product['extraField2Title'],
                "extraField2ID"  => @$product['extraField2ID'],
                "extraField2Code"  => @$product['extraField2Code'],
                "extraField2Name"  => @$product['extraField2Name'],
                "extraField3Title"  => @$product['extraField3Title'],
                "extraField3ID"  => @$product['extraField3ID'],
                "extraField3Code"  => @$product['extraField3Code'],
                "extraField3Name"  => @$product['extraField3Name'],
                "extraField4Title"  => @$product['extraField4Title'],
                "extraField4ID"  => @$product['extraField4ID'],
                "extraField4Code"  => @$product['extraField4Code'],
                "extraField4Name"  => @$product['extraField4Name'],
                "salesPackageClearBrownGlass"  => @$product['salesPackageClearBrownGlass'],
                "salesPackageGreenOtherGlass"  => @$product['salesPackageGreenOtherGlass'],
                "salesPackagePlasticPpPe"  => @$product['salesPackagePlasticPpPe'],
                "salesPackagePlasticPet"  => @$product['salesPackagePlasticPet'],
                "salesPackageMetalFe"  => @$product['salesPackageMetalFe'],
                "salesPackageMetalAl"  => @$product['salesPackageMetalAl'],
                "salesPackageOtherMetal"  => @$product['salesPackageOtherMetal'],
                "salesPackageCardboard"  => @$product['salesPackageCardboard'],
                "salesPackageWood"  => @$product['salesPackageWood'],
                "groupPackagePaper"  => @$product['groupPackagePaper'],
                "groupPackagePlastic"  => @$product['groupPackagePlastic'],
                "groupPackageMetal"  => @$product['groupPackageMetal'],
                "groupPackageWood"  => @$product['groupPackageWood'],
                "transportPackageWood"  => @$product['transportPackageWood'],
                "transportPackagePlastic"  => @$product['transportPackagePlastic'],
                "transportPackageCardboard"  => @$product['transportPackageCardboard'],
                "registryNumber"  => $product['registryNumber'],
                "alcoholPercentage"  => isset($product['alcoholPercentage']) ? ($product['alcoholPercentage'] == '' ? 0 : $product['alcoholPercentage']) : 0,
                "batches"  => $product['batches'],
                "exciseDeclaration"  => $product['exciseDeclaration'],
                "exciseFermentedProductUnder6"  => $product['exciseFermentedProductUnder6'] == '' ? 0.0 : $product['exciseFermentedProductUnder6'],
                "exciseWineOver6"  => @$product['exciseWineOver6'] == '' ? 0.0 : $product['exciseWineOver6'],
                "exciseFermentedProductOver6"  => @$product['exciseFermentedProductOver6'] == '' ? 0.0 : $product['exciseFermentedProductOver6'],
                "exciseIntermediateProduct"  => @$product['exciseIntermediateProduct'] == '' ? 0.0 : $product['exciseIntermediateProduct'],
                "exciseOtherAlcohol"  => @$product['exciseOtherAlcohol'] == '' ? 0.0 : $product['exciseOtherAlcohol'],
                "excisePackaging"  => @$product['excisePackaging'] == '' ? 0.0 : $product['excisePackaging'],
                "attributes"  => !empty($product['attributes']) ? json_encode($product['attributes'], 1) : '',
                "longAttributes"  => !empty($product['longAttributes']) ? json_encode($product['longAttributes'], 1) : '',
                "parameters"  => !empty($product['parameters']) ? json_encode($product['parameters'], 1) : '',
                "productReplacementHistory"  => !empty($product['productReplacementHistory']) ? json_encode($product['productReplacementHistory'], 1) : '',
            ]
        );
        $this->letsLog->setChronLog($old ? json_encode($old, true) : '', json_encode($change, true), $old  ? "Variation Product Updated" : "Variation Product Created");
    }

    public function getTempUpdateDate($id)
    {
        $date = TempDate::where("id", $id)->first();
        if ($date) {
            return strtotime($date->datetime);
        }
        return 0;
    }


    public function getLastUpdateDate($type = 'changed')
    {
        $erplyDate = ErplySyncDate::latest()->first();

        if (!$erplyDate) {
            return 0;
        }

        // Pick correct columns based on type
        if ($type === 'addedSince') {
            $matrixDate = $erplyDate->matrix_product_added;
            $variationDate = $erplyDate->variation_product_added;
        } else { // 'changed' or 'modified'
            $matrixDate = $erplyDate->matrix_product_last_modified;
            $variationDate = $erplyDate->variation_product_last_modified;
        }

        // Handle nulls safely
        if (!$matrixDate && !$variationDate) {
            return 0;
        }

        if (!$matrixDate) {
            return $variationDate;
        }

        if (!$variationDate) {
            return $matrixDate;
        }

        // Return the smaller (earlier) datetime
        $l = $matrixDate > $variationDate ? $matrixDate : $variationDate;

        return strtotime($l);
    }

    // public function getLastUpdateDate()
    // {

    // $erplyDate = ErplySyncDate::latest()->first();
    // if(!$erplyDate){
    //     return 0;
    // }


        // echo "im call";
        //  $latest = $this->variation->where('clientCode',  $this->api->client->clientCode)->orderBy('lastModified', 'desc')->first();
        // if($latest){
        //     return strtotime($latest->lastModified);
        // }else{
        //     $latest = $this->matrix->where('clientCode',  $this->api->client->clientCode)->orderBy('lastModified', 'desc')->first();
        //     if($latest){
        //         return strtotime($latest->lastModified);
        //     }
        // }

        // $erplyFlag = ($this->api->client->clientCode == 607655) ? '' : 'PSW';
        // $vlatest = $this->variationLive->where('ERPLYFLAG', $erplyFlag)->orderBy('productAdded', 'desc')->first();
        // $mlatest = $this->liveProductMatrix->where('ERPLYFLAG', $erplyFlag)->orderBy('productAdded', 'desc')->first();
        //  echo $vlatest->lastModified."  ".$mlatest->lastModified;
        //  die;
        // if ($vlatest) {
        //     $l = $mlatest->productAdded > $vlatest->productAdded ? $mlatest->productAdded : $vlatest->productAdded;
        //     return strtotime($l);
        // }
    //     return 0; // strtotime($latest);
    // }

    public function getProductByDateFilter($req)
    {
        // echo "im call";
        $latest = UserOperationLog::where('clientCode', $this->api->client->clientCode)->where('tableName', 'variation_products_live')->orderBy('timestamp', 'desc')->first();
        if ($latest) {

            return strtotime($latest->timestamp);
        }
        return 0; // strtotime($latest);
    }

    public function getLastUpdateDateDelete($table)
    {
        // echo "im call";
        $latest = UserOperationLog::where('clientCode', $this->api->client->clientCode)->where('tableName', $table)->orderBy('timestamp', 'desc')->first();
        if ($latest) {

            return strtotime($latest->timestamp);
        }
        return 0; // strtotime($latest);
    }

    public function deleteRecords($res, $clientCode)
    {

        //    dd("Hello im from get product service class");
        foreach ($res as $l) {
            $this->handleOperationLog($l, $clientCode,  $l['itemID']);
            if ($l['operation'] == 'delete') {
                VariationProduct::deleteProduct($clientCode, $l["itemID"]);
                MatrixProduct::deleteProduct($clientCode, $l["itemID"]);
            }
        }
    }
}
