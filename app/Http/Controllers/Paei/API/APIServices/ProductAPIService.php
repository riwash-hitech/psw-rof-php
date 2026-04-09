<?php

namespace App\Http\Controllers\Paei\API\APIServices;

use App\Classes\Except;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\InventoryRegistration;
use App\Models\PAEI\MatrixProduct;
use App\Models\PAEI\VariationProduct;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductVariation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseTrait; 

class ProductAPIService
{
    use ResponseTrait;

    protected $matrix;
    protected $variation;
    protected $inventory;
    protected $api;

    public function __construct(MatrixProduct $mp, VariationProduct $vp, InventoryRegistration $ir, EAPIService $api)
    {
        $this->matrix = $mp;
        $this->variation = $vp;
        $this->inventory = $ir;
        $this->api = $api;
    }

    public function getByProductID($id)
    {
        $mp = $this->matrix->where('clientCode', $this->api->client->clientCode)->where("productID", $id)->get();
        $vp = $this->variation->where('clientCode',  $this->api->client->clientCode)->where("productID", $id)->get();
        $res = $this->packaging($mp, $vp);
        return response()->json(["status" => 200, "records" => $res]);
        // $product = $this->variation->findOrfail($)
    }

    public function getByProductCode($req)
    {

        $mp = $this->matrix->filter($req)->get();

        $vp = $this->variation->filter($req)->get();

        $res = $this->packaging($mp, $vp);
        $res = $this->customPagination($req, $res);
        return response()->json(["status" => 200, "records" => $res]);
        // $product = $this->variation->findOrfail($)
    }

    public function getByProductIDs($request)
    {
        // $ids = mb_split(",", $request->productIDs);
        if (isset($request->strictFilter) == 0) {
            $request->strictFilter = true;
        }

        $limit = $request->recordsOnPage ? $request->recordsOnPage : 20;


        $page = $request->page ? $request->page : 1;
        // echo $limit. ' '.$page ;
        // die;
        $requestData = $request->except(Except::$except);

        $direction = $request->direction ? $request->direction : "asc";
        $sort_by = $request->sort_by ? $request->sort_by : 'name';



        // $requestData = $request->except('sort_by', 'direction', 'pagination', 'page');
        // $select = $request->select ? $request->select
        //             : 
        //             "id, productID, type, active, status, name, code, code2, code3, supplierCode, code5, code6, code7, code8, groupID, groupName, price, priceWithVat, displayedInWebshop, categoryID, categoryName, supplierID, supplierName, unitID, unitName, taxFree, deliveryTime, vatrateID, vatrate, hasQuickSelectButton, isGiftCard, isRegularGiftCard, nonDiscountable, nonRefundable, manufacturerName, priorityGroupID, countryOfOriginID, brandID, brandName, width, height, length, lengthInMinutes, setupTimeInMinutes, cleanupTimeInMinutes, walkInService, rewardPointsNotAllowed, nonStockProduct, hasSerialNumbers, soldInPackages, cashierMustEnterPrice, netWeight, grossWeight, volume, description, longdesc, descriptionENG, longdescENG, descriptionRUS, longdescRUS, descriptionFIN, longdescFIN, cost, FIFOCost, purchasePrice, backbarCharges, added, addedByUsername, lastModified, lastModifiedByUsername, images, warehouses, variationDescription, productVariations, variationList, parentProductID, containerID, containerName, containerCode, containerAmount, packagingType, packages, productPackages, replacementProducts, relatedProducts, relatedFiles, productComponents, priceListPrice, priceListPriceWithVat, priceCalculationSteps, locationInWarehouse, locationInWarehouseID, locationInWarehouseName, locationInWarehouseText, reorderMultiple, extraField1Title, extraField1ID, extraField1Code, extraField1Name, extraField2Title, extraField2ID, extraField2Code, extraField2Name, extraField3Title, extraField3ID,extraField3Code,extraField3Name,extraField4Title,extraField4ID,extraField4Code,extraField4Name,salesPackageClearBrownGlass,salesPackageGreenOtherGlass,salesPackagePlasticPpPe, salesPackagePlasticPet,salesPackageMetalFe,salesPackageMetalAl,salesPackageOtherMetal, salesPackageCardboard,salesPackageWood, groupPackagePaper, groupPackagePlastic, groupPackageMetal, groupPackageWood, transportPackageWood, transportPackagePlastic,transportPackageCardboard, registryNumber, alcoholPercentage, batches,exciseDeclaration, exciseFermentedProductUnder6, exciseWineOver6, exciseFermentedProductOver6, exciseIntermediateProduct,exciseOtherAlcohol,excisePackaging,attributes,longAttributes, parameters,productReplacementHistory, created_at, updated_at ";
        // print_r(explode(",",$request->select));

        $select = $request->select ? explode(",", $request->select) :
            array(
                'id',
                'productID',
                'type',
                'active',
                'status',
                'name',
                'code',
                'code2',
                'code3',
                'supplierCode',
                'code5',
                'code6',
                'code7',
                'code8',
                'groupID',
                'groupName',
                'price',
                'priceWithVat',
                'displayedInWebshop',
                'categoryID',
                'categoryName',
                'supplierID',
                'supplierName',
                'unitID',
                'unitName',
                'taxFree',
                'deliveryTime',
                'vatrateID',
                'vatrate',
                'hasQuickSelectButton',
                'isGiftCard',
                'isRegularGiftCard',
                'nonDiscountable',
                'nonRefundable',
                'manufacturerName',
                'priorityGroupID',
                'countryOfOriginID',
                'brandID',
                'brandName',
                'width',
                'height',
                'length',
                'lengthInMinutes',
                'setupTimeInMinutes',
                'cleanupTimeInMinutes',
                'walkInService',
                'rewardPointsNotAllowed',
                'nonStockProduct',
                'hasSerialNumbers',
                'soldInPackages',
                'cashierMustEnterPrice',
                'netWeight',
                'grossWeight',
                'volume',
                'description',
                'longdesc',
                'descriptionENG',
                'longdescENG',
                'descriptionRUS',
                'longdescRUS',
                'descriptionFIN',
                'longdescFIN',
                'cost',
                'FIFOCost',
                'purchasePrice',
                'backbarCharges',
                'added',
                'addedByUsername',
                'lastModified',
                'lastModifiedByUsername',
                'images',
                'warehouses',
                'variationDescription',
                'productVariations',
                'variationList',
                'parentProductID',
                'containerID',
                'containerName',
                'containerCode',
                'containerAmount',
                'packagingType',
                'packages',
                'productPackages',
                'replacementProducts',
                'relatedProducts',
                'relatedFiles',
                'productComponents',
                'priceListPrice',
                'priceListPriceWithVat',
                'priceCalculationSteps',
                'locationInWarehouse',
                'locationInWarehouseID',
                'locationInWarehouseName',
                'locationInWarehouseText',
                'reorderMultiple',
                'extraField1Title',
                'extraField1ID',
                'extraField1Code',
                'extraField1Name',
                'extraField2Title',
                'extraField2ID',
                'extraField2Code',
                'extraField2Name',
                'extraField3Title',
                'extraField3ID',
                'extraField3Code',
                'extraField3Name',
                'extraField4Title',
                'extraField4ID',
                'extraField4Code',
                'extraField4Name',
                'salesPackageClearBrownGlass',
                'salesPackageGreenOtherGlass',
                'salesPackagePlasticPpPe',
                'salesPackagePlasticPet',
                'salesPackageMetalFe',
                'salesPackageMetalAl',
                'salesPackageOtherMetal',
                'salesPackageCardboard',
                'salesPackageWood',
                'groupPackagePaper',
                'groupPackagePlastic',
                'groupPackageMetal',
                'groupPackageWood',
                'transportPackageWood',
                'transportPackagePlastic',
                'transportPackageCardboard',
                'registryNumber',
                'alcoholPercentage',
                'batches',
                'exciseDeclaration',
                'exciseFermentedProductUnder6',
                'exciseWineOver6',
                'exciseFermentedProductOver6',
                'exciseIntermediateProduct',
                'exciseOtherAlcohol',
                'excisePackaging',
                'attributes',
                'longAttributes',
                'parameters',
                'productReplacementHistory',
                'created_at',
                'updated_at'
            );


        $results = DB::table('newsystem_product_matrix')
            ->select($select)->where(function ($q) use ($requestData, $request) {
                $q->where('clientCode', $this->api->client->clientCode);
                foreach ($requestData as $keys => $value) {
                    if ($value != null && "$value" != 'undefined') {

                        if ($request->strictFilter == true) {

                            if ($keys == 'productIDs') {
                                $q->whereIn(substr($keys, 0, -1), explode(",", $value));
                            } else {
                                $q->Where($keys, $value);
                            }
                        } else {
                            if ($keys == 'productIDs') {
                                $q->whereIn(substr($keys, 0, -1), explode(",", $value));
                            } else {
                                $q->Where($keys, 'LIKE', '%' . $value . '%');
                            }
                        }
                        // 'like', '%' . $value . '%'); 
                    }
                }
            })
            // ->where('column1', 'value1')
            ->unionAll(
                DB::table('newsystem_product_variations')
                    ->select($select)->where(function ($q) use ($requestData, $request) {
                        $q->where('clientCode', $this->api->client->clientCode);
                        // if($request->includeMatrixVariations == 1){
                        foreach ($requestData as $keys => $value) {
                            if ($value != null && "$value" != 'undefined') {
                                if ($request->strictFilter == true) {
                                    if ($keys == 'productIDs') {
                                        $q->whereIn(substr($keys, 0, -1), explode(",", $value));
                                    } else {
                                        $q->Where($keys, $value);
                                    }
                                } else {
                                    if ($keys == 'productIDs') {
                                        $q->whereIn(substr($keys, 0, -1), explode(",", $value));
                                    } else {
                                        $q->Where($keys, 'LIKE', '%' . $value . '%');
                                    }
                                }
                                // 'like', '%' . $value . '%'); 
                            }
                        }
                        // }else{
                        //     $q->where('type', 'MATRIX');
                        // }
                    })

            )->orderBy($sort_by, $direction)
            // ->get();
            ->paginate($limit);
        // return    

        return $this->successWithData($results);
    }

    public function getInventoryRegistration($req)
    {
        $id = $req->productID;

        $results = $this->inventory
            ->with('warehouse')
            // ->join('newsystem_warehouse_locations', 'newsystem_warehouse_locations.warehouseID', 'newsystem_inventory_registrations.warehouseID')
            ->whereJsonContains('rows', [['productID' => (int)$id]])->get();

        if (!$results) {
            return response()->json(['status' => 400, "records" => "Inventory Not Found!"]);
        }
        return response()->json(['status' => 200, "records" => $results]);
    }

    public function getProductShort($request)
    {
        return $this->getProductShortV2($request);
        die;
        // foreach($request->toArray() as $r){
        //     if($r)
        // }
        if (isset($request->strictFilter) == 0) {
            $request->strictFilter = false;
        }
        if (isset($request->direction) == 0) {
            $request->direction = 'asc';
        }
        if (isset($request->sort_by) == 0) {
            $request->sort_by = 'name';
        }


        $limit = $request->recordsOnPage ? $request->recordsOnPage : 20;


        // $page = $request->page ? $request->page : 1;
        // echo $limit. ' '.$page ;
        // die;
        $requestData = $request->except(Except::$except);

        $direction = $request->direction ? $request->direction : 'asc';
        $sort_by = $request->sort_by ? $request->sort_by : 'name';





        // $requestData = $request->except('sort_by', 'direction', 'pagination', 'page');
        // $select = $request->select ? $request->select
        //             : 
        //             "id, productID, type, active, status, name, code, code2, code3, supplierCode, code5, code6, code7, code8, groupID, groupName, price, priceWithVat, displayedInWebshop, categoryID, categoryName, supplierID, supplierName, unitID, unitName, taxFree, deliveryTime, vatrateID, vatrate, hasQuickSelectButton, isGiftCard, isRegularGiftCard, nonDiscountable, nonRefundable, manufacturerName, priorityGroupID, countryOfOriginID, brandID, brandName, width, height, length, lengthInMinutes, setupTimeInMinutes, cleanupTimeInMinutes, walkInService, rewardPointsNotAllowed, nonStockProduct, hasSerialNumbers, soldInPackages, cashierMustEnterPrice, netWeight, grossWeight, volume, description, longdesc, descriptionENG, longdescENG, descriptionRUS, longdescRUS, descriptionFIN, longdescFIN, cost, FIFOCost, purchasePrice, backbarCharges, added, addedByUsername, lastModified, lastModifiedByUsername, images, warehouses, variationDescription, productVariations, variationList, parentProductID, containerID, containerName, containerCode, containerAmount, packagingType, packages, productPackages, replacementProducts, relatedProducts, relatedFiles, productComponents, priceListPrice, priceListPriceWithVat, priceCalculationSteps, locationInWarehouse, locationInWarehouseID, locationInWarehouseName, locationInWarehouseText, reorderMultiple, extraField1Title, extraField1ID, extraField1Code, extraField1Name, extraField2Title, extraField2ID, extraField2Code, extraField2Name, extraField3Title, extraField3ID,extraField3Code,extraField3Name,extraField4Title,extraField4ID,extraField4Code,extraField4Name,salesPackageClearBrownGlass,salesPackageGreenOtherGlass,salesPackagePlasticPpPe, salesPackagePlasticPet,salesPackageMetalFe,salesPackageMetalAl,salesPackageOtherMetal, salesPackageCardboard,salesPackageWood, groupPackagePaper, groupPackagePlastic, groupPackageMetal, groupPackageWood, transportPackageWood, transportPackagePlastic,transportPackageCardboard, registryNumber, alcoholPercentage, batches,exciseDeclaration, exciseFermentedProductUnder6, exciseWineOver6, exciseFermentedProductOver6, exciseIntermediateProduct,exciseOtherAlcohol,excisePackaging,attributes,longAttributes, parameters,productReplacementHistory, created_at, updated_at ";
        // print_r(explode(",",$request->select));

        $select = $request->select ? explode(",", $request->select) :
            array(
                'id',
                'productID',
                'type',
                'active',
                'status',
                'name',
                'code',
                'code2',
                'code3',
                'supplierCode',
                'code5',
                'code6',
                'code7',
                'code8',
                'groupID',
                'groupName',
                'price',
                'priceWithVat',
                'displayedInWebshop',
                'categoryID',
                'categoryName',
                'supplierID',
                'supplierName',
                'unitID',
                'unitName',
                'taxFree',
                'deliveryTime',
                'vatrateID',
                'vatrate',
                'hasQuickSelectButton',
                'isGiftCard',
                'isRegularGiftCard',
                'nonDiscountable',
                'nonRefundable',
                'manufacturerName',
                'priorityGroupID',
                'countryOfOriginID',
                'brandID',
                'brandName',
                'width',
                'height',
                'length',
                'lengthInMinutes',
                'setupTimeInMinutes',
                'cleanupTimeInMinutes',
                'walkInService',
                'rewardPointsNotAllowed',
                'nonStockProduct',
                'hasSerialNumbers',
                'soldInPackages',
                'cashierMustEnterPrice',
                'netWeight',
                'grossWeight',
                'volume',
                'description',
                'longdesc',
                'descriptionENG',
                'longdescENG',
                'descriptionRUS',
                'longdescRUS',
                'descriptionFIN',
                'longdescFIN',
                'cost',
                'FIFOCost',
                'purchasePrice',
                'backbarCharges',
                'added',
                'addedByUsername',
                'lastModified',
                'lastModifiedByUsername',
                'images',
                'warehouses',
                'variationDescription',
                'productVariations',
                'variationList',
                'parentProductID',
                'containerID',
                'containerName',
                'containerCode',
                'containerAmount',
                'packagingType',
                'packages',
                'productPackages',
                'replacementProducts',
                'relatedProducts',
                'relatedFiles',
                'productComponents',
                'priceListPrice',
                'priceListPriceWithVat',
                'priceCalculationSteps',
                'locationInWarehouse',
                'locationInWarehouseID',
                'locationInWarehouseName',
                'locationInWarehouseText',
                'reorderMultiple',
                'extraField1Title',
                'extraField1ID',
                'extraField1Code',
                'extraField1Name',
                'extraField2Title',
                'extraField2ID',
                'extraField2Code',
                'extraField2Name',
                'extraField3Title',
                'extraField3ID',
                'extraField3Code',
                'extraField3Name',
                'extraField4Title',
                'extraField4ID',
                'extraField4Code',
                'extraField4Name',
                'salesPackageClearBrownGlass',
                'salesPackageGreenOtherGlass',
                'salesPackagePlasticPpPe',
                'salesPackagePlasticPet',
                'salesPackageMetalFe',
                'salesPackageMetalAl',
                'salesPackageOtherMetal',
                'salesPackageCardboard',
                'salesPackageWood',
                'groupPackagePaper',
                'groupPackagePlastic',
                'groupPackageMetal',
                'groupPackageWood',
                'transportPackageWood',
                'transportPackagePlastic',
                'transportPackageCardboard',
                'registryNumber',
                'alcoholPercentage',
                'batches',
                'exciseDeclaration',
                'exciseFermentedProductUnder6',
                'exciseWineOver6',
                'exciseFermentedProductOver6',
                'exciseIntermediateProduct',
                'exciseOtherAlcohol',
                'excisePackaging',
                'attributes',
                'longAttributes',
                'parameters',
                'productReplacementHistory',
                'created_at',
                'updated_at'
            );
        

        //query caching 
        // $cacheKey = 'my_query_' . md5(json_encode($request). $request->page . $sort_by . $direction . $limit);
        // if (Cache::has($cacheKey)) {
        //     $results = Cache::get($cacheKey);
        //     return $this->successWithData($results);
        // }

        //Old Working Query
        // $results = DB::table('newsystem_product_matrix')
        //             ->where('clientCode', $this->api->client->clientCode)
        //             ->select($select)->where(function ($q) use ($requestData, $request) {
        //                 foreach ($requestData as $keys => $value) {
        //                     if ($value != null && "$value" != 'undefined') { 
        //                         if($request->strictFilter == true){
        //                             $q->Where($keys, $value);
        //                         }else{
        //                             $q->Where($keys, 'LIKE', '%'.$value.'%');
        //                         }
        //                         // 'like', '%' . $value . '%'); 
        //                     }
        //                 }
        //             }) 
        //             // ->where('column1', 'value1')
        //             ->unionAll(DB::table('newsystem_product_variations')
        //                     ->where('clientCode', $this->api->client->clientCode)
        //                     ->select($select)->where(function ($q) use ($requestData, $request) {
        //                         if($request->includeMatrixVariations == 1){
        //                             foreach ($requestData as $keys => $value) {
        //                                 if ($value != null && "$value" != 'undefined') { 
        //                                     if($request->strictFilter == true){
        //                                         $q->Where($keys, $value);
        //                                     }else{
        //                                         $q->Where($keys, 'LIKE', '%'.$value.'%');
        //                                     }
        //                                     // 'like', '%' . $value . '%'); 
        //                                 }
        //                             }
        //                         }else{
        //                             $q->where('type', 'MATRIX');
        //                         }
        //                     }) 

        //             )->orderBy($sort_by, $direction) 
        //             // ->get();
        //             ->paginate($limit);     
        // return    
        //end old working query

        $subqueryMatrix = DB::table('newsystem_product_matrix')
            ->select($select)
            ->where('clientCode', $this->api->client->clientCode)
            ->where(function ($q) use ($requestData, $request) {
                foreach ($requestData as $keys => $value) {
                    if (!is_null($value) && $value !== 'undefined') {
                        if ($request->strictFilter == true) {
                            $q->where($keys, $value);
                        } else {
                            $q->orWhere($keys, 'LIKE', '%' . $value . '%');
                        }
                    }
                }
            });

        $subqueryVariations = null;
        if ($request->includeMatrixVariations == 1) {
            $subqueryVariations = DB::table('newsystem_product_variations')
                ->select($select)
                ->where('clientCode', $this->api->client->clientCode)
                ->where('deleted', 0)
                ->where(function ($q) use ($requestData, $request) {
                    foreach ($requestData as $keys => $value) {
                        if (!is_null($value) && $value !== 'undefined') {
                            if ($request->strictFilter == true) {
                                $q->where($keys, $value);
                            } else {
                                $q->orWhere($keys, 'LIKE', '%' . $value . '%');
                            }
                        }
                    }
                });
        }

        $query = DB::query()
            ->fromSub($subqueryMatrix, 'matrix')
            ->select($select);
            // ->orderBy($sort_by, $direction);

        if ($subqueryVariations) {
            $query->union($subqueryVariations);
        }
        // Apply the ORDER BY clause
        $query->orderBy($sort_by, $direction);
        $results = $query->paginate($limit);
        // Cache::put($cacheKey, $results, 60 * 24 );
        return $this->successWithData($results);
        // return response()->json(["status"=>200, "success" => true, "records" => collect($results)]);                          
        // 
        // $query = "SELECT $select FROM newsystem_product_matrix ";
        // if($request->includeMatrixVariations == 1){
        //     $query = "SELECT $select  FROM (SELECT * FROM newsystem_product_matrix UNION ALL SELECT * FROM newsystem_product_variations) as products";
        // }

        // $filter = array();
        // foreach ($requestData as $keys => $value) {
        //     if ($value != null) {
        //         if($keys == 'price'){
        //             $pp = mb_split(",",$value);
        //             $filter[] = $keys.' > '.$pp[0];
        //             $filter[] = $keys.' < '.$pp[1];
        //             // $q->Where($keys, '>', $pp[0])->where($keys, '<', $pp[1]);
        //         }else{
        //             if($request->strictFilter == true){
        //                 $filter[] = $keys.' = '.$value;
        //             }else{ 
        //                 $filter[] = $keys.' LIKE "%'.$value.'%"'; 
        //             } 
        //         }

        //     }
        // }
        // if(count($filter) > 0){
        //     $query .= " where ". implode(' AND ', $filter);
        // }

        // //for order by 
        // $query .= " order by ".$sort_by .' '.$direction;// " limit ".$limit . " offset ".$page == 1 ? 0 : $page * $limit;

        // //for pagination
        // $skip = $page == 1 ? 0 : ($page-1) * $limit;
        // $query .= " limit ".$limit ." offset ".$skip;
        // //  print_r($query);
        // //  die;
        // $matrix = DB::select($query); 
        // // print_r($matrix);
        // // die;
        // $products = $this->customPagination($request, $matrix);

        // return response()->json(["status"=>200, "records" => $products]);


    }

    public function getProductShortV2($request)
    {
        
        if (isset($request->strictFilter) == 0) {
            $request->strictFilter = false;
        }
        if (isset($request->direction) == 0) {
            $request->direction = 'asc';
        }
        if (isset($request->sort_by) == 0) {
            $request->sort_by = 'updated_at';
        }

        $limit = $request->recordsOnPage ? $request->recordsOnPage : 20;
 
        $requestData = $request->except(Except::$except);

        $direction = $request->direction ? $request->direction : 'asc';
        $sort_by = $request->sort_by ? $request->sort_by : 'ItemName';

 
        $select = $request->select ? explode(",", $request->select) :
            array(
                'erplyID',
                'erplyPending',
                'SchoolID',
                'type',
                'SchoolName',
                'ERPLYSKU',
                'WEBSKU',
                'ITEMID',
                'ItemName',
                'ColourID',
                'ColourName',
                'SizeID',
                'CONFIGID',
                'ConfigName',
                'EANBarcode',
                'RetailSalesPrice',
                'DefaultStore',
                'SecondaryStore',
                'ERPLYFLAG',
                'WebEnabled',
                'erplyEnabled',
                'Category_Name',
                'updated_at',
            );
         

        $subqueryMatrix = LiveProductMatrix:://DB::connection("mysql2")->table('newsystem_product_matrix_live')
            select($select)
            // ->where('clientCode', $this->api->client->clientCode)
            ->where(function ($q) use ($requestData, $request) {
                foreach ($requestData as $keys => $value) {
                    if (!is_null($value) && $value !== 'undefined' && !empty($value)) {
                        if ($request->strictFilter == true) {
                            $q->where($keys, $value);
                        } else {
                            $q->Where($keys, 'LIKE', '%' . $value . '%');
                        }
                    }
                }
            });

        $subqueryVariations = null;
        if ($request->includeMatrixVariations == 1) {
            $subqueryVariations = LiveProductVariation:://DB::table('newsystem_product_variation_live')
                select($select)
                // ->where('clientCode', $this->api->client->clientCode)
                // ->where('deleted', 0)
                ->where(function ($q) use ($requestData, $request) {
                    foreach ($requestData as $keys => $value) {
                        if (!is_null($value) && $value !== 'undefined' && !empty($value)) {
                            if ($request->strictFilter == true) {
                                $q->where($keys, $value);
                            } else {
                                $q->Where($keys, 'LIKE', '%' . $value . '%');
                            }
                        }
                    }
                });
        }

        $query = DB::connection("mysql2")->query()
            ->fromSub($subqueryMatrix, 'matrix')
            ->select($select); 

        if ($subqueryVariations) {
            $query->union($subqueryVariations);
        }
        // Apply the ORDER BY clause
        $query->orderBy($sort_by, $direction);
        $results = $query->paginate($limit); 
        return $this->successWithData($results);
         


    }

    public function getProductLong()
    {
    }

    public function getGroup($req){

        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'productCategoryName';
        }
        if(isset($req->strictFilter) == 0){
            $req->strictFilter = false;
        }

        $pagination = $req->recordsOnPage == '' ? 2000 : $req->recordsOnPage;
        // $categories = $this->category->paginate($pagination);
        $requestData = $req->except(Except::$except);
        
        $datas = LiveProductGroup::where(function ($q) use ($requestData, $req) {
            // $q->where('clientCode', $this->api->client->clientCode);
            foreach ($requestData as $keys => $value) {
                if ($value != null) { 
                    if($req->strictFilter == true){
                        $q->Where($keys, $value);
                    }else{
                        $q->Where($keys, 'LIKE', '%'.$value.'%');
                    }
                    // 'like', '%' . $value . '%'); 
                }
            }
        })->orderBy($req->sort_by, $req->direction)->paginate($pagination);

        return response()->json(["status"=>200, "success" => true, "records" => collect($datas)]);
    }


    protected function packaging($matrix, $variation)
    {
        $newPackage = array();
        foreach ($matrix as $m) {
            array_push($newPackage, $m);
        }

        foreach ($variation as $v) {
            array_push($newPackage, $v);
        }
        return $newPackage;
    }



    protected function customPagination($req, $arrayData,)
    {
        $rOnPage = $req->recordsOnPage == '' ? 20 : $req->recordsOnPage;
        $page = $req->page == '' ? 1 : $req->page; // Get the current page or default to 1, this is what you miss!
        $perPage = $rOnPage;
        $offset = ($page * $perPage) - $perPage;
        return new LengthAwarePaginator(array_slice($arrayData, $offset, $perPage, false), count($arrayData), $perPage, $page, ['path' => $req->url(), 'query' => $req->query()]);
    }
}
