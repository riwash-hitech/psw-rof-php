<?php
namespace App\Http\Controllers\PswClientLive\Services;

use App\Http\Controllers\Services\EAPIService; 
use App\Traits\AxTrait; 
use App\Models\PswClientLive\Local\LiveProductCategory;
use App\Models\PswClientLive\Local\LiveProductColor;
use App\Models\PswClientLive\Local\LiveProductGenericMatrix;
use App\Models\PswClientLive\Local\LiveProductGenericVariation;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductSize;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use App\Models\PswClientLive\Local\TempProduct;
use App\Models\PswClientLive\Local\TempProductGeneric;
use App\Models\PswClientLive\Product;
use App\Models\PswClientLive\ProductGeneric;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\DB;

class AxResyncService{

    use AxTrait, ResponseTrait; 
    protected $api;
    public function __construct(EAPIService $api){
        $this->api = $api; 
    }

    public function resyncFromAx(){
        $store = LiveWarehouseLocation::where("ENTITY", $this->api->client->ENTITY)->pluck("LocationID")->toArray();
        $data = LiveProductVariation::where("axResync", 1)->limit(200)->pluck("ERPLYSKU")->toArray();
        // ->whereNoIn("DefaultStore", $store)

        // dd($data);
        if(count($data) < 1){
            info("All Product Re-Syncced from AX.");
            return response("All Product Re-Syncced from AX.");
        }
        $axDatas = Product::whereIn("ERPLY SKU", $data)->get();
        if($axDatas->isEmpty()){
            info("Product Not Found in AX.");
            return response("Product Not Found in AX.");
        }

        $this->saveResyncProductToTemp($axDatas, true);
        // foreach($data as $d){
        //     LiveProductVariation::where("ERPLYSKU", $d)->update(["axResync" => 0]);
        // }
        info("AX To Synccare : Resyncing Special Cron.............................");
        return response("AX To Synccare : Resyncing Special Cron.............................");

    }

    public function resyncByWebSkuProduct($req, $websku = '', $flag=false){
        
        if($flag == false){
        
            if($req->websku){
                $products = Product::where("WEB SKU", $req->websku)->get();
                // dd($products);
                $this->saveResyncProductToTemp($products);
            }
        }
        else{
            if($websku != ''){
                $products = Product::where("WEB SKU", $websku)->get();
                $this->saveResyncProductToTemp($products);
            }
        }

        
        return $this->successWithMessage("Product By WebSku Resyncced Successfully.");
    }

    public function resyncByWebSkuGenericProduct($req, $sku = '', $flag = false){
        if($flag == false){
            $datas = ProductGeneric::where("ERPLY SKU", $req->erplysku)->get();
            if($datas){
                $this->createOrUpdateGenericProductV2($datas);
                return $this->successWithMessage("Generic Product Resync By ERPLY SKU.");
            }
        }
        if($flag == true){
            $datas = ProductGeneric::where("ERPLY SKU", $sku)->get();
            if($datas){
                $this->createOrUpdateGenericProductV2($datas);
                return $this->successWithMessage("Generic Product Resync By ERPLY SKU.");
            }
        }

        return $this->successWithMessage("Generic Product Not Found.");
    }

    public function resyncBySchool($req){
        
        if($req->schoolID){

            $schoolProducts = Product::where("School ID", $req->schoolID)->get();
            // dd($schoolProducts);
            $this->saveResyncProductToTemp($schoolProducts);
            return $this->successWithMessage("Resync By School Success.");
            return response("Resyncc Success.");
        }

        if($req->storeID){

            $schoolProducts = Product::where("Default Store", $req->storeID)->get();
            // dd($schoolProducts);
            $this->saveResyncProductToTemp($schoolProducts);
            return $this->successWithMessage("Resync By Store Location Success.");
            return response("Resyncc Success.");
        }
    }

    public function getNotSynccedGenericProduct(){

        //syncced sku
        $synccarePro = LiveProductGenericMatrix::pluck("ERPLYSKU")->toArray();

        //now getting non syncced 
        $nonSynccedPro = ProductGeneric::whereNotIn("ERPLY SKU", $synccarePro)->get();

        if(count($nonSynccedPro) > 0){
            $this->createOrUpdateGenericProductV2($nonSynccedPro);
            return $this->successWithMessage("Generic Product Syncced.");
        }

        return $this->successWithMessage("All Generic Product Syncced.");

    }

