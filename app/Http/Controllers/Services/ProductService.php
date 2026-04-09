<?php
namespace App\Http\Controllers\Services;

use App\Http\Controllers\EAPI;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;
 

class ProductService
{
    protected $api;
    protected $product;
    protected $productvariant;
    protected $client;
    public function __construct( EAPIService $api, Product $product, ProductVariant $pv)
    {
        $this->api = $api;
        $this->product = $product;
        $this->productvariant = $pv;
    }
    // protected function setModel(){
    //     session_start(); 
    //     $this->api = new EAPIService();  
    //     // $this->client = Client::findOrfail(1);
    //     // $this->api->clientCode = $this->client->clientCode;
    //     // $this->api->username = $this->client->username;
    //     // $this->api->password = $this->client->password;
    //     // $this->api->url = "https://".$this->api->clientCode.".erply.com/api/";

    //     $this->product = new Product();
    //     $this->productvariant = new ProductVariant();
    // }

    public function getData($param){
        $result = $this->api->sendRequest("getProducts", $param);
        $products = json_decode($result, true);
        if($products['status']['errorCode'] != 0){
            return response()->json(['stauts' => 'Error '. $products['status']['errorCode']]);
        }
        return $products['records'];
    }



    public function handleProduct($param){
        
        $products = $this->getData($param, "getProducts");
        // $products = json_decode($products, true);
        // if($products['status']['errorCode'] != 0){
        //     return response()->json(['stauts' => 'Error '. $products['status']['errorCode']]);
        // }
        //  print_r($products);
        //  die;
        foreach($products as $product){
            if(!array_key_exists("parentProductID", $product)){
                // dd($product);
                // echo $product['productID'];
                $this->ProductUpdateCreate($product);
                // echo "Product Create Or Updated";
            }else{
                if($product["parentProductID"] == 0){
                    // dd($product);
                    $this->product->updateOrCreate($product);
                    // echo "Product Create Or Updated";
                }else{ 
                    // echo "Product Variants Create Or Updated";
                    // echo $product['productID'];
                    // print_r($product);
                    $this->ProductVariantUpdateCreate($product);
                } 
            }
            
        }

        echo "Product Create or Updated Successfully.";
        die;


    }

    public function handleCronJob($param){
         Log::info("cron Job Called");
        $data = $this->getData($param);
        $bulkP = array();
        $bulkPV = array();
        foreach($data as $d){
            $p = $this->product->findOrfail($d['productID']);
            if(!array_key_exists("parentProductID", $data) || $data["parentProductID"] == 0){
                // dd($product);
                if(!$p){
                    array_push( $bulkP,$d);
                }else{
                    $this->ProductUpdateCreate($d);
                }

            }else{
                
                if(!$p){
                    array_push( $bulkPV, $d);
                }else{
                    $this->ProductVariantUpdateCreate($d);
                } 
                 
            }
        }

        //for bulk entry
        if($bulkP){
            $this->product->insert($bulkP);
        }
        if($bulkPV){
            $this->productvariant->insert($bulkPV);
        }
        
    }
     

    protected function ProductVariantUpdateCreate($product){
        // dd($product);
        //counting variation id
        $rows = 0;
        if(array_key_exists('variationDescription', $product)){
            $rows = count($product['variationDescription']);
        }
        
        $this->productvariant->updateOrCreate(
            [
                "erplyVariationProductID"  => $product['productID']
            ],
            [
                "syncCompanyID"   => 16,
                "erplyClientCode"   => 466822,
                "erplyParentProductID"   => $product["parentProductID"],
                "erplyVariationProductID"  => $product['productID'],
                "productCode"   => $product['code'],
                "erplyVariation1ID" => $rows >= 1 ? $product['variationDescription'][0]['variationID'] : 0,
                "erplyVariation2ID" =>  $rows >= 2 ? $product['variationDescription'][1]['variationID'] : 0,
                "erplyVariation3ID" =>  $rows >= 3 ? $product['variationDescription'][2]['variationID'] : 0,
                "erplyVariation4ID" =>  $rows >= 4 ? $product['variationDescription'][3]['variationID'] : 0,
                "erplyVariation5ID" =>  $rows >= 5 ? $product['variationDescription'][4]['variationID'] : 0,
                "erplyVariationProductName"  => $product['name'],
                "productCode2"  => $product['code2'],
                "productCode3"  => $product['code3'] != '' ? $product['code3'] : '',
                "productSupplierCode"  => $product['supplierCode'] != '' ? $product['supplierCode'] : '',
                "productCode5"  => 0,
                "productCode6"  => 0,
                "productCode7"  => 0,
                "productCode8"  => 0,
                "erplyVariationProductType"  => $product['type'],
                "erplyVariationActive"  => $product['status'] == "Active" ? 1 : 0,
                "erplyVariationArchive"  => $product['status'] == "Archive" ? 1 : 0,
                "erplyVariationDescription"  => $product['descriptionENG'],
                "erplyVariationLongDescription"  => $product['longdescENG'], 
                "erplyVariationPrice"  => $product['price'],
                "erplyVariationPriceWithTax"  => $product['priceWithVat'], 
                "erplyVariationSalePrice"  => 0, 
                "erplyVariationSalePriceWithTax"  => 0, 
                "cost"  => 0,
                "FIFOCost"  => 0,
                "purchasePrice"  => 0,
                "lastModifiedDateTime"  => date('Y-m-d H:i:s', $product['lastModified']),
                "pendingProcess"  => 1, 
            ]
        );
    }

    protected function ProductUpdateCreate($product){
        // dd($product);
        
        $this->product->updateOrCreate(
            [
                "erplyProductID"  => $product['productID']
            ],
            [
                "syncCompanyID"   => 16,
                "erplyClientCode" => 466822,
                "erplyProductID" => $product['productID'],
                "productCode" => $product['code'],
                "productType" => $product['type'],
                "productActive" => $product['active'], 
                "productStatus"  => $product['status'],
                "productName"  => $product['name'],
                "productCode2"  => $product['code2'] != '' ? $product['code2'] : '',
                "productCode3"  => $product['code3'] != '' ? $product['code3'] : '',
                "productSupplierCode"  => $product['supplierCode'] != '' ? $product['supplierCode'] : '',
                "productCode5"  => '',
                "productCode6"  => '',
                "productCode7"  => '',
                "productCode8"  => '',
                "productGroupID"  => $product['groupID'],
                "productPrice"  => $product['price'],
                "productPriceWithTax"  => $product['priceWithVat'],
                "salesPrice"  => 0, 
                "salesPricewithTax"  => 0,
                "cost"  => $product['cost'], 
                "FIFOCost"  => 0, 
                "purchasePrice"  => 0, 
                "productWebActive"  => $product['status'] == "ACTIVE" ? 1 : 0,
                "productCategoryID"  => $product['categoryID'],
                "productSupplierID"  => $product['supplierID'],
                "productUnitID"  => $product['unitID'],
                "productTaxRateID"  => 0, 
                "productManufacturer"  => 0, 
                "productBrandID"  => $product['brandID'], 
                "productWidth"  => $product['width'], 
                "productHeight"  => $product['height'], 
                "productLength"  => $product['length'], 
                "productNonStock"  => $product['nonStockProduct'], 
                "productNetWeight"  => $product['netWeight'] == '' ? 0 : $product['netWeight'], 
                "productGrossWeight"  => $product['grossWeight'] == '' ? 0 : $product['grossWeight'], 
                "productShortDescription"  => $product['descriptionENG'], 
                "productLongDescription"  => '', 
                "lastModifiedDateTime"  => date('Y-m-d H:i:s', $product['lastModified']), 
                "lastProcessedTime"  => date('Y-m-d H:i:s'),//today date time 
                "pendingProcess"  => 1, 
                "imagePending"  => 1, 
                "imageProcessedTime"  => date('Y-m-d H:i:s'),// today date  
                "eslPending"  => 0, 
                "eslActive"  => 0, 
                "sohPending"  => 0, 
                "eslLastUpdated"  => '0000-00-00 00:00:00', 
                "sohProcessedTime"  => '0000-00-00 00:00:00', 
                "attrSize"  => '', 
                 

            ]
        );

    }

    public function getLastUpdateDate(){
        $latest = $this->product->orderBy('lastModifiedDateTime', 'desc')->first()->lastModifiedDateTime;
        return $latest;
        
    }

}