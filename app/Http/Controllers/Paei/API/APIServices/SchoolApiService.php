<?php

namespace App\Http\Controllers\Paei\API\APIServices;

use Illuminate\Support\Facades\{DB, File};
use App\Classes\Except;
use App\Http\Controllers\Paei\Services\GetSalesDocumentService;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\{Cashin, Currency, Customer, SalesDocument, Warehouse};
use App\Models\PswClientLive\Local\{LiveDeliveryMode, LiveItemByLocation, LiveItemLocation, LiveOnHandInventory, LiveProductGroup, LiveProductMatrix, LiveProductVariation, LiveWarehouseLocation};
use App\Models\PswClientLive\SalesOrder;
use App\Traits\{ResponseTrait, WmsValidationTrait};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SchoolApiService
{

    protected $school;
    protected $product;
    protected $api;
    use ResponseTrait, WmsValidationTrait;

    protected $sdService;

    public function __construct(LiveProductMatrix $school, LiveProductVariation $product, EAPIService $api, GetSalesDocumentService $sdService)
    {
        $this->school = $school;
        $this->product = $product;
        $this->api = $api;
        $this->sdService = $sdService;
    }


    public function getSchool($req)
    {


        return $this->getSchoolFromVariationProductTable($req);



        $currentWarehouse = $this->getCurrentWarehouse($req->posID);
        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }

        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SchoolName';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);



        // $groups = $this->group->paginate($pagination);
        $cashins = LiveProductGroup::
            // ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_matrix_live.DefaultStore")
            // ->where("newstystem_store_location_live.erplyID", $req->posID)
            // ->where("newstystem_store_location_live.LocationID", $req->posID)
            // where("parentSchoolGroup", $currentWarehouse["warehouseCode"])
            where(function ($q) use ($currentWarehouse) {
                $q->where("parentSchoolGroup", $currentWarehouse["warehouseCode"])
                    ->orWhere("SecondaryParentSchoolGroup", $currentWarehouse["warehouseCode"]);
            })
            // ->where("newsystem_product_matrix_live.erplyID", ">",0)
            // ->where("newsystem_product_matrix_live.erplyEnabled", 1)
            ->select(["SchoolName", "SchoolID"])
            ->distinct("SchoolName")
            ->where(function ($q) use ($requestData, $req) {
                foreach ($requestData as $keys => $value) {
                    if ($value != null) {
                        if ($req->strictFilter == true) {
                            $q->Where($keys, $value);
                        } else {
                            $q->Where($keys, 'LIKE', '%' . $value . '%');
                        }
                        // 'like', '%' . $value . '%');
                    }
                }
            })->orderBy($req->sort_by, $req->direction)->get(); //->paginate($pagination);
        // $cashins = $this->school
        //     // ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_matrix_live.DefaultStore")
        //     // ->where("newstystem_store_location_live.erplyID", $req->posID)
        //     // ->where("newstystem_store_location_live.LocationID", $req->posID)
        //     ->where("newsystem_product_matrix_live.DefaultStore", $currentWarehouse["warehouseCode"])
        //     ->where("newsystem_product_matrix_live.erplyID", ">",0)
        //     ->where("newsystem_product_matrix_live.erplyEnabled", 1)
        //     ->select(["newsystem_product_matrix_live.SchoolName", "newsystem_product_matrix_live.SchoolID"])
        //     ->distinct("newsystem_product_matrix_live.SchoolName")
        //     ->where(function ($q) use ($requestData, $req) {
        //     foreach ($requestData as $keys => $value) {
        //         if ($value != null) {
        //             if($req->strictFilter == true){
        //                 $q->Where($keys, $value);
        //             }else{
        //                 $q->Where($keys, 'LIKE', '%'.$value.'%');
        //             }
        //             // 'like', '%' . $value . '%');
        //         }
        //     }
        // })->orderBy($req->sort_by, $req->direction)->get();//->paginate($pagination);

        return response()->json(["status" => 200, "records" => $cashins]);
    }
    public function getSchoolFromVariationProductTable($req)
    {

        $currentWarehouse = $this->getCurrentWarehouse($req->posID);
        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }

        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SchoolName';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);


        $cashins = LiveProductVariation::where(function ($q) use ($currentWarehouse) {
            $q->where("DefaultStore", $currentWarehouse["warehouseCode"])
                ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
        })
            ->when($currentWarehouse["warehouseCode"] == '3R120', function ($q) {
                $q->whereNot("SchoolID", 17971);
            })
            ->select(["SchoolName", "SchoolID"])
            ->where("erplyEnabled", 1)
            // ->distinct("SchoolID")
            ->groupBy("SchoolID")
            // ->where(function ($q) use ($requestData, $req) {
            //     foreach ($requestData as $keys => $value) {
            //         if ($value != null) {
            //             if ($req->strictFilter == true) {
            //                 $q->Where($keys, $value);
            //             } else {
            //                 $q->Where($keys, 'LIKE', '%' . $value . '%');
            //             }
            //             // 'like', '%' . $value . '%');
            //         }
            //     }
            // })
            ->orderBy($req->sort_by, $req->direction)->get();


        return response()->json(["status" => 200, "records" => $cashins]);
    }

    public function getSchoolV2($req)
    {

        $isLive = false;

        if ($this->api->client->ENV == 1) {
            $isLive = true;
        }

        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SchoolName';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);

        // env("isLive") == true ? "'https://psw.synccare.com.au/php/resyncBySchool?env=LIVE&schoolID='newsystem_product_matrix_live.SchoolID  as extra_column

        // $groups = $this->group->paginate($pagination);
        $cashins = $this->school
            ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_matrix_live.DefaultStore")
            // ->where("newstystem_store_location_live.erplyID", $req->posID)

            // ->where("newsystem_product_matrix_live.erplyID", ">",0)
            // ->where("newsystem_product_matrix_live.erplyEnabled", 1)
            ->select(
                [
                    "newsystem_product_matrix_live.*",
                    "newstystem_store_location_live.LocationName",
                    DB::raw("CASE WHEN '" . $isLive . "' THEN CONCAT('https://psw.synccare.com.au/php/resyncBySchool?env=LIVE&schoolID=', newsystem_product_matrix_live.SchoolID) ELSE CONCAT('https://pswstaging.synccare.com.au/php/public/resyncBySchool?env=TEST&schoolID=', newsystem_product_matrix_live.SchoolID) END as url")
                ]
            )
            // ->distinct("newsystem_product_matrix_live.SchoolName")
            ->with("schoolInfo")
            ->withCount("school")
            ->where(function ($q) use ($requestData, $req) {
                foreach ($requestData as $keys => $value) {
                    if ($value != null) {
                        if ($req->strictFilter == true) {
                            $q->Where("newsystem_product_matrix_live." . $keys, $value);
                        } else {
                            $q->Where("newsystem_product_matrix_live." . $keys, 'LIKE', '%' . $value . '%');
                        }
                        // 'like', '%' . $value . '%');
                    }
                }
            })->groupBy("newsystem_product_matrix_live.SchoolID")
            ->orderBy($req->sort_by, $req->direction)->paginate($pagination);

        return response()->json(["status" => 200, "records" => $cashins]);
    }

    public function getAllMatrix($req)
    {
        $version = $req->version ? $req->version : '';

        if ($version == "v3") {
            return $this->getAllMatrixV3($req);
        }

        if ($version == "v4") {
            return $this->getAllMatrixV4($req);
        }

        if ($req->newapi == true) {
            return $this->getAllMatrixV2($req);
        }



        // if($version == "v5"){
        return $this->getAllMatrixV5($req);
        // }

        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SOFOrder';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }

        $currentWarehouse = $this->getCurrentWarehouse($req->posID);
        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);

        //first getting the distinct SOF Template
        // $sofTemplate  = $this->school->select("SOFTemplate", "SOFName")->where("SchoolID", $req->schoolID)->where("erplyEnabled", 1)->groupBy("SOFTemplate")->orderBy("SOFTemplate", 'asc')->get();
        $sofTemplate  = LiveProductVariation::select("SOFTemplate", "SOFName")->where("SchoolID", $req->schoolID)->where("erplyEnabled", 1)->groupBy("SOFTemplate")->orderBy("SOFTemplate", 'asc')->get();

        $currentSOF = '';
        if (count($sofTemplate) > 0) {
            $currentSOF = $sofTemplate[0]["SOFTemplate"];
        }
        if (isset($req->sofTemplate) == 1) {
            $currentSOF = $req->sofTemplate;
        }

        $query = $this->school
            // ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_matrix_live.DefaultStore")
            // ->where("newstystem_store_location_live.erplyID", $req->posID)
            // ->where("newstystem_store_location_live.LocationID", $req->posID)
            ->where(function ($q) use ($currentWarehouse) {
                $q->where("newsystem_product_matrix_live.DefaultStore", $currentWarehouse["warehouseCode"])
                    ->orWhere("newsystem_product_matrix_live.SecondaryStore", $currentWarehouse["warehouseCode"]);
            })
            ->where("newsystem_product_matrix_live.erplyID", ">", 0)
            ->where("newsystem_product_matrix_live.erplyEnabled", 1)
            ->where("newsystem_product_matrix_live.PSWPRICELISTITEMCATEGORY", '>', 0)
            ->where("newsystem_product_matrix_live.SOFTemplate", $currentSOF)
            ->select(
                [
                    "WEBSKU",
                    "ITEMID",
                    "ColourID",
                    "CONFIGID",
                    "ItemName",
                    "ColourName",
                    "SizeID",
                    "RetailSalesPrice",
                    "DefaultStore",
                    "SchoolID",
                    "imageUrl",
                    "Category_Name",
                    "PSWPRICELISTITEMCATEGORY",
                ]
            );
        if ($req->has('schoolID')) {
            $query->where("newsystem_product_matrix_live.SchoolID", $req->schoolID);
        }
        $query->where(function ($q) use ($requestData, $req) {
            foreach ($requestData as $keys => $value) {
                if ($value != null) {
                    if ($req->strictFilter == true) {
                        $q->where($keys, $value);
                    } else {
                        $q->where($keys, 'LIKE', '%' . $value . '%');
                    }
                }
            }
        });
        $results = $query
            ->with(
                [
                    "variations" => function ($q) {

                        $q->select(
                            [
                                "ICSC",
                                "WEBSKU",
                                "erplyID as productID",
                                "SchoolID",
                                "SchoolName",
                                "ColourName",
                                "SizeID",
                                "ItemName",
                                "ERPLYSKU",
                                "RetailSalesPrice",
                                "RetailSalesPrice2",
                                "DefaultStore",
                                "imageUrl",
                                "ERPLYSKU",
                                "PSWPRICELISTITEMCATEGORY",
                                DB::raw("CONCAT(ItemName,' ', ColourName, ' ',SizeID) as productName"),
                            ]
                        )
                            ->where("erplyID", '>', 0)
                            ->where("erplyEnabled", 1)
                            ->where("PSWPRICELISTITEMCATEGORY", '>', 0);
                    }
                ]
            )
            ->withCount(['variations' => function ($q) {
                $q->where("erplyID", '>', 0)
                    ->where("erplyEnabled", 1);
            }])
            ->orderBy("PSWPRICELISTITEMCATEGORY", 'asc')
            ->orderBy($req->sort_by, $req->direction)
            ->get();
        $results->each(function ($item) {
            if ($item->variations_count == 1 && !$item->variations->isEmpty()) {
                $defaultStore = $item->variations[0]->DefaultStore;
                // $item->load(['variations.stocks' => function ($q) use ($defaultStore) {
                //     $q->where("Warehouse", $defaultStore)
                //         ->select(["ICSC", "Warehouse", "AvailablePhysical as Stock"]);
                // }]);
                $item->load(['variations' => function ($q) use ($defaultStore) {
                    $q->select([
                        "ICSC",
                        "WEBSKU",
                        "erplyID as productID",
                        "SchoolID",
                        "SchoolName",
                        "ColourName",
                        "SizeID",
                        "ItemName",
                        "ERPLYSKU",
                        "RetailSalesPrice",
                        "RetailSalesPrice2",
                        "DefaultStore",
                        "imageUrl",
                        "ERPLYSKU",
                        "Category_Name",
                        "PSWPRICELISTITEMCATEGORY",
                        DB::raw("CONCAT(ItemName,' ', ColourName, ' ',SizeID) as productName")
                    ])->with(['stocks' => function ($q) use ($defaultStore) {
                        $q->where("Warehouse", $defaultStore)
                            ->select(["ICSC", "Warehouse", "AvailablePhysical as Stock"]);
                    }]);
                }]);
            }
        });
        return response()->json(["status" => 200, "records" => $results, "SOF" => $sofTemplate]);
    }

    public function getAllMatrixV5($req)
    {

        $debug = $req->debug ?? 0;
        $version = $req->version ? $req->version : '';

        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SOFOrder';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }

        $currentWarehouse = $this->getCurrentWarehouse($req->posID);


        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }


        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $excepts = Except::$except;
        $excepts[] = "version";
        $excepts[] = "debug";
        $requestData = $req->except($excepts);

        //first getting the distinct SOF Template
        // $sofTemplate  = $this->school->select("SOFTemplate", "SOFName")->where("SchoolID", $req->schoolID)->where("erplyEnabled", 1)->groupBy("SOFTemplate")->orderBy("SOFTemplate", 'asc')->get();
        $sofTemplate  = LiveProductVariation::select("SOFTemplate", "SOFName")->where("SchoolID", $req->schoolID)->where("erplyEnabled", 1)->groupBy("SOFTemplate")->orderBy("SOFTemplate", 'asc')->get();

        $currentSOF = '';
        if (count($sofTemplate) > 0) {
            $currentSOF = $sofTemplate[0]["SOFTemplate"];
        }
        if (isset($req->sofTemplate) == 1) {
            $currentSOF = $req->sofTemplate;
        }



        $query = $this->school
            // ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_matrix_live.DefaultStore")
            // ->where("newstystem_store_location_live.erplyID", $req->posID)
            // ->where("newstystem_store_location_live.LocationID", $req->posID)
            ->where(function ($q) use ($currentWarehouse) {
                $q->where("newsystem_product_matrix_live.DefaultStore", $currentWarehouse["warehouseCode"])
                    ->orWhere("newsystem_product_matrix_live.SecondaryStore", $currentWarehouse["warehouseCode"]);
            })
            ->where("newsystem_product_matrix_live.erplyID", ">", 0)
            ->where("newsystem_product_matrix_live.erplyEnabled", 1)
            // ->where("newsystem_product_matrix_live.PSWPRICELISTITEMCATEGORY", '>', 0)
            // ->where("newsystem_product_matrix_live.SOFTemplate", $currentSOF)
            ->whereHas("variations", function ($q) use ($currentSOF) {
                $q->where("SOFTemplate", $currentSOF)
                    ->where("erplyEnabled", 1)
                    ->where("PSWPRICELISTITEMCATEGORY", '>', 0);
            })
            // ->where("WEBSKU", '19882_1106285_0')
            ->select(
                [
                    "WEBSKU",
                    "ITEMID",
                    "ColourID",
                    "CONFIGID",
                    "ItemName",
                    "ColourName",
                    "SizeID",
                    "RetailSalesPrice",
                    "DefaultStore",
                    "SchoolID",
                    "imageUrl",
                    "Category_Name",
                    "PSWPRICELISTITEMCATEGORY",
                ]
            );
        if ($req->has('schoolID')) {
            $query->where("newsystem_product_matrix_live.SchoolID", $req->schoolID);
        }
        $query->where(function ($q) use ($requestData, $req) {
            foreach ($requestData as $keys => $value) {
                if ($value != null) {
                    if ($req->strictFilter == true) {
                        $q->where($keys, $value);
                    } else {
                        $q->where($keys, 'LIKE', '%' . $value . '%');
                    }
                }
            }
        });




        $results = $query
            ->with(
                [
                    "variations" => function ($q) use ($currentSOF) {

                        $q->select(
                            [
                                "ICSC",
                                "WEBSKU",
                                "erplyID as productID",
                                "SchoolID",
                                "SchoolName",
                                "ColourName",
                                "SizeID",
                                "ItemName",
                                "ERPLYSKU",
                                "RetailSalesPrice",
                                "RetailSalesPrice2",
                                "DefaultStore",
                                "imageUrl",
                                "ERPLYSKU",
                                "PSWPRICELISTITEMCATEGORY",
                                DB::raw("CONCAT(ItemName,' ', ColourName, ' ',SizeID) as productName"),
                            ]
                        )
                            ->where("erplyID", '>', 0)
                            ->where("erplyEnabled", 1)
                            ->where("SOFTemplate", $currentSOF)
                            ->where("PSWPRICELISTITEMCATEGORY", '>', 0);
                    }
                ]
            )
            ->withCount(['variations' => function ($q) {
                $q->where("erplyID", '>', 0)
                    ->where("erplyEnabled", 1);
            }])
            ->orderBy("PSWPRICELISTITEMCATEGORY", 'asc')
            ->orderBy($req->sort_by, $req->direction)
            ->get();
        if ($debug == 101) {
            dd($results);
        }


        $newResults = collect();


        $results->each(function ($item) use ($currentWarehouse, $newResults, $debug) {

            if ($item->variations_count == 1 && !$item->variations->isEmpty()) {
                $defaultStore = @$item->variations[0]->DefaultStore;
                $item->SizeID = @$item->variations[0]->SizeID;
                $item->SizeName = '2XL';
                $item->ColourName = @$item->variations[0]->ColourName;
                $item->PSWPRICELISTITEMCATEGORY = @$item->variations[0]->PSWPRICELISTITEMCATEGORY;
                // $item->load(['variations.stocks' => function ($q) use ($defaultStore) {
                //     $q->where("Warehouse", $defaultStore)
                //         ->select(["ICSC", "Warehouse", "AvailablePhysical as Stock"]);
                // }]);
                $item->load(['variations' => function ($q) use ($defaultStore) {
                    $q->where("erplyID", '>', 0)
                        ->where("erplyEnabled", 1)
                        ->select([
                            "ICSC",
                            "WEBSKU",
                            "erplyID as productID",
                            "SchoolID",
                            "SchoolName",
                            "ColourName",
                            "SizeID",
                            "ItemName",
                            "ERPLYSKU",
                            "RetailSalesPrice",
                            "RetailSalesPrice2",
                            "DefaultStore",
                            "imageUrl",
                            "ERPLYSKU",
                            "Category_Name",
                            "PSWPRICELISTITEMCATEGORY",
                            DB::raw("CONCAT(ItemName,' ', ColourName, ' ',SizeID) as productName")
                        ])->with(['stocks' => function ($q) use ($defaultStore) {
                            $q->where("Warehouse", $defaultStore)
                                ->select(["ICSC", "Warehouse", "AvailablePhysical as Stock"]);
                        }]);
                }]);
                $item->variations->each(function ($variations)use($currentWarehouse) {

                    // dd( $variations->ICSC);
                    if (is_null($variations->stocks)) {
                        $variations->setRelation('stocks', collect(
                            [
                                "ICSC" => $variations->ICSC,
                                "Warehouse" => $currentWarehouse["warehouseCode"],
                                "Stock" => "0", // Default stock value
                            ]
                        ));
                    }
                });
                if($debug == 102){
                    if ($item->WEBSKU == '19896_4700065_19896_3') {
                        dd($item);
                    }
                }
                $newResults->push($item);
            } else {
                $colours = LiveProductVariation::select("ColourName", "ColourID")
                    ->where("WEBSKU", $item->WEBSKU)
                    ->where("erplyID", '>', 0)
                    ->where("erplyEnabled", 1)
                    ->where(function ($q) use ($currentWarehouse) {
                        $q->where("DefaultStore", $currentWarehouse["warehouseCode"])
                            ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                    })
                    ->groupBy("ColourName")
                    ->orderBy("ColourName", "asc")
                    ->pluck("ColourName", "ColourID")
                    ->toArray();
                if ($debug == 100) {
                    if ($item->WEBSKU == '19859_4700072_0') {
                        dd($item, $colours);
                    }
                }
                foreach ($colours as $key => $color) {
                    // $vInfo = LiveProductVariation::where("WEBSKU", $item->WEBSKU)->where("erplyEnabled", 1)->where("ColourName", $color)->where('PSWPRICELISTITEMCATEGORY', '>', 0)->first();
                    // if (!$vInfo) {
                    //     $vInfo = LiveProductVariation::where("WEBSKU", $item->WEBSKU)->where("erplyEnabled", 1)->where("ColourName", $color)->first();
                    // }
                    // $variationTotalCount = LiveProductVariation::where("WEBSKU", $item->WEBSKU)->where("erplyEnabled", 1)->where("ColourID", $key)->count();
                    // $newMatrixProduct = clone $item;
                    // if($debug == 100){
                    //     if($item->WEBSKU == '19871_4700072_0'){
                    //         dd($item, $colours, $vInfo);
                    //     }
                    // }
                    // // Modify the clone to reflect the current variation's details
                    // $newMatrixProduct->ColourName = $color;
                    // $newMatrixProduct->ColourID = $key;
                    // if (@$vInfo->PSWPRICELISTITEMCATEGORY > 0) {
                    //     $newMatrixProduct->PSWPRICELISTITEMCATEGORY = $vInfo->PSWPRICELISTITEMCATEGORY;
                    //     $newMatrixProduct->Category_Name = $vInfo->Category_Name;
                    // }
                    // $newMatrixProduct->variations_count = $variationTotalCount;
                    // $newMatrixProduct->ItemName = $newMatrixProduct->ItemName . ' ' . $color;
                    // $newMatrixProduct->SizeID = $vInfo->SizeID;
                    // $newMatrixProduct->RetailSalesPrice = $vInfo->RetailSalesPrice;
                    // $newMatrixProduct->imageUrl = $vInfo->imageUrl;
                    // if (@$vInfo->PSWPRICELISTITEMCATEGORY > 0){
                    //     $newResults->push($newMatrixProduct);
                    // }

                    $vInfo = LiveProductVariation::where("WEBSKU", $item->WEBSKU)->where('erplyID', '>', 0)
                        ->where("erplyEnabled", 1)
                        ->where("ColourName", $color)
                        ->where('PSWPRICELISTITEMCATEGORY', '>', 0)->first();
                    if ($vInfo) {
                        $variationTotalCount = LiveProductVariation::where("WEBSKU", $item->WEBSKU)
                            ->where('PSWPRICELISTITEMCATEGORY', '>', 0)
                            ->where("erplyEnabled", 1)
                            ->where('erplyID', '>', 0)
                            ->where("ColourID", $key)
                            ->count();
                        $allVariations = LiveProductVariation::where("WEBSKU", $item->WEBSKU)->where("erplyEnabled", 1)->where("ColourID", $key)
                            ->where('PSWPRICELISTITEMCATEGORY', '>', 0)
                            ->where('erplyID', '>', 0)
                            ->select([
                                "ICSC",
                                "WEBSKU",
                                "erplyID as productID",
                                "SchoolID",
                                "SchoolName",
                                "ColourName",
                                "SizeID",
                                "ItemName",
                                "ERPLYSKU",
                                "RetailSalesPrice",
                                "RetailSalesPrice2",
                                "DefaultStore",
                                "imageUrl",
                                "ERPLYSKU",
                                "PSWPRICELISTITEMCATEGORY",
                                DB::raw("CONCAT(ItemName,' ', ColourName, ' ',SizeID) as productName"),
                            ])
                            ->with(['stocks' => function ($q) use ($currentWarehouse) {
                                $q->where("Warehouse", $currentWarehouse["warehouseCode"])
                                    ->select(["ICSC", "Warehouse", "AvailablePhysical as Stock"]);
                            }])
                            ->get();
                        $allVariations->each(function ($variation)use($currentWarehouse) {
                            if (is_null($variation->stocks)) {
                                $variation->setRelation('stocks', collect(
                                    [
                                        "ICSC" => $variation->ICSC,
                                        "Warehouse" => $currentWarehouse["warehouseCode"],
                                        "Stock" => "0", // Default stock value
                                    ],
                                ));
                            }
                        });

                        $newMatrixProduct = clone $item;
                        // Modify the clone to reflect the current variation's details
                        $newMatrixProduct->ColourName = $color;
                        $newMatrixProduct->ColourID = $key;
                        $newMatrixProduct->PSWPRICELISTITEMCATEGORY = $vInfo->PSWPRICELISTITEMCATEGORY;
                        $newMatrixProduct->Category_Name = $vInfo->Category_Name;
                        $newMatrixProduct->variations_count = $variationTotalCount;
                        $newMatrixProduct->ItemName = $newMatrixProduct->ItemName . ' ' . $color;
                        $newMatrixProduct->SizeID = $vInfo->SizeID;
                        $newMatrixProduct->RetailSalesPrice = $vInfo->RetailSalesPrice;
                        $newMatrixProduct->imageUrl = $vInfo->imageUrl;
                        // $newMatrixProduct->variations = $allVariations;
                        $newMatrixProduct->setRelation('variations', $allVariations);
                        $newResults->push($newMatrixProduct);
                        if ($debug == 100) {
                            if ($item->WEBSKU == '19871_4700072_0') {
                                dd($item, $colours, $vInfo, $allVariations, $newMatrixProduct);
                            }
                        }
                    }
                }
            }
        });


        return response()->json(["status" => 200, "records" => $newResults, "SOF" => $sofTemplate]);
    }



    public function getAllMatrixV2($req)
    {
        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SchoolName';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);
        $matrix = LiveProductMatrix::where("WEBSKU", $req->WEBSKU)->first();
        $datas = LiveProductVariation::with(
            [
                "stocks" => function ($q) use ($matrix) {
                    $q->where("Warehouse", $matrix->DefaultStore)
                        ->select(["ICSC", "Warehouse", "AvailablePhysical as Stock"]);
                }
            ]
        )
            // join('newsystem_item_by_locations', function($join) {
            //     $join->on('newsystem_product_variation_live.ICSC', '=', 'newsystem_item_by_locations.ICSC')
            //          ->on('newsystem_product_variation_live.DefaultStore', '=', 'newsystem_item_by_locations.Warehouse');
            // })
            ->leftJoin("newsystem_product_size_sort_order_live", "newsystem_product_size_sort_order_live.size", "newsystem_product_variation_live.SizeID")
            ->where("newsystem_product_variation_live.WEBSKU", $req->WEBSKU)
            // ->when($colourID != '', function($q)use($colourID){
            //     $q->where("ColourID", $colourID);
            // })
            ->where("newsystem_product_variation_live.erplyID", '>', 0)
            ->where("newsystem_product_variation_live.erplyEnabled", 1)
            ->select(
                [
                    "newsystem_product_variation_live.ICSC",
                    "newsystem_product_variation_live.WEBSKU",
                    "newsystem_product_variation_live.erplyID as productID",
                    "newsystem_product_variation_live.SchoolID",
                    "newsystem_product_variation_live.SchoolName",
                    "newsystem_product_variation_live.ColourID",
                    "newsystem_product_variation_live.ColourName",
                    "newsystem_product_variation_live.SizeID",
                    "newsystem_product_variation_live.ItemName",
                    "newsystem_product_variation_live.ERPLYSKU",
                    "newsystem_product_variation_live.RetailSalesPrice",
                    // "newsystem_product_variation_live.RetailSalesPrice2",
                    "newsystem_product_variation_live.DefaultStore",
                    "newsystem_product_variation_live.imageUrl",
                    // "newsystem_product_variation_live.ERPLYSKU",
                    DB::raw("CONCAT(newsystem_product_variation_live.ItemName,' ', newsystem_product_variation_live.ColourName, ' ',newsystem_product_variation_live.SizeID) as productName"),
                    // "newsystem_item_by_locations.Warehouse",
                    // "newsystem_item_by_locations.AvailablePhysical as Stock",
                ]
            )->orderBy("newsystem_product_size_sort_order_live.sort_order", "asc")
            // ->orderBy("newsystem_product_variation_live.ColourName", "asc")
            ->paginate($pagination);

        return response()->json(["status" => 200, "records" => $datas]);
    }
    public function getAllMatrixV3($req)
    {



        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SchoolName';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);

        $matrix = LiveProductMatrix::where("WEBSKU", $req->WEBSKU)->first();

        //first checking is there multiple colour
        $colours = LiveProductVariation::select("ColourName")->where("WEBSKU", $req->WEBSKU)->where("erplyEnabled", 1)->groupBy("ColourName")->orderBy("ColourName", "asc")->pluck("ColourName")->toArray();

        $slideData = array();

        foreach ($colours as $color) {

            $datas = LiveProductVariation::with(
                [
                    "stocks" => function ($q) use ($matrix) {
                        $q->where("Warehouse", $matrix->DefaultStore)
                            ->select(["ICSC", "Warehouse", "AvailablePhysical as Stock"]);
                    }
                ]
            )
                ->leftJoin("newsystem_product_size_sort_order_live", "newsystem_product_size_sort_order_live.size", "newsystem_product_variation_live.SizeID")
                ->where("newsystem_product_variation_live.WEBSKU", $req->WEBSKU)
                ->where("newsystem_product_variation_live.ColourName", $color)
                ->where("newsystem_product_variation_live.erplyID", '>', 0)
                ->select(
                    [
                        "newsystem_product_variation_live.ICSC",
                        "newsystem_product_variation_live.WEBSKU",
                        "newsystem_product_variation_live.erplyID as productID",
                        "newsystem_product_variation_live.SchoolID",
                        "newsystem_product_variation_live.SchoolName",
                        "newsystem_product_variation_live.ColourName",
                        "newsystem_product_variation_live.SizeID",
                        "newsystem_product_variation_live.ItemName",
                        "newsystem_product_variation_live.ERPLYSKU",
                        "newsystem_product_variation_live.RetailSalesPrice",
                        // "newsystem_product_variation_live.RetailSalesPrice2",
                        "newsystem_product_variation_live.DefaultStore",
                        "newsystem_product_variation_live.imageUrl",
                        // "newsystem_product_variation_live.ERPLYSKU",
                        DB::raw("CONCAT(newsystem_product_variation_live.ItemName,' ', newsystem_product_variation_live.ColourName, ' ',newsystem_product_variation_live.SizeID) as productName"),
                        // "newsystem_item_by_locations.Warehouse",
                        // "newsystem_item_by_locations.AvailablePhysical as Stock",
                    ]
                )->orderBy("newsystem_product_size_sort_order_live.sort_order", "asc")
                ->get();
            $slideData[] = $datas;
        }

        return response()->json(["status" => 200, "records" => $slideData]);
    }

    public function getAllMatrixV4($req)
    {

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }

        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SchoolName';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }
        $debug = $req->debug ?? 0;
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);
        $colourID = $req->ColourID ?? '';
        $matrix = LiveProductMatrix::where("WEBSKU", $req->WEBSKU)->first();

        //first checking is there multiple colour
        $colours = LiveProductVariation::select("ColourName")
            ->where("WEBSKU", $req->WEBSKU)
            ->when($colourID != '', function ($q) use ($colourID) {
                $q->where("ColourID", $colourID);
            })
            ->where("erplyID", '>', 0)
            ->where("erplyEnabled", 1)
            ->where("PSWPRICELISTITEMCATEGORY", '>', 0)
            ->where(function ($q) use ($currentWarehouse) {
                $q->where("DefaultStore", $currentWarehouse["warehouseCode"])
                    ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
            })
            ->groupBy("ColourName")
            ->orderBy("ColourName", "asc")
            ->pluck("ColourName")
            ->toArray();
        $slideData = array();
        foreach ($colours as $color) {
            $datas = LiveProductVariation::with(
                [
                    "stocks" => function ($q) use ($matrix) {
                        $q->where("Warehouse", $matrix->DefaultStore)
                            ->select(["ICSC", "Warehouse", "AvailablePhysical as Stock"]);
                    }
                ]
            )
                // ->leftJoin("newsystem_product_size_sort_order_live", "newsystem_product_size_sort_order_live.size", "newsystem_product_variation_live.SizeID")
                ->where("newsystem_product_variation_live.WEBSKU", $req->WEBSKU)
                ->where("newsystem_product_variation_live.ColourName", $color)
                ->where("newsystem_product_variation_live.erplyID", '>', 0)
                ->where("newsystem_product_variation_live.PSWPRICELISTITEMCATEGORY", '>', 0)
                ->where(function ($q) use ($currentWarehouse) {
                    $q->where("DefaultStore", $currentWarehouse["warehouseCode"])
                        ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                })
                ->where("newsystem_product_variation_live.erplyEnabled", 1)
                ->select(
                    [
                        "newsystem_product_variation_live.ICSC",
                        "newsystem_product_variation_live.WEBSKU",
                        "newsystem_product_variation_live.erplyID as productID",
                        "newsystem_product_variation_live.SchoolID",
                        "newsystem_product_variation_live.SchoolName",
                        "newsystem_product_variation_live.ColourID",
                        "newsystem_product_variation_live.ColourName",
                        "newsystem_product_variation_live.SizeID",
                        "newsystem_product_variation_live.ItemName",
                        "newsystem_product_variation_live.ERPLYSKU",
                        "newsystem_product_variation_live.RetailSalesPrice",
                        // "newsystem_product_variation_live.RetailSalesPrice2",
                        "newsystem_product_variation_live.DefaultStore",
                        "newsystem_product_variation_live.imageUrl",
                        // "newsystem_product_variation_live.ERPLYSKU",
                        DB::raw("CONCAT(newsystem_product_variation_live.ItemName,' ', newsystem_product_variation_live.ColourName, ' ',newsystem_product_variation_live.SizeID) as productName"),
                        // "newsystem_item_by_locations.Warehouse",
                        // "newsystem_item_by_locations.AvailablePhysical as Stock",
                    ]
                )
                // ->orderBy("newsystem_product_size_sort_order_live.sort_order", "asc")
                ->get();
            $datas->each(function ($variation) use($currentWarehouse){
                if (is_null($variation->stocks)) {
                    $variation->setRelation('stocks', collect(
                        [
                            "ICSC" => $variation->ICSC,
                            "Warehouse" => $currentWarehouse["warehouseCode"],
                            "Stock" => "0", // Default stock value
                        ],
                    ));
                }
            });
            if (count($datas) > 0) {
                $slideData[] = $datas;
            }
        }
        return response()->json(["status" => 200, "records" => $slideData]);
    }

    public function getAllVariationsWithSOH($req) {}

    public function getAll($req)
    {
        if (isset($req->direction) == 0) {
            $req->direction = 'asc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'SchoolName';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = false;
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 200;
        $requestData = $req->except(Except::$except);


        // $groups = $this->group->paginate($pagination);
        $cashins = $this->product
            ->join("newstystem_store_location_live", "newstystem_store_location_live.LocationID", "newsystem_product_variation_live.DefaultStore")
            ->where("newstystem_store_location_live.erplyID", $req->posID)
            ->where("newsystem_product_variation_live.erplyID", ">", 0)
            ->where(function ($q) use ($requestData, $req) {
                foreach ($requestData as $keys => $value) {
                    if ($value != null) {
                        if ($req->strictFilter == true) {

                            $q->Where($keys, $value);
                        } else {

                            $q->Where($keys, 'LIKE', '%' . $value . '%');
                        }
                        // 'like', '%' . $value . '%');
                    }
                }
            })->select(["newsystem_product_variation_live.*", "newsystem_product_variation_live.erplyID as productID"])->orderBy($req->sort_by, $req->direction)->get(); //paginate($pagination);

        return response()->json(["status" => 200, "records" => $cashins]);
    }

    public function getDeliveryMode()
    {

        $modes = LiveDeliveryMode::all();
        return response()->json($modes);
    }

    public function salesOrder($req)
    {

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }

        $ownerCustomer = Customer::where("clientCode", $currentWarehouse["clientCode"])->where("customerID", $req->customerID)->first();

        if (!$ownerCustomer) {
            return $this->failWithMessage("Customer Doesn't Exist.");
        }
        // dd($req);

        $reqArray = array(
            // "requestName" => "saveSalesDocument",
            "sessionKey" => $this->api->client->sessionKey,
            // "clientCode" => $this->api->client->clientCode,
            // "type" => "OFFER",
            // "invoiceNo" => substr($so->newSystemOrderNumber, 1 ),
            // "customNumber" => $so->newSystemOrderNumber,
            "confirmInvoice" => 0,
            "warehouseID" => $currentWarehouse["warehouseID"],
            // "paymentType" => $req->paymentType,
            "customerID" => $req->customerID,
            // "addressID" => $so->erplyAddressID,
            // "shipToAddressID" => $so->erplyDeliveryID,
            // "shipToID" => $so->erplyDeliveryID,

            // "attributeName1" => "SchoolName",
            // "attributeType1" => "text",
            // "attributeValue1" => "1234,56789",
            "notes" => $req->notes ? $req->notes : '',
            // "paymentInfo" => $so->payment_detail_title,

        );



        $type = 'OFFER';
        if (@$req->isFinal == true || @$req->payNow == true) {
            $type = "ORDER";
            $reqArray["confirmInvoice"] = 1;
        }

        $isOfferToOrder = 0;
        if ((@$req->isOfferToOrder ? @$req->isOfferToOrder : 0) == 1) {
            $isOfferToOrder = 1;
        }

        $reqArray["type"] = $type;

        $currentDateTime = date('Y-m-d H:i:s');
        if (!$req->salesDocumentID) {
            $reqArray["date"] = date('Y-m-d');
            $reqArray["time"] = date('H:i:s');
        }


        $attname = array();

        $cartItems = array();
        $myCartItems = array();

        $isItemExist = false;

        foreach ($req->toArray() as $key => $pi) {

            if (str_contains($key, "salesDocumentID")) {
                if ($isOfferToOrder == 0) {
                    $reqArray["id"] = $pi;
                } else {
                    //if the sales doc is offer then update delete flag and later delete from erply
                    SalesDocument::where("clientCode", $currentWarehouse["clientCode"])->where("salesDocumentID", $pi)->where("type", "OFFER")->update(
                        [
                            "deleted" => 1
                        ]
                    );
                }
            }

            if (str_contains($key, "productID")) {

                $isItemExist = true;
                $pCount = trim(str_replace("productID", "", $key));
                $reqArray[$key] = $pi;
                // dd($req["price".$pCount] / 1.1);
                $cart = array(
                    "productID" => $req["productID" . $pCount],
                    "amount"  => $req["amount" . $pCount],
                    // "discount" => $req["discount".$key],
                    // "price" => bcadd($req["price".$pCount] / 1.1, 0,4),
                    "price"  => $req["price" . $pCount] / 1.1,
                );
                // if($req["price".$pCount]){
                //     $req["price".$pCount] =  bcadd($req["price".$pCount] / 1.1, 0,4);
                // }
                if ($req->discount . $pCount) {
                    $cart["discount"] = $req["discount" . $pCount];
                }

                $myCartItems["orderIndex" . $pCount] = $pCount;
                $myCartItems["productID" . $pCount] = $req["productID" . $pCount];
                $myCartItems["amount" . $pCount] = $req["amount" . $pCount];
                // $myCartItems["price".$pCount] = $req["price".$pCount] / 1.1;
                $cartItems[] = $cart;
            }
            if (str_contains($key, "amount")) {
                $reqArray[$key] = $pi;
            }
            if (str_contains($key, "price")) {
                // dd($pi);
                // $reqArray[$key] = round($pi / 1.1, 4);
            }
            if (str_contains($key, "discount")) {
                $reqArray[$key] = $pi;
            }

            //for school id
            if (str_contains($key, "attributeName")) {
                $attname[] = $pi;
                $reqArray[$key] = $pi;
            }
            if (str_contains($key, "attributeType")) {
                $reqArray[$key] = $pi;
            }
            if (str_contains($key, "attributeValue")) {
                $reqArray[$key] = $pi;
            }
        }

        if ($isItemExist == false) {
            return $this->failWithMessage("Empty Cart Item.");
        }
        //first saving sales orders to local ldb
        $isAtt = false;
        $attributes = array();
        foreach ($attname as $key => $fatt) {
            $attList = array(
                "attributeName" => $req["attributeName" . $key + 1],
                "attributeType" => $req["attributeType" . $key + 1],
                "attributeValue" => $req["attributeValue" . $key + 1],
            );

            $attributes[] = $attList;
            $isAtt = true;
        }
        // $chk = SalesDocument::where("salesDocumentID", 264)->first();
        // $data = json_decode($chk->attributes,true);
        // return response()->json($data);
        // dd(json_encode($attributes, true));


        //calling calculate cart api
        $myCartItems["customerID"] = $reqArray["customerID"];
        $myCartItems["warehouseID"] = $reqArray["warehouseID"];
        $calculateCart = $this->api->sendRequest("calculateShoppingCart", $myCartItems);
        if ($calculateCart["status"]["errorCode"] == 0 && !empty(@$calculateCart["records"][0]["rows"])) {
            foreach ($calculateCart["records"][0]["rows"] as $key => $promotion) {
                // dd($key, $promotion);
                foreach ($promotion as $promoKey => $promo) {
                    if (str_contains($promoKey, "promotionRule")) {
                        $reqArray[$promoKey] = $promo;
                    }
                    if ($promoKey == "discount" && $promo > 0) {
                        $reqArray["discount" . $key] = $promo;
                    }
                    // dd($key, $promotion, $promoKey, $promo);
                }
            }
        }
        // dd($reqArray, $currentWarehouse, $myCartItems, $calculateCart);
        $res = $this->api->sendRequest("saveSalesDocument", $reqArray);

        if ($res["status"]["errorCode"] == 0 && !empty($res['records'])) {
            // info($res);
            //now again getting sales order for synccing to local db
            // $res = $this->api->sendRequest("saveSalesDocument", $reqArray);
            $localDetails = $res["records"][0];
            // if($isAtt == true){
            // if($req->salesDocumentID){
            $localDetails["attributes"] = $attributes;
            // }
            $localDetails["id"] = $res["records"][0]["invoiceID"];
            $localDetails["number"] = $res["records"][0]["invoiceNo"];
            $localDetails["netTotal"] = $res["records"][0]["net"];
            $localDetails["vatTotal"] = $res["records"][0]["vat"];
            $localDetails["warehouseID"] = $currentWarehouse["warehouseID"];
            $localDetails["type"] = $type;
            $localDetails["invoiceState"] = "PENDING";
            $localDetails["date"] = date("Y-m-d");
            $localDetails["added"] = strtotime($currentDateTime);
            $localDetails["clientID"] = $req->customerID;

            if (isset($req->express) == 1) {
                $localDetails["isExpress"] = $req->express;
            }
            if (@$req->payNow == true) {
                $localDetails["payNow"] = 1;
            }

            $localDetails["clientName"] = @$ownerCustomer->lastName . ', ' . @$ownerCustomer->firstName;
            // info($localDetails);
            //now handling sales items
            $newRows = array();
            foreach ($res["records"][0]["rows"] as $row) {
                $makeRow = array(
                    "rowID" => $row["rowID"],
                    "stableRowID" => $row["stableRowID"],
                    "productID" => $row["productID"],
                    "serviceID" => $row["serviceID"],
                    "amount" => $row["amount"]
                );

                //now getting product name , sku, rate

                foreach ($cartItems as $litem) {
                    if ($litem["productID"] == $row["productID"]) {
                        $makeRow["price"] = $litem["price"];
                        $makeRow["finalNetPrice"] = $litem["price"];
                        $makeRow["finalPriceWithVAT"] = round($litem["price"] * 1.1, 2);
                        $ci = LiveProductVariation::where("erplyID", $row["productID"])->first();
                        $makeRow["itemName"] = $ci->ItemName . ' ' . $ci->ColourName . ' ' . $ci->SizeID;
                        $makeRow["code"] = $ci->ERPLYSKU;
                    }

                    if (@$litem["discount"]) {
                        $makeRow["discount"] = $litem["discount"];
                    }

                    $newRows[] = $makeRow;
                }
            }

            unset($localDetails["rows"]);
            $localDetails["rows"] = $newRows;
            $localDetails["lastModifierUsername"] = $this->getCurrentUserEmail();
            // dd($localDetails);
            // info($newRows);
            // info($cartItems);
            $this->sdService->saveUpdateFromLocal($localDetails);
            //if final order then send picking slip link
            // if($req->isFinal){
            if (env("isLive") == true) {
                $res['records'][0]["pickingSlipLink"] = "https://www.psw.synccare.com.au/php/getPickingSlip?env=LIVE&warehouseID=" . $currentWarehouse["warehouseID"] . "&salesDocumentID=" . $res["records"][0]["invoiceID"];
            } else {
                $res['records'][0]["pickingSlipLink"] = "https://pswstaging.synccare.com.au/php/public/getPickingSlip?env=TEST&warehouseID=" . $currentWarehouse["warehouseID"] . "&salesDocumentID=" . $res["records"][0]["invoiceID"];
            }
            // }
            return $this->successWithDataAndMessage("Sales Order Placed Successfully.", $res);
        }

        return $this->failWithMessageAndData("Failed while placing sales order.", $res);
    }

    public function orderPayment($payments, $salesDocumentID)
    {


        $bulkPayment = array();

        foreach ($payments as $pmt) {

            $param = array(
                "requestName" => "savePayment",
                "clientCode" => $this->api->client->clientCode,
                "sessionKey" => $this->api->client->sessionKey,
                "documentID" => $salesDocumentID,
                "type" => $pmt->type,
                "sum" => $pmt->amount
            );

            $bulkPayment[] = $param;
        }

        if (count($bulkPayment) < 1) {
            info("No Payment found.");
        } else {

            $bulkPayment = json_encode($bulkPayment, true);

            $param = array(
                "sessionKey" => $this->api->client->sessionKey
            );

            $res = $this->api->sendRequest($bulkPayment, $param, 1);

            if ($res["status"]["errorCode"] == 0) {
                info("Cart Order Payment Success");
            }
        }
    }

    public function getOfferOrder($req)
    {

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }


        if ($req->warehouseID) {
            //getting offers from sales document
            // $warehouseInfo = Warehouse::where("code", $req->warehouseID)->first();
            $offers = SalesDocument::with("SalesDetails.axRelation")
                // ->addSelect("newsystem_sales_documents.*","newsystem_sales_document_details.*","newsystem_product_variation_live.erplyID")
                ->where("clientCode", $currentWarehouse["clientCode"])->whereIn("type", ["OFFER", "ORDER"])
                ->where("warehouseID", $currentWarehouse["warehouseID"])
                ->where("deleted", 0)
                ->where("isSynccarePos", 1)
                ->where("isPrinted", 0)
                ->whereIn("invoiceState", ["READY", "PENDING"])
                ->where("pickedOrder", 0)
                ->where("readyToFulfill", 0)
                ->select('id', 'salesDocumentID', 'type', 'warehouseID', 'warehouseName', 'number', 'date', 'time', 'clientID', 'clientName', 'clientEmail', 'total', 'attributes', 'created_at', 'isExpress', 'lastModifierUsername', 'payNow')
                ->whereDate("date", now()->toDateString())
                ->orderBy("salesDocumentID", 'desc')
                // ->select(["newsystem_sales_documents.*", "SalesDetails.productID as erplyID","SalesDetails.*"])
                ->get();
            // return response()->json($offers);
            // dd($offers);
            return $this->successWithData($offers);
        }
    }

    public function deleteOffer($req)
    {

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }

        if ($req->warehouseID && $req->salesDocumentID) {
            // $warehouseInfo = Warehouse::where("code", $req->warehouseID)->first();
            SalesDocument::where("clientCode", $currentWarehouse['clientCode'])->where("warehouseID", $currentWarehouse["warehouseID"])->where("salesDocumentID", $req->salesDocumentID)
                ->update(
                    [
                        "deleted" => 1
                    ]
                );

            return $this->successWithMessage("Order Deleted Successfully.");
        }
        return $this->failWithMessage("Invalid Order ID.");
    }

    public function deleteOfferAfterOneDay($req)
    {

        $limit = @$req->limit ? @$req->limit : 50;
        //first getting offer order before current day
        $datas = SalesDocument::where("clientCode", $this->api->client->clientCode)->where("type", "OFFER")->where("isSynccarePos", 1)->whereDate('added', '<',  now()->toDateString())->where('erplyDeleted', 0)->orderBy("added", 'desc')->limit($limit)->get();

        if ($datas->isEmpty()) {
            return response("All Offer Deleted From Locally.");
        }

        $bulkDelete = array();
        foreach ($datas as $data) {
            // $data->deleted = 1;
            // $data->save();
            $param = array(
                "requestName" => "deleteSalesDocument",
                "sessionKey" => $this->api->client->sessionKey, //$this->api->verifySessionByKey($this->api->client->sessionKey),
                "clientCode" => $this->api->client->clientCode,
                "documentID" => $data->salesDocumentID
            );

            $bulkDelete[] = $param;
        }

        // dd($bulkDelete);

        $bulkDelete = json_encode($bulkDelete, true);

        $p = array(
            "sessionKey" => $this->api->client->sessionKey
        );

        $res = $this->api->sendRequest($bulkDelete, $p, 1);

        if ($res["status"]["errorCode"] != 0) {
            return response()->json($res);
        }


        foreach ($datas as $key => $data) {
            if ($res["requests"][$key]["status"]["errorCode"] == 0) {
                $data->erplyDeleted = 1;
                $data->deleted = 1;
                $data->save();
            } else {
                //if error on documentID then this offer is deleted from erply
                if ($res["requests"][$key]["status"]["errorField"] == "documentID") {
                    //means offer deleted from erply
                    $data->erplyDeleted = 1;
                    $data->deleted = 1;
                    $data->save();
                }
            }
        }

        return response()->json($res);
    }

    public function getReceipt($req)
    {

        $isDebug = $req->debug ? $req->debug : 0;

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if ($currentWarehouse["status"] == 0) {
            return $this->failWithMessage("Invalid Warehouse ID!");
        }

        if ($req->warehouseID && $req->salesDocumentIDs) {

            //now first validating warehouse id
            $bulkOrder = explode(",", $req->salesDocumentIDs);

            $isA4 = 1;
            if (@$req->size == 'true') {
                $isA4 = 0;
            }

            $bulkPickingSlip = [];
            foreach ($bulkOrder as $key => $sid) {

                //now updating order as printed
                $updatePrint = SalesDocument::where("clientCode", $currentWarehouse["clientCode"])->where("warehouseID", $currentWarehouse["warehouseID"])->where("id", $sid)
                    ->update(
                        [
                            "isPrinted" => 1,
                            "printedDate" => Carbon::now(new \DateTimeZone('Australia/Sydney'))->format('Y-m-d H:i:s'),
                        ]
                    );

                //now for
                $data = SalesDocument:: //join("newsystem_employees", "newsystem_employees.employeeID", "newsystem_sales_documents.")

                    with(["SalesDetails" => function ($q) use ($currentWarehouse) {
                        $q->where("clientCode", $currentWarehouse["clientCode"]);
                    }])
                    // ->where("clientCode", $currentWarehouse["clientCode"]) //->where("type", "OFFER")
                    // ->where("warehouseID", $currentWarehouse["warehouseID"])
                    // ->where("salesDocumentID", $sid)
                    ->where("id", $sid)
                    ->orderBy("salesDocumentID", 'desc')
                    // ->select(["newsystem_sales_documents.*", "SalesDetails.productID as erplyID","SalesDetails.*"])
                    ->first();

                if ($data) {
                    $data->paperSize = $isA4;
                    $erplyWarehouse = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();
                    $axWarehouse = LiveWarehouseLocation::where("LocationID", $erplyWarehouse->code)->first();
                    $client = Customer::where("clientCode", $currentWarehouse["clientCode"])->where("customerID", $data->clientID)->first();
                    $schoolInfo = LiveProductVariation::where("erplyID", $data["SalesDetails"][0]["productID"])
                        ->where(function ($q) use ($currentWarehouse) {
                            $q->where("DefaultStore", $currentWarehouse["warehouseCode"])
                                ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                        })->first();
                    foreach ($data->salesDetails as $sl) {
                        if ($schoolInfo) {
                            continue;
                        }
                        if (!$schoolInfo) {
                            $schoolInfo = LiveProductVariation::where("erplyID", $sl->productID)
                                ->where(function ($q) use ($currentWarehouse) {
                                    $q->where("DefaultStore", $currentWarehouse["warehouseCode"])
                                        ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                                })->first();
                        }
                    }
                    $productDetails = array();

                    $totQty = 0;
                    // dd($data->SalesDetails);
                    foreach ($data->SalesDetails as $sd) {
                        $totQty += $sd->amount;
                        $axDetails = LiveProductVariation::where("erplyID", $sd->productID)
                            ->where(function ($q) use ($currentWarehouse) {
                                $q->where("DefaultStore", $currentWarehouse["warehouseCode"])
                                    ->orWhere("SecondaryStore", $currentWarehouse["warehouseCode"]);
                            })
                            ->first();

                        if (@$axDetails) {
                            $itemLocation = LiveItemLocation::where("warehouse", $axWarehouse->LocationID)->where("ERPLYSKU", @$axDetails->ERPLYSKU)->first();
                        }
                        // $itemLocation = LiveItemLocation::where("warehouse", $axWarehouse->LocationID)
                        //             ->where("ICSC", $axDetails->ICSC)
                        //             // ->where("Configuration", $axDetails->ITEMID)
                        //             // ->where("Item", $axDetails->ITEMID)
                        //             // ->where("Item", $axDetails->ITEMID)
                        //             ->first();
                        // $soh = LiveOnHandInventory::where("Warehouse", $axWarehouse->LocationID)->where("ERPLYSKU", $axDetails->ERPLYSKU)->first();
                        if (@$axDetails) {
                            $soh = LiveItemByLocation::where("ICSC", @$axDetails->ICSC)->where('Warehouse', $currentWarehouse["warehouseCode"])->first();
                        }

                        $lines = array(
                            "ICSC" => @$axDetails->ITEMID . '-' . @$axDetails->ColourID . '-' . @$axDetails->CONFIGID,
                            "ICSCBarcode" => @$axDetails->ITEMID . '' . @$axDetails->ColourID . '' . @$axDetails->SizeID,
                            "productName" => $sd->itemName,
                            "productName2" => @$axDetails->ItemName ?? $sd->itemName,
                            "itemID" => @$axDetails->ITEMID,
                            "configID" => @$axDetails->CONFIGID,
                            "Size" => @$axDetails->SizeID,
                            "colourID" => @$axDetails->ColourID,
                            "barcode" => @$axDetails->EANBarcode ?? $sd->productID,
                            "qty" => $sd->amount,
                            "location" => @$itemLocation->issueLocation ?? ''
                        );

                        if (@$soh) {
                            $lines["SOH"] = @$soh->AvailablePhysical;
                        } else {
                            $lines["SOH"] = 0;
                        }

                        $sohStatus = 'L';
                        if ($lines["SOH"] > 5 && $lines["SOH"] <= 10) {
                            $sohStatus = 'M';
                        }
                        if ($lines["SOH"] > 10) {
                            $sohStatus = 'H';
                        }
                        $lines["sohStatus"] = $sohStatus;

                        $productDetails[] = $lines;
                    }

                    // $productDetails = collect($productDetails)->sortBy('location')->values()->all();
                    $productDetails = collect($productDetails)->sortBy(function ($item) {
                        $parts = preg_split('/-/', $item['location']);
                        return array_map(function ($part) {
                            return is_numeric($part) ? (int) $part : strtolower($part); // Convert to lowercase
                        }, $parts);
                    });

                    $bulkPickingSlip[$key]["info"] = $data;
                    $bulkPickingSlip[$key]["axWarehouse"] = $axWarehouse;
                    $bulkPickingSlip[$key]["schoolInfo"] = $schoolInfo;
                    $bulkPickingSlip[$key]["productDetails"] = $productDetails;
                    $bulkPickingSlip[$key]["client"] = $client;
                    $bulkPickingSlip[$key]["totQty"] = $totQty;
                } else {
                    return response("Invalid Sales Order ID!!!");
                }
            }

            if ($isDebug == 1) {
                dd($bulkPickingSlip);
            }

            if (count($bulkPickingSlip) > 0) {
                if ($isA4 == 1) {
                    return view('prints.picking-slip', compact('bulkPickingSlip', 'isA4'));
                }

                return view('prints.picking-slip-click-collect', compact('bulkPickingSlip', 'isA4'));
            }
        }
        // dd("hello sir");
        if ($req->warehouseID && $req->salesDocumentID) {

            $bulkPickingSlip = [];

            $isA4 = 1;
            if ($req->size) {
                $isA4 = 0;
            }

            $wid = $req->warehouseID;
            //now for
            $data = SalesDocument:: //join("newsystem_employees", "newsystem_employees.employeeID", "newsystem_sales_documents.")
                with("SalesDetails") //->where("clientCode", $this->api->client->clientCode) //->where("type", "OFFER")
                ->where("warehouseID", $wid)
                ->where("id", $req->salesDocumentID)
                ->orderBy("salesDocumentID", 'desc')
                // ->select(["newsystem_sales_documents.*", "SalesDetails.productID as erplyID","SalesDetails.*"])
                ->first();
            if ($data) {
                $data->paperSize = $isA4;
                $erplyWarehouse = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $data->warehouseID)->first();
                $axWarehouse = LiveWarehouseLocation::where("LocationID", $erplyWarehouse->code)->first();
                $axWarehouse = LiveWarehouseLocation::where("erplyID", $wid)->first();
                $client = Customer::where("clientCode", $this->api->client->clientCode)->where("customerID", $data->clientID)->first();
                $schoolInfo = LiveProductVariation::where("erplyID", $data["SalesDetails"][0]["productID"])->first();
                // $schoolInfo = LiveProductGroup::where
                $productDetails = array();

                $totQty = 0;
                // dd($data->SalesDetails);
                foreach ($data->SalesDetails as $sd) {
                    $totQty += $sd->amount;
                    $axDetails = LiveProductVariation::where("erplyID", $sd->productID)->first();
                    $itemLocation = LiveItemLocation::where("ERPLYSKU", $axDetails->ERPLYSKU)->first();
                    // $itemLocation = LiveItemLocation::where("warehouse", $axWarehouse->LocationID)
                    //             ->where("ICSC", $axDetails->ICSC)
                    //             // ->where("Configuration", $axDetails->ITEMID)
                    //             // ->where("Item", $axDetails->ITEMID)
                    //             // ->where("Item", $axDetails->ITEMID)
                    //             ->first();
                    $soh = LiveOnHandInventory::where("Warehouse", $axWarehouse->LocationID)->where("ERPLYSKU", $axDetails->ERPLYSKU)->first();

                    $lines = array(
                        "ICSC" => $axDetails->ITEMID . '-' . $axDetails->ColourID . '-' . $axDetails->SizeID,
                        "ICSCBarcode" => $axDetails->ITEMID . '' . $axDetails->ColourID . '' . $axDetails->SizeID,
                        "productName" => $sd->itemName,
                        "Size" => $axDetails->SizeID,
                        "qty" => $sd->amount,
                        "location" => $itemLocation ? $itemLocation->issueLocation : ''
                    );

                    if ($soh) {
                        $lines["SOH"] = $soh->AvailablePhysical;
                    }

                    $productDetails[] = $lines;
                }

                $productDetails = collect($productDetails);

                $bulkPickingSlip[0]["info"] = $data;
                $bulkPickingSlip[0]["axWarehouse"] = $axWarehouse;
                $bulkPickingSlip[0]["schoolInfo"] = $schoolInfo;
                $bulkPickingSlip[0]["productDetails"] = $productDetails;
                $bulkPickingSlip[0]["client"] = $client;
                $bulkPickingSlip[0]["totQty"] = $totQty;

                // dd($bulkPickingSlip);
                return view('prints.picking-slip', compact('bulkPickingSlip', 'isA4'));

                // return view('prints.picking-slip', compact('data', 'axWarehouse','schoolInfo','productDetails','client','totQty'));
            } else {
                return response("Invalid Sales Order ID!!!");
            }
        }
    }

    //get payment status form erply and update

    private function updatePaymentStatus($ids, $currentWarehouse)
    {

        //first getting clientcode details form order



        $ids =  explode(",", $ids);
        $sids = '';
        foreach ($ids as $key => $id) {
            $data = SalesDocument::where("id", $id)->first();
            $sids .=  $key > 1 ? ',' . $data->salesDocumentID : $data->salesDocumentID . ',';
        }

        $chk = substr($sids, -1);
        if ($chk == ',') {
            $sids = rtrim($sids, ',');
        }

        //getting sales document from erply and update payment status



        // $data = SalesDocument::where("clientCode", "605325")->where("salesDocumentID", $id[0])->where("warehouseID", $warehouseID)->first();

    }
}