    private function saveResyncProductToTemp($datas, $isSpecial = false){

        foreach($datas as $value){

            $tempPayload = array(
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
                "RetailSalesPrice2" => $value['Retail Sales Price2'] ? $value['Retail Sales Price2'] : "0.00",
                "RetailSalesPriceExclGST" => $value['Retail Sales Price excl GST'] ? $value['Retail Sales Price excl GST'] : "0.00",
                "RetailSalesPriceExclGST2" => $value['Retail Sales Price excl GST2'] ? $value['Retail Sales Price excl GST2'] : "0.00",
                "CostPrice" => $value['Cost Price'] ,
                "DefaultStore" => $value['Default Store'] ,
                "SecondaryStore" => $value['Secondary Store'] ,
                "ERPLYFLAG" => $value['ERPLY FLAG'] ,
                // "ERPLYFLAGModified" => $value['ERPLY FLAG Modified'],
                "AvailableForPurchase" => $value['Available for Purchase'],
                "WebEnabled" => $value['Web Enabled'] ,
                "SOFLastModified" => $value['SOF Last Modified'],
                "ItemLastModified" => $value['Item Last Modified'],
                "SchoolLastModified" => $value['School Last Modified'],
                "PSWPRICELISTITEMCATEGORY" => $value['PSW_PRICELISTITEMCATEGORY'] ,
                "Category_Name" => $value['CATEGORYNAME'],
                // "erplyPending" => 1,
                // "assortmentPending" => 1,
                // "erplyPending" => 1,
                "ICSC" => $value['ICSC'],
                "genericFlag" => $value['Generic Flag'],
                "erplyEnabled" => $value['ERPLY_Enabled'],
                "customItemName" => $value['Custom Item Name'],
                "receiptDescription" => $value['ReceiptDescription'],
            );

            if($isSpecial == true){
                LiveProductVariation::where("ERPLYSKU", $value['ERPLY SKU'])->update(
                    [
                        "axResync" => 0
                    ]
                );
            }

            TempProduct::create($tempPayload);
        }
    }

    // public function createOrupdateProduct($datas, $isSpecial = false){

    //     foreach($datas as $value){

    //         $vAassPending = 0;
    //         $check = LiveProductVariation::where("ERPLYSKU", $value['ERPLY SKU'])->first();
    //         if($check){
    //             $old = $check->DefaultStore."_".$check->SecondaryStore;
    //             $new = $value['Default Store']."_".$value['Secondary Store'] ? $value['Secondary Store'] : '';
    //             if($old != $new){
    //                 $vAassPending = 1;
    //             }
    //         }

    //         $vDetails = [
    //             "SchoolName" => trim($value['School Name']) ,
    //             "SchoolID" => $value['School ID'],
    //             "CustomerGroup" => $value['Customer Group'] ,
    //             "ERPLYSKU" => $value['ERPLY SKU'] ,
    //             "WEBSKU" => $value['WEB SKU'] ,
    //             "ITEMID" => $value['ITEMID'] ,
    //             "ItemName" => trim($value['Item Name']),
    //             "ColourID" => $value['ColourID'],
    //             "ColourName" => trim($value['Colour Name']),
    //             "SizeID" => $value['SizeID'] ,
    //             "CONFIGID" => $value['CONFIGID'] ,
    //             "ConfigName" => trim($value['Config Name']),
    //             "EANBarcode" => $value['EAN Barcode'] ,
    //             "SOFTemplate" => $value['SOF Template'] ,
    //             "SOFName" => $value['SOF Name'],
    //             "SOFOrder" => $value['SOF Order'] ,
    //             "SOFStatus" => $value['SOF Status'] ,
    //             "PLMStatus" => $value['PLM Status'] ,
    //             "ProductType" => $value['Product Type'] ,
    //             "ProductSubType" => $value['Product Sub Type'],
    //             "Supplier" => $value['Supplier']  ,
    //             "Gender" => $value['Gender']  ,
    //             "CategoryName" => $value['Category Name'] ,
    //             "ItemWeightGrams" => $value['Item Weight - grams'],
    //             "RetailSalesPrice" => $value['Retail Sales Price'] ? $value['Retail Sales Price'] : "0.00",
    //             "RetailSalesPriceExclGST" => $value['Retail Sales Price excl GST'] ? $value['Retail Sales Price excl GST'] : "0.00",
    //             "CostPrice" => $value['Cost Price'] ,
    //             "DefaultStore" => $value['Default Store'] ,
    //             "SecondaryStore" => $value['Secondary Store'] ,
    //             "ERPLYFLAG" => $value['ERPLY FLAG'] ,
    //             // "ERPLYFLAGModified" => $value['ERPLY FLAG Modified'],
    //             "AvailableForPurchase" => $value['Available for Purchase'],
    //             "WebEnabled" => $value['Web Enabled'] ,
    //             "SOFLastModified" => $value['SOF Last Modified'],
    //             "ItemLastModified" => $value['Item Last Modified'],
    //             "PSWPRICELISTITEMCATEGORY" => $value['PSW_PRICELISTITEMCATEGORY'] ,
    //             "Category_Name" => $value['CATEGORYNAME'],
    //             "erplyPending" => 1,
    //             // "assortmentPending" => 1,
    //             "ICSC" => $value['ICSC'],
    //             "genericProduct" => $value['Generic Flag'],
    //             "erplyEnabled" => $value['ERPLY_Enabled'],
    //             "customItemName" => $value['Custom Item Name'],
    //             "receiptDescription" => $value['ReceiptDescription'],
    //         ];

    //         if($vAassPending == 1){
    //             $vDetails["assortmentPending"] = 1;
    //         }

    //         if($isSpecial == true){
    //             $vDetails["axResync"] = 0;
    //         }
    //         //first update variation product
    //         LiveProductVariation::updateOrcreate(
    //             [
    //                 "ERPLYSKU" => $value['ERPLY SKU']
    //             ],
    //             $vDetails
    //         );

            
    //         //Now Update Matrix Product and variation pending 1 and matrix pending 1
    //         $flag = LiveProductVariation::where('WEBSKU', $value['WEB SKU'])->where('WebEnabled', '1')->first();
    //         $isActive = 1;
    //         if(!$flag){
    //             $isActive = 0;
    //         }

    //         $flag2 = LiveProductVariation::where('WEBSKU', $value['WEB SKU'])->where('erplyEnabled', 1)->first();

    //         $erplyEnabled = 0;
    //         if($flag2){
    //             $erplyEnabled = 1;
    //         }

    //         $mAassPending = 0;
    //         $mCheck = LiveProductMatrix::where("WEBSKU", $value['WEB SKU'])->first();
    //         $secondStore = LiveProductVariation::where("WEBSKU", $value['WEB SKU'])->where("SecondaryStore",'<>','')->first();
    //         $ss = '';
    //         if($mCheck){
    //             $old = $mCheck->SecondaryStore ? $mCheck->SecondaryStore : '';
                
    //             if($secondStore){
    //                 $ss = $secondStore->SecondaryStore;
    //             }
    //             $new = $value['Default Store']."_".$ss;
    //             // if($old == ''){
    //             //     info($mCheck->WEBSKU." Empty Old Value");
    //             // }
    //             if($mCheck->DefaultStore."_".$old != $new){
    //                 $mAassPending = 1;
    //                 info("******************************************************************************************************************** ".$old ." != " . $new);
    //             }
    //         }

    //         $mDetails = array(
    //             "SchoolName" => trim($value['School Name']) ,
    //             "SchoolID" => $value['School ID'],
    //             "CustomerGroup" => $value['Customer Group'] ,
    //             "ERPLYSKU" => "",
    //             "WEBSKU" => $value['WEB SKU'] ,
    //             "ITEMID" => $value['ITEMID'] ,
    //             "ItemName" => trim($value['Item Name']),
    //             "ColourID" => $value['ColourID'],
    //             "ColourName" => trim($value['Colour Name']),
    //             "SizeID" => $value['SizeID'] ,
    //             "CONFIGID" => $value['CONFIGID'] ,
    //             "ConfigName" => trim($value['Config Name']),
    //             // "EANBarcode" => $value['EAN Barcode'] ,
    //             "SOFTemplate" => $value['SOF Template'] ,
    //             "SOFName" => trim($value['SOF Name']),
    //             "SOFOrder" => $value['SOF Order'] ,
    //             "SOFStatus" => $value['SOF Status'] ,
    //             // "PLMStatus" => $value['PLM Status'] ,
    //             "ProductType" => $value['Product Type'] ,
    //             "ProductSubType" => $value['Product Sub Type'],
    //             "Supplier" => $value['Supplier'] ,
    //             "Gender" => $value['Gender'] ,
    //             "CategoryName" => $value['Category Name'] ,
    //             "ItemWeightGrams" => $value['Item Weight - grams'],
    //             "RetailSalesPrice" => $value['Retail Sales Price'] ? $value['Retail Sales Price'] : "0.00",
    //             "RetailSalesPriceExclGST" => $value['Retail Sales Price excl GST'] ? $value['Retail Sales Price excl GST'] : "0.00",
    //             "CostPrice" => $value['Cost Price'] ,
    //             "DefaultStore" => $value['Default Store'] ,
    //             "SecondaryStore" => $ss,
    //             "ERPLYFLAG" => $value['ERPLY FLAG'] ,
    //             // "ERPLYFLAGModified" => $value['ERPLY FLAG Modified'],
    //             "AvailableForPurchase" => $value['Available for Purchase'],
    //             "WebEnabled" => $isActive,
    //             "SOFLastModified" => $value['SOF Last Modified'],
    //             "ItemLastModified" => $value['Item Last Modified'],
    //             "PSWPRICELISTITEMCATEGORY" => $value['PSW_PRICELISTITEMCATEGORY'] ,
    //             "Category_Name" => $value['CATEGORYNAME'],
    //             "erplyPending" => 1, 
    //             // "assortmentPending" => 1,
    //             "variationPending" => 1, 
    //             "genericProduct" => $value['Generic Flag'],
    //             "erplyEnabled" => $erplyEnabled,
    //             "customItemName" => $value['Custom Item Name'],
    //             "receiptDescription" => $value['ReceiptDescription'],
                
    //         );
    //         if($mAassPending == 1){
    //             $mDetails["assortmentPending"] = 1;
    //         }
            
    //         LiveProductMatrix::updateOrcreate(
    //             [
    //                 "WEBSKU" => trim($value['WEB SKU']) 
    //             ],
    //             $mDetails
    //         );

    //         //now checking product group

    //         // if(!LiveProductGroup::where("SchoolID", trim($value['School ID']))->first()){
    //         LiveProductGroup::updateOrcreate(
    //             [
    //                 "SchoolID" => trim($value['School ID']), 
    //             ],
    //             [
    //                 "SchoolID" => trim($value['School ID']),
    //                 "SchoolName" => trim($value['School Name']), 
    //                 "WebEnabled" => $isActive,
    //                 "parentSchoolGroup" => $value["Default Store"],
    //                 "pendingProcess" => 1
    //             ]
    //         );
    //         // }

    //         //Now checking product colour
    //         $checkColor = LiveProductColor::where('name', trim($value['Colour Name']))->first();
    //         if($checkColor){
    //             if($value['Colour Name'] != ''){
    //                 LiveProductColor::where("id", $checkColor->id)->update(["name" => trim($value['Colour Name']),"colourID" => $value['ColourID'], "pendingProcess" => 0 ]);
    //             }
    //         }else{
    //             if($value['Colour Name'] != ''){
    //                 LiveProductColor::create(["name" => trim($value['Colour Name']),"colourID" => $value['ColourID'], "pendingProcess" => 1 ]);
    //             }
    //         }

    //         // }

    //         //Now checking product size
    //         $checkSize = LiveProductSize::where('name', trim($value['SizeID']))->first();
    //         if(!$checkSize){
    //             if(trim($value['SizeID']) != ''){
    //                 LiveProductSize::create(["name" => trim($value['SizeID']), "pendingProcess" => 1 ]);
    //             }
    //         }

    //         //For Category
    //         $checkCat = LiveProductCategory::where('name', trim($value['Product Type']))->first();
    //         if(!$checkCat){
    //             if(trim($value['Product Type'])){
    //                 LiveProductCategory::updateOrcreate(
    //                     [
    //                         'name' => trim($value['Product Type'])
    //                     ],
    //                     [
    //                         'name' => trim($value['Product Type']),
    //                         'pendingProcess' => 1
    //                     ]
    //                 );
    //             }
    //         }

 
    //     }
    // }

    // public function createOrUpdateGenericProduct($datas ){ 
        
    //     // dd($datas);
    //     foreach($datas as $value){
             
