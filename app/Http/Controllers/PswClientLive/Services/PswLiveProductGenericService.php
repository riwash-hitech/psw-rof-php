<?php
namespace App\Http\Controllers\PswClientLive\Services;
 
use App\Models\PswClientLive\Local\AxSyncDatetime; 
use App\Models\PswClientLive\Local\LiveProductCategory;
use App\Models\PswClientLive\Local\LiveProductColor; 
use App\Models\PswClientLive\Local\LiveProductSize; 
use App\Models\PswClientLive\Local\LiveProductGenericMatrix;
use App\Models\PswClientLive\Local\LiveProductGenericVariation; 
use App\Models\PswClientLive\Local\TempProductGeneric; 
use App\Models\PswClientLive\ProductGeneric; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;
use App\Traits\ColourSizeTrait;
use Exception;

class PswLiveProductGenericService{

    use ResponseTrait, ColourSizeTrait;
    // protected $psw_live_product;
    // protected $psw_generic_product;
    // protected $temp_product;
    // protected $tem_generic_product;
    // protected $currentsystem_product_matrix_live;
    // protected $currentsystem_product_variation_live;

    // protected $currentsystem_product_generic_matrix_live;
    // protected $currentsystem_product_generic_variation_live;

    // public function __construct(ProductGeneric $psw_generic_product, 
    //                             Product $psw_product,
    //                             TempProductGeneric $tem_generic_product, 
    //                             TempProduct $temp_product, 
    //                             LiveProductMatrix $currentsystem_product_matrix, 
    //                             LiveProductVariation $currentsystem_product_variation,
    //                             LiveProductGenericMatrix $currentsystem_product_generic_matrix_live,
    //                             LiveProductGenericVariation $currentsystem_product_generic_variation_live
    //                             ){
    //     $this->psw_live_product = $psw_product;
    //     $this->psw_generic_product = $psw_generic_product;
    //     $this->temp_product = $temp_product;
    //     $this->tem_generic_product = $tem_generic_product;
    //     $this->currentsystem_product_matrix_live = $currentsystem_product_matrix;
    //     $this->currentsystem_product_variation_live = $currentsystem_product_variation;
    // }

    function escapeFunc($val){
        
        // $val = str_replace("'","\'",$val);
        // $val = str_replace('"','\"',$val);
        // $val = trim($val);
        return DB::getPdo()->quote($val);
        return $val;
    }


    public function makeProductFile($req){
        info("************************************** Product Generic by Last Modified date : Ax to Synccare Cron Called.***************************************");
        $limit = $req->limit ? $req->limit : 100000;
        $path = public_path('PswLiveTemp');

        File::delete($path . '/productDevGeneric.txt');

        if (!File::exists($path)) {

            File::makeDirectory($path);

        }
        $lastModified = AxSyncDatetime::where("id", 1)->first()->product_genericV2;
        // echo $lastModified;
        // die;
        if('0000-00-00 00:00' == $lastModified || '0000-00-00' == $lastModified || is_null($lastModified) == true || $lastModified == '0000-00-00 00:00:00.000'){
            $datas = ProductGeneric::orderBy("Item Last Modified", "asc")->limit($limit)->get();
           
        }else{
            $datas = ProductGeneric::where("Item Last Modified", '>=', $lastModified)->orderBy("Item Last Modified", "asc")->limit($limit)->get();
            
        }
        info("********************************************************************".count($datas)." Products Generic read from AX... ***********************************************");
        // $products = $this->psw_generic_product->get();
        // dd($products);
        $chunkProduct = $datas->chunk(500);

        foreach ($chunkProduct as $cpro) {

            $content = 'Insert into `temp_product_generic`(`ERPLYSKU`,
                    `ITEMID`,
                    `ItemName`,
                    `ColourID`,
                    `ColourName`,
                    `SizeID`,
                    `CONFIGID`,
                    `ConfigName`,
                    `EANBarcode`,
                    `Supplier`,
                    `CategoryName`,
                    `RetailSalesPrice`,
                    `RetailSalesPriceExclGST`,
                    `CostPrice`,
                    `ItemLastModified`,
                    `ICSC`,
                    `ProductType`
                    ) VALUES ';
            
            $q = null;

            foreach ($cpro as $key => $value) {

                //$compareField = $value->STOCKCODE . '-' . (int)$value->LOCNO . '-' . (int)$value->INSTOCKQTY . '-' . (int)$value->SALESORDQTY . '-' . (int)$value->VIRTSTOCK;

                $key = $cpro->last() == $value ? ';' : ',';
                if($chunkProduct->last() == $cpro){
                    if($cpro->last() == $value){
                        $key = '';
                    }
                }

                $q .= '( "'. $value['ERPLY SKU'] . '",
                        "' . $value['ITEMID'] . '",
                        "' . $value['Item Name'] . '",
                        "' . $value['ColourID'] . '",
                        ' . $this->escapeFunc($value['Colour Name']) . ',
                        "' . $value['SizeID'] . '",
                        "' . $value['CONFIGID'] . '",
                        ' . $this->escapeFunc($value['Config Name']) . ',
                        "' . $value['EAN Barcode'] . '",
                        "' . $value['Supplier'] . '",
                        "' . $value['Category Name'] . '",
                        "' . $value['Retail Sales Price'] . '",
                        "' . $value['Retail Sales Price excl GST'] . '",
                        "' . $value['Cost Price'] . '",
                        "' . $this->makeNullDate($value['Item Last Modified']) . '", 
                        "' . $value['ICSC'] . '",
                        "' . $value['Prod Type'] . '"
                      )' . $key;

            }

            $content = $content . '' . $q . '' . "\n";

            File::append($path . '/productDevGeneric.txt', $content);

        }
        
        info("******************************************************************** Product Generic File Generated Successfully. ***********************************************");
        return $this->readProductFileAndStore();
        // "' . $value['ERPLY FLAG Modified'] . '",
        // return $this->successWithMessage("Product Generic File Generated Successfully.");
    }

    public function readProductFileAndStore(){
        $path = public_path('PswLiveTemp/productDevGeneric.txt');

        if (File::exists($path)) { 
              
            $check = TempProductGeneric::where("pendingProcess", 1)->first();
            if(!$check){
                TempProductGeneric::truncate();
            }
             
            return $this->processFile($path); 
            
        } else{
            echo "no file";
            die;
        }
    }

    protected function processFile($path){ 

        info("******************************************************************** Product Generic File Processing... ***********************************************");
        $file = File::get($path);
         
        $sqls = explode(";\n", $file); 
        // dd($sqls);
        foreach ($sqls as $sql) {
              
            if ($sql != '') { 
                DB::connection('mysql2')->select($sql); 
            }
            
           
        }

        $lastSyncDatetime = TempProductGeneric::orderBy("ItemLastModified", 'desc')->first()->ItemLastModified;
        AxSyncDatetime::where("id", 1)->where("product_genericV2", "<=", $lastSyncDatetime)->update([ "product_genericV2" => $lastSyncDatetime ]);

        info("******************************************************************** Product Generic File Executed Successfully. ***********************************************");

        return response("******************************************************************** Product Generic File Executed Successfully. ***********************************************");

        // return $this->successWithMessage("Product Generic File Executed Successfully.");
  
    }

    private function makeNullDate($date){
        if($date == ''){
            return '0000-00-00';
        }
        return $date;
    }

    public function syncTempToCurrentsystemGenericMatrix(){
        return $this->syncToCurrentTable();
        die;
        //get data from temp table
        $temp_product = TempProductGeneric::where('matrixPending', 1)->groupBy('ERPLYSKU')->limit(500)->get();
        // return $temp_product;

        foreach($temp_product as $temp_p){
            // $flag = $this->temp_product->where('WEBSKU', $temp_p->WEBSKU)->where('WebEnabled', '1')->first();
            // $isActive = 1;
            // if(!$flag){
            //     $isActive = 0;
            // }
            // $checkMatrix = LiveProductGenericMatrix::where("ERPLYSKU", trim($temp_p->ERPLYSKU))->first();
            // if($checkMatrix){

                
            LiveProductGenericMatrix::updateOrcreate(
                [
                    "ERPLYSKU" => $temp_p->ERPLYSKU
                ],
                [
                    "ERPLYSKU" => $temp_p->ERPLYSKU,
                    "ITEMID" =>  trim($temp_p->ITEMID),
                    "ItemName" =>   trim($temp_p->ItemName),
                    "ColourID" =>    trim($temp_p->ColourID),
                    "ColourName" =>  trim($temp_p->ColourName),
                    "SizeID" =>  trim($temp_p->SizeID),
                    "CONFIGID" =>    trim($temp_p->CONFIGID),
                    "ConfigName" =>  trim($temp_p->ConfigName),
                    "EANBarcode" =>  trim($temp_p->EANBarcode),
                    "ProductType" => trim($temp_p->ProductType),
                    "Supplier" =>    trim($temp_p->Supplier),
                    "CategoryName" =>    trim($temp_p->CategoryName),
                    "RetailSalesPrice" =>    trim($temp_p->RetailSalesPrice == '' ? '0.00' : $temp_p->RetailSalesPrice),
                    "RetailSalesPriceExclGST" => trim($temp_p->RetailSalesPriceExclGST == '' ? '0.00' : $temp_p->RetailSalesPriceExclGST),
                    "CostPrice" =>   trim($temp_p->CostPrice == '' ? '0.00' : $temp_p->CostPrice),
                    "ItemLastModified" => $temp_p->ItemLastModified ? $temp_p->ItemLastModified : "0000-00-00 00:00:00:",
                    "ProductType"=>trim($temp_p->ProductType),
                    "erplyPending" => 1
                ]
            );
             
            
            
            if($temp_p->ProductType != ''){
                $checkType = LiveProductCategory::where("name", trim($temp_p->ProductType))->first();
                if(!$checkType){
                    LiveProductCategory::updateOrcreate(
                        [
                            "name" => trim($temp_p->ProductType), 
                        ],
                        [
                            "name" => trim($temp_p->ProductType),
                            "pendingProcess" => 1
                        ]
                    );
                }
            }

            TempProductGeneric::where("ERPLYSKU", $temp_p->ERPLYSKU)->update(["matrixPending" => 0]);
            // $temp_p->matrixPending = 0;
            // $temp_p->save();
            

        }

        return $this->successWithMessage("Temp Generic Product Matrix Sync Successfully.");
    }

    public function syncTempToCurrentsystemGenericVariation(){
        die;
        //get data from temp table
        $temp_product = TempProductGeneric::where('variationPending', '1')->limit(500)->get();
        foreach($temp_product as $temp_p){
            // $flag = $this->temp_product->where('WEBSKU', $temp_p->WEBSKU)->where('WebEnabled', '1')->first();
            // $isActive = 1;
            // if(!$flag){
            //     $isActive = 0;
            // }

            LiveProductGenericVariation::updateOrcreate(
                [
                    "ICSC"=>trim($temp_p->ICSC)
                ],
                [
                    "ERPLYSKU" =>    trim($temp_p->ERPLYSKU),
                    "ITEMID" =>  trim($temp_p->ITEMID),
                    "ItemName" =>   trim($temp_p->ItemName),
                    "ColourID" =>    trim($temp_p->ColourID),
                    "ColourName" =>  trim($temp_p->ColourName),
                    "SizeID" =>  trim($temp_p->SizeID),
                    "CONFIGID" =>    trim($temp_p->CONFIGID),
                    "ConfigName" =>  trim($temp_p->ConfigName),
                    "EANBarcode" =>  trim($temp_p->EANBarcode),
                    "ProductType" => trim($temp_p->ProductType),
                    "Supplier" =>    trim($temp_p->Supplier),
                    "CategoryName" =>    trim($temp_p->CategoryName),
                    "RetailSalesPrice" =>    trim($temp_p->RetailSalesPrice == '' ? '0.00' : $temp_p->RetailSalesPrice),
                    "RetailSalesPriceExclGST" => trim($temp_p->RetailSalesPriceExclGST == '' ? '0.00' : $temp_p->RetailSalesPriceExclGST),
                    "CostPrice" =>   trim($temp_p->CostPrice == '' ? '0.00' : $temp_p->CostPrice),
                    "ItemLastModified" => $temp_p->ItemLastModified,
                    "ProductType"=>trim($temp_p->ProductType),
                    "ICSC"=>trim($temp_p->ICSC),
                    "erplyPending" => 1
                ]
            );

            $color = LiveProductColor::where('name',trim($temp_p->ColourName) )->first();
            if(!$color){
                LiveProductColor::create([
                    "name" => trim($temp_p->ColourName),
                    "pendingProcess" => 1 
                ]);
            }

            $size = LiveProductSize::where('name', trim($temp_p->SizeID))->first();
            if(!$size){
                LiveProductSize::create([
                    "name" => trim($temp_p->SizeID),
                    "pendingProcess" => 1 
                ]);
            }
            

            //updang flag
            $temp_p->variationPending= 0;
            $temp_p->save();

        }

        return $this->successWithMessage("Temp Product Generic Variation Sync Successfully.");
    }
 

    //SYNCING AX TO MIDDLEWARE
    public function syncProductAxtoMiddlewareByLastModified(){
        //first get last modifeid date by descending
        $latest = AxSyncDatetime::where("id", 1)->first();
        // LiveProductGenericVariation::orderBy('ItemLastModified', 'desc')->first();
        // dd($latest);
        if(!$latest){
            return $this->successWithMessage("Product not found");
        }
  
        //now getting product from AX by last modification

        $datas = ProductGeneric::where("Item Last Modified",">=", $latest->product_generic)->orderBy("Item Last Modified", 'asc')->limit(2000)->get();
        // dd($datas);
        // info($datas);
        if($datas->isEmpty()){
            info("AX to SYNCCARE : All Generic Product Synced.");
            return $this->successWithMessage("AX to SYNCCARE : All Generic Product Synced.");
        }
        // dd($datas);
        foreach($datas as $value){

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
                        "erplyPending"=> 1,
                        "aPending"=> 1, 
            );
            //first update variation product
            LiveProductGenericVariation::updateOrcreate(
                [
                    "ICSC" => $value['ICSC']
                ],
                $details
            );

            
            //Now Update Matrix Product and variation pending 1 and matrix pending 1
            // $flag = LiveProductVariation::where('WEBSKU', $value['ERPLY SKU'])->where('WebEnabled', '1')->first();
            // $isActive = 1;
            // if(!$flag){
            //     $isActive = 0;
            // }
            $details["variationPending"] = 1;
            LiveProductGenericMatrix::updateOrcreate(
                [
                    "ERPLYSKU" => trim($value['ERPLY SKU']) 
                ],
                $details
            );

            
            //Now checking product colour
            $checkColor = LiveProductColor::where('name', trim($value['Colour Name']))->first();
            // if(!$checkColor){
            // if(trim($value['Colour Name'])){
            //     LiveProductColor::create(["name" => trim($value['Colour Name']), "colourID" => $value["ColourID"],  "pendingProcess" => $checkColor ? 0 : 1 ]);
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
                AxSyncDatetime::where("id", 1)->update(["product_generic" => $info["Item Last Modified"]]);
            }

 
        }

        info("AX to SYNCCARE : All Generic Product Synccing...");

        return $this->successWithDataAndMessage("AX to Middleware Generic Product Synced Successfully.",$datas);


    }

    public function syncToCurrentTable(){
         
        //now getting product from AX by last modification

        $datas = TempProductGeneric::where("pendingProcess", 1)->orderBy("ItemLastModified",'asc')->limit(200)->get();
        // dd($datas);
        // info($datas);
        if($datas->isEmpty()){
            info("AX to SYNCCARE : All Generic Product Synced.");
            return $this->successWithMessage("AX to SYNCCARE : All Generic Product Synced.");
        }
        // dd($datas);
        foreach($datas as $value){
            try{
                DB::transaction(function ()use($value) {
                    $icsc = trim($value->ICSC);
                    $vCompare = $value->ITEMID."_".$value->ItemName."_".$value->ColourID."_".$value->SizeID."_".$value->RetailSalesPrice."_".$value->ProductType."_";
                    $check = LiveProductGenericVariation::where("ICSC", $icsc)->first();
                    $vErplyPending = 1;

                    $aPending = 0;

                    if($check){
                        $checkCompare = LiveProductGenericVariation::where("ICSC", $icsc)->where("compareField", $vCompare)->first();
                        // dd($checkCompare);
                        if($checkCompare){
                            $vErplyPending = 0;//$check->erplyPending;
                        }
                         
                        $aPending = $check->aPending == 1 ? 1 : 0;
                    }
                    $details = array(
                                "ERPLYSKU" =>    $value->ERPLYSKU,
                                "ITEMID" =>  $value->ITEMID,
                                "ItemName" =>   $value->ItemName,
                                "ColourID" =>    $value->ColourID,
                                "ColourName" =>  $value->ColourName,
                                "SizeID" =>  $value->SizeID,
                                "CONFIGID" =>    $value->CONFIGID,
                                "ConfigName" =>  $value->ConfigName,
                                "EANBarcode" =>  $value->EANBarcode,
                                "Supplier" =>    $value->Supplier,
                                "CategoryName" =>    $value->CategoryName,
                                "RetailSalesPrice" =>    $value->RetailSalesPrice ? $value->RetailSalesPrice : "0.00" ,
                                "RetailSalesPriceExclGST" => $value->RetailSalesPriceExclGST ? $value->RetailSalesPriceExclGST : "0.00",
                                "CostPrice" =>   $value->CostPrice ? $value->CostPrice : "0.00",
                                "ItemLastModified" => $value->ItemLastModified,
                                "ProductType" => $value->ProductType,
                                "ICSC" => $icsc,
                                // "erplyPending"=> $vErplyPending,
                                "compareField"=> trim($vCompare),
                                "aPending"=> $aPending, 
                    );
                    
                    if($vErplyPending == 1){
                        $details["erplyPending"] = $vErplyPending;
                    }

                    // dd($details);
                    //first update variation product
                    LiveProductGenericVariation::updateOrcreate(
                        [
                            "ICSC" => $icsc
                        ],
                        $details
                    );
        

                    if($vErplyPending == 1){
                        $details["variationPending"] = 1;
                    }

                    $mCompare = $value->ItemName."_".$value->ProductType."_";
                    $mCheck = LiveProductGenericMatrix::where("ERPLYSKU", trim($value->ERPLYSKU))->first();
                    $mErplyPending = 1;
                    if($mCheck){
                        $mCompareCheck = LiveProductGenericMatrix::where("ERPLYSKU", trim($value->ERPLYSKU))->where("compareField", trim($mCompare))->first();
                        if($mCompareCheck){
                            $mErplyPending = 0;
                        }
                    }

                    if(@$details["erplyPending"]){
                        unset($details["erplyPending"]);
                    }
                    if(@$details["compareField"]){
                        unset($details["compareField"]);
                    }
                    
                    $details["compareField"] = trim($mCompare);
                    if($mErplyPending == 1){
                        $details["erplyPending"] = $mErplyPending;
                    } 

                    // dd($details);

                    LiveProductGenericMatrix::updateOrcreate(
                        [
                            "ERPLYSKU" => trim($value->ERPLYSKU) 
                        ],
                        $details
                    );

                    
                    //For Colour
                    $this->saveUpdateColour($value->ColourName, $value->ColourID);
        

                    //Now product size
                    $this->saveUpdateSize($value->SizeID);
                    

                    //For Category
                    $this->saveUpdateCategory($value->ProductType);

                    $value->pendingProcess = 0;
                    $value->save();

                });
            }catch(Exception $e){
                info("An error occurred while Product Temp to Current System :  ". $e->getMessage()." SKU ". $value->ERPLYSKU);
            }
 
        }

        info("Generic Product Temp to Current Table Synccing...");

        return $this->successWithMessage("AX to Middleware Generic Product Synced Successfully.");


    }
 

 

}