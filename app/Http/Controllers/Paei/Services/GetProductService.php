<?php

namespace App\Http\Controllers\Paei\Services;

use App\Classes\UserLogger;
use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\{MatrixProduct, ProductGroup, Supplier, TempDate, UserOperationLog, VariationProduct, Warehouse};
use App\Models\PswClientLive\Local\{LiveItemByLocation, LiveProductMatrix, LiveProductVariation};
use App\Traits\UserOperationTrait;

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

            // dd($p);

            if ($p['type'] == "MATRIX") {

                $this->matrixSaveUpdate($p, $this->api->client->clientCode);
            } else {


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
                'id ' => $p['productID'] ?? null,
                'message' => "Product with ID {$p['productID']} processed as " . ($p['type'] ?? 'Unknown Type')
            ];
        }

        dump($responseData);


        return response()->json(['status' => 200, 'message' => "Product fetched Successfully."]);
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


    private function parseAttributes($product)
    {
        if (!empty($product['attributes']) && is_array($product['attributes'])) {
            return array_column($product['attributes'], 'attributeValue', 'attributeName');
        }
        return [];
    }

    private function normalizeStatus($status)
    {
        $webEnabled = 1;
        $erplyEnabled = 1;
        $erplyDeleted = 0;

        if ($status === 'not_for_sale') {
            $webEnabled = 0;
            $erplyEnabled = 0;
        } elseif ($status === 'archived') {
            $webEnabled = 0;
            $erplyEnabled = 0;
        }

        return compact('webEnabled', 'erplyEnabled', 'erplyDeleted');
    }

    private function decodeStoreLocation($json)
    {
        $data = json_decode($json ?? '', true);
        return $this->nullIfEmpty($data[0]['location'] ?? null);
    }

    private function sumSOH($json)
    {
        $total = 0;
        $data = json_decode($json ?? '', true);

        if (is_array($data)) {
            foreach ($data as $row) {
                $total += (float) ($row['SOH'] ?? 0);
            }
        }

        return $this->nullIfEmpty($total);
    }

    private function updateSOH($icsc, $defaultStore, $secondaryStore, $sohDefault, $sohSecondary, $productId)
    {
        LiveItemByLocation::updateOrCreate(
            ['icsc' => $icsc, 'warehouse' => $defaultStore],
            [
                'icsc' => $icsc,
                'AvailablePhysical' => $sohDefault,
                'warehouse' => $defaultStore,
                'item' => $productId
            ]
        );

        LiveItemByLocation::updateOrCreate(
            ['icsc' => $icsc, 'warehouse' => $secondaryStore],
            [
                'icsc' => $icsc,
                'AvailablePhysical' => $sohSecondary,
                'warehouse' => $secondaryStore,
                'item' => $productId
            ]
        );
    }

    private function nullIfEmpty($value)
    {
        return $value === '' ? null : $value;
    }

    protected function matrixSaveUpdate(array $product, int $clientCode)
    {


        $erplyFlag = ($clientCode == 607655) ? null : 'PSW';

        $old = $this->liveProductMatrix
            ->where('ERPLYFLAG', $erplyFlag)
            ->where('erplyID', $product['productID'])
            ->first();

        $school = ProductGroup::where('clientCode', $clientCode)
            ->where('productGroupID', $product['groupID'])
            ->first();

        $attr = $this->parseAttributes($product);
        $statusData = $this->normalizeStatus($product['status'] ?? null);

        $webEnabled = $statusData['webEnabled'];
        $erplyEnabled = $statusData['erplyEnabled'];
        $erplyDeleted = $statusData['erplyDeleted'];

        $attrLower = array_change_key_case($attr, CASE_LOWER);

        $primaryJson   = $attrLower['primaryjson'] ?? null;
        $secondaryJson = $attrLower['secondaryjson'] ?? null;

        $defaultStore   = $this->decodeStoreLocation($primaryJson);
        $secondaryStore = $this->decodeStoreLocation($secondaryJson);

        $sohDefault   = $this->sumSOH($primaryJson);
        $sohSecondary = $this->sumSOH($secondaryJson);

        // ✅ KEEP ALL YOUR ORIGINAL FIELD MAPPING
        $fields = [
            'erplyID' => $product['productID'],
            'type' => $product['type'] ?? 'MATRIX',
            'productAdded' => date('Y-m-d H:i:s', $product['added']),

            'SchoolID' => $this->nullIfEmpty($attr['SchoolID'] ?? $product['groupID']),
            'SchoolName' => $this->nullIfEmpty($attr['SchoolName'] ?? ($school->name ?? null)),
            'CustomerGroup' => $this->nullIfEmpty($attr['CustomerGroup'] ?? null),

            'ERPLYSKU' => $this->nullIfEmpty($attr['ERPLYSKU'] ?? $product['code']),
            'WEBSKU' => $product['productID'],
            'ITEMID' => $this->nullIfEmpty($attr['ITEMID'] ?? $product['productID']),
            'ItemName' => $this->nullIfEmpty($attr['Matrix_Product_Name'] ?? $product['name']),

            'RetailSalesPrice' => $product['priceWithVat'] ?? 0,

            'DefaultStore' => $defaultStore,
            'SecondaryStore' => $secondaryStore,

            'ERPLYFLAG' => $erplyFlag,
            'WebEnabled' => $webEnabled,
            'erplyEnabled' => $erplyEnabled,
            'erplyDeleted' => $erplyDeleted,

            'erplyAttributes' => json_encode($product['attributes'] ?? []),
            'erplyStatus' => $product['status'] ?? null
        ];

        $change = $this->liveProductMatrix->updateOrCreate(
            ['ERPLYFLAG' => $erplyFlag, 'erplyID' => $product['productID']],
            $fields
        );


        $this->updateSOH(
            $attr['ICSC'] ?? null,
            $defaultStore,
            $secondaryStore,
            $sohDefault,
            $sohSecondary,
            $product['productID']
        );

        $this->letsLog->setChronLog(
            $old ? json_encode($old) : '',
            json_encode($change),
            $old ? "Matrix Product Updated" : "Matrix Product Created"
        );
    }

    protected function variationSaveUpdate($product, $clientCode)
    {
        $erplyFlag = ($clientCode == 607655) ? '' : 'PSW';

        $attr = $this->parseAttributes($product);
        $statusData = $this->normalizeStatus($product['status'] ?? null);

        $webEnabled = $statusData['webEnabled'];
        $erplyEnabled = $statusData['erplyEnabled'];
        $erplyDeleted = $statusData['erplyDeleted'];

        $attrLower = array_change_key_case($attr, CASE_LOWER);

        $primaryJson   = $attrLower['primaryjson'] ?? null;
        $secondaryJson = $attrLower['secondaryjson'] ?? null;

        $defaultStore   = $this->decodeStoreLocation($primaryJson);
        $secondaryStore = $this->decodeStoreLocation($secondaryJson);

        $sohDefault   = $this->sumSOH($primaryJson);
        $sohSecondary = $this->sumSOH($secondaryJson);

        $old = $this->variationLive
            ->where('ERPLYFLAG', $erplyFlag)
            ->where('erplyID', $product['productID'])
            ->first();

        // ✅ KEEP ARRAY STYLE (AS YOU WANTED)
        $fields = [
            "ERPLYFLAG" => $erplyFlag,
            "erplyID"   => $product['productID'],

            "ItemName" => $product['name'] ?? null,
            "ERPLYSKU" => $product['code'] ?? null,
            "RetailSalesPrice" => $product['priceWithVat'] ?? 0,

            "DefaultStore" => $defaultStore,
            "SecondaryStore" => $secondaryStore,

            "WebEnabled" => $webEnabled,
            "erplyEnabled" => $erplyEnabled,
            "erplyDeleted" => $erplyDeleted,

            "primaryJson" => $primaryJson,
            "secondaryJson" => $secondaryJson,

            "erplyAttributes" => json_encode($product['attributes'] ?? []),
            "erplyStatus" => $product['status'] ?? null,
        ];

        $change = $this->variationLive->updateOrCreate(
            [
                "ERPLYFLAG" => $erplyFlag,
                "erplyID"   => $product['productID']
            ],
            $fields
        );

        $this->updateSOH(
            $attr['ICSC'] ?? null,
            $defaultStore,
            $secondaryStore,
            $sohDefault,
            $sohSecondary,
            $product['productID']
        );

        $this->letsLog->setChronLog(
            $old ? json_encode($old) : '',
            json_encode($change),
            $old ? "Variation Product Updated" : "Variation Product Created"
        );
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

    public function getLastUpdateDate()
    {
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

        $erplyFlag = ($this->api->client->clientCode == 607655) ? '' : 'PSW';
        $vlatest = $this->variationLive->where('ERPLYFLAG', $erplyFlag)->orderBy('productAdded', 'desc')->first();
        $mlatest = $this->liveProductMatrix->where('ERPLYFLAG', $erplyFlag)->orderBy('productAdded', 'desc')->first();
        //  echo $vlatest->lastModified."  ".$mlatest->lastModified;
        //  die;
        if ($vlatest) {
            $l = $mlatest->productAdded > $vlatest->productAdded ? $mlatest->productAdded : $vlatest->productAdded;
            return strtotime($l);
        }
        return 0; // strtotime($latest);
    }

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