    //             $details = array(
    //                 "ERPLYSKU" =>    $value["ERPLY SKU"],
    //                 "ITEMID" =>  $value["ITEMID"],
    //                 "ItemName" =>   $value["Item Name"],
    //                 "ColourID" =>    $value["ColourID"],
    //                 "ColourName" =>  $value["Colour Name"],
    //                 "SizeID" =>  $value["SizeID"],
    //                 "CONFIGID" =>    $value["CONFIGID"],
    //                 "ConfigName" =>  $value["Config Name"],
    //                 "EANBarcode" =>  $value["EAN Barcode"], 
    //                 "Supplier" =>    $value["Supplier"],
    //                 "CategoryName" =>    $value["Category Name"],
    //                 "RetailSalesPrice" =>    $value["Retail Sales Price"],
    //                 "RetailSalesPriceExclGST" => $value["Retail Sales Price excl GST"],
    //                 "CostPrice" =>   $value["Cost Price"],
    //                 "ItemLastModified" => $value["Item Last Modified"],
    //                 "ProductType"=>$value["Prod Type"],
    //                 "ICSC"=>$value["ICSC"], 
    //                 "erplyPending"=> 1,
    //                 "aPending"=> 1, 
    //             );
    //             //first update variation product
    //             LiveProductGenericVariation::updateOrcreate(
    //                 [
    //                     "ICSC" => $value['ICSC']
    //                 ],
    //                 $details
    //             );
            
            

            
    //         //Now Update Matrix Product and variation pending 1 and matrix pending 1
    //         // $flag = LiveProductVariation::where('WEBSKU', $value['ERPLY SKU'])->where('WebEnabled', '1')->first();
    //         // $isActive = 1;
    //         // if(!$flag){
    //         //     $isActive = 0;
    //         // }
    //         $details["variationPending"] = 1;
    //         LiveProductGenericMatrix::updateOrcreate(
    //             [
    //                 "ERPLYSKU" => trim($value['ERPLY SKU']) 
    //             ],
    //             $details
    //         );

            
    //         //Now checking product colour
    //         $checkColor = LiveProductColor::where('name', trim($value['Colour Name']))->first();
    //         // if(!$checkColor){
    //         // if(trim($value['Colour Name']) != ''){
    //         //     LiveProductColor::create(["name" => trim($value['Colour Name']), "colourID" => $value["ColourID"], "pendingProcess" => $checkColor ? 0 : 1 ]);
    //         // }
    //         if($checkColor){
    //             if($value['Colour Name'] != ''){
    //                 LiveProductColor::where("id", $checkColor->id)->update(["name" => trim($value['Colour Name']),"colourID" => $value['ColourID'], "pendingProcess" => 0 ]);
    //             }
    //         }else{
    //             if($value['Colour Name'] != ''){
    //                 LiveProductColor::create(["name" => trim($value['Colour Name']),"colourID" => $value['ColourID'], "pendingProcess" => 1 ]);
    //             }
    //         }
    //         // }

    //         //Now checking product size
    //         $checkSize = LiveProductSize::where('name', trim($value['SizeID']))->first();
    //         if(!$checkSize){
    //             if(trim($value['SizeID']) != ''){
    //                 LiveProductSize::create(["name" => trim($value['SizeID']), "pendingProcess" => 1 ]);
    //             }
    //         }

    //         //For Category
    //         $checkCat = LiveProductCategory::where('name', trim($value['Product Type']))->first();
    //         if(!$checkCat){
    //             if(trim($value['Product Type'] != '')){
    //                 LiveProductCategory::updateOrcreate(
    //                     [
    //                         'name' => trim($value['Product Type'])
    //                     ],
    //                     [
    //                         'name' => trim($value['Product Type']),
    //                         'pendingProcess' => 1
    //                     ]
    //                 );
    //             }
    //         }

 
    //     }
    // }

    public function createOrUpdateGenericProductV2($datas){ 
        
        // dd($datas);
        foreach($datas as $value){
            try{
                DB::transaction(function ()use($value) {
                    $details = array(
                        "ERPLYSKU" =>    $value["ERPLY SKU"],
                        "ITEMID" =>  $value["ITEMID"],
                        "ItemName" =>   $value["Item Name"],
                        "ColourID" =>    $value["ColourID"],
                        "ColourName" =>  $value["Colour Name"],
                        "SizeID" =>  $value["SizeID"],
                        "CONFIGID" =>    $value["CONFIGID"],
                        "ConfigName" =>  $value["Config Name"],
                        "EANBarcode" =>  $value["EAN Barcode"], 
                        "Supplier" =>    $value["Supplier"],
                        "CategoryName" =>    $value["Category Name"],
                        "RetailSalesPrice" =>    $value["Retail Sales Price"],
                        "RetailSalesPriceExclGST" => $value["Retail Sales Price excl GST"],
                        "CostPrice" =>   $value["Cost Price"],
                        "ItemLastModified" => $value["Item Last Modified"],
                        "ProductType"=>$value["Prod Type"],
                        "ICSC"=>$value["ICSC"], 
                        "pendingProcess"=> 1, 
                        
                    );
                    TempProductGeneric::create($details);
                
                });
            }catch(Exception $e){
                info("An error occurred while Product Temp to Current System :  ". $e->getMessage()." SKU ". $value->ERPLYSKU);
            }
             
        }
    }


    /********************** DETECTING DELETED AX PRODUCT ****************************/
    public function detectDeletedProductAX(){
        
        $datas = LiveProductMatrix::withCount("variations")->where("axCheckFlag", 1)->limit(10)->get();
        // dd($datas);
        if($datas->isEmpty()){
            //ALL PRODUCT CHECKED TO AX
            //NOW AGAIN REPEATING THIS PROCEDURE
            LiveProductMatrix::where("axCheckFlag", 0)->update(["axCheckFlag" => 1]);
            die;
        }
        foreach($datas as $data){
            $axDataCount = Product::where("WEB SKU", $data->WEBSKU)->get()->count();
             
            if($axDataCount == 0){
                //All Product Deleted
                LiveProductMatrix::where("WEBSKU", $data->WEBSKU)->update(["axDeleted" => 1]);
                LiveProductVariation::where("WEBSKU", $data->WEBSKU)->update(["axDeleted" => 1]);
                
            }else{
                LiveProductMatrix::where("WEBSKU", $data->WEBSKU)->update(["axDeleted" => 0]);
                //PARTIALLY PRDUCT DELETED
                $this->checkPartiallyDelete($data->WEBSKU, $axDataCount != $data->variations_count ? 1 : 0); 
            } 

            $data->axCheckFlag = 0;
            $data->save();

        }

        info("Detector Code Executed Successfully.");
        return response("Detector Code Executed Successfully.");
    }


    protected function checkPartiallyDelete($websku, $isResync = 0){

        $datas = LiveProductVariation::where("WEBSKU", $websku)->get();

        foreach($datas as $data){
            $axDatas = Product::where("ERPLY SKU", $data->ERPLYSKU)->get();
            if($axDatas->isEmpty()){
                $data->axDeleted = 1;
                $data->save();
            }else{
                $data->axDeleted = 0;
                $data->save();
            }
        }

        //now resynccing this product to synccare
        // if($isResync == 1){
        //     $this->resyncByWebSkuProduct([], $websku, true);
        // }
        
    }

    /********************** DETECTING DELETED AX GENERIC PRODUCT ****************************/
    public function detectGenericDeletedProductAX(){
        
        $datas = LiveProductGenericMatrix::withCount("variations")->where("axCheckFlag", 1)->limit(10)->get();
        // dd($datas);
        if($datas->isEmpty()){
            //ALL PRODUCT CHECKED TO AX
            //NOW AGAIN REPEATING THIS PROCEDURE
            LiveProductGenericMatrix::where("axCheckFlag", 0)->update(["axCheckFlag" => 1]);
            die;
        }
        foreach($datas as $data){
            $axDataCount = ProductGeneric::where("ERPLY SKU", $data->ERPLYSKU)->get()->count();
             
            if($axDataCount == 0){
                //All Product Deleted
                LiveProductGenericMatrix::where("ERPLYSKU", $data->ERPLYSKU)->update(["axDeleted" => 1]);
                LiveProductGenericVariation::where("ERPLYSKU", $data->ERPLYSKU)->update(["axDeleted" => 1]);
                
            }else{
                LiveProductGenericMatrix::where("ERPLYSKU", $data->ERPLYSKU)->update(["axDeleted" => 0]);
                //PARTIALLY PRDUCT DELETED
                $this->checkPartiallyGenericDelete($data->ERPLYSKU, $axDataCount != $data->variations_count ? 1 : 0); 
            }

            $data->axCheckFlag = 0;
            $data->save();

        }

        info("Generic Detector Code Executed Successfully.");
        return response("Detector Code Executed Successfully.");
    }

    protected function checkPartiallyGenericDelete($erplysku, $isResync = 0){

        $datas = LiveProductGenericVariation::where("ERPLYSKU", $erplysku)->get();

        foreach($datas as $data){
            $axDatas = ProductGeneric::where("ICSC", $data->ICSC)->get();
            if($axDatas->isEmpty()){
                $data->axDeleted = 1;
                $data->save();
            }else{
                $data->axDeleted = 0;
                $data->save();
            }
        }

        //now resynccing this product to synccare
        // if($isResync == 1){
        //     $this->resyncByWebSkuGenericProduct([], $erplysku, true);
        // }
        
    }




 

}