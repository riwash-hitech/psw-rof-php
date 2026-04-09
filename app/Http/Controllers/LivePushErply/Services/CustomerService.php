<?php

namespace App\Http\Controllers\LivePushErply\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveProductGroup;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Http\Request;

class CustomerService{
    //
    protected $api;
    protected $customer;

    public function __construct(EAPIService $api, LiveCustomer $customer){
        $this->api = $api;
        $this->customer = $customer;
       
    }

    public function syncCustomerToErply(){

        $customers = LiveCustomerRelation::whereIn("CUSTGROUP", ['Wholesale','Retail','Internal'])
                        // ->join("newsystem_customer_business_relations", "newsystem_customer_business_relations.PSW_SMMCUSTACCOUNT", "newsystem_customer_flag.ACCOUNTNUM")
                        ->where("pendingProcess", 1)
                        ->limit(20)
                        ->get();
        $BundleArray = array();
        foreach($customers as $customer){

            // dd($customer->toArray());
            //now getting home store id
            $storeID = 0;
            if($customer->SAB_RBOSTOREPRIMARY){
                $warehouse = LiveWarehouseLocation::where("erplyID",'>', 0)->where("LocationID", $customer->SAB_RBOSTOREPRIMARY)->first();
                if($warehouse){
                    $storeID = $warehouse->erplyID;
                }
            }
            $custGroupID = 0;
            if($customer->CUSTGROUP == 'Wholesale'){
                $custGroupID = 15;
            }
            if($customer->CUSTGROUP == 'Retail'){
                $custGroupID = 16;
            }
            if($customer->CUSTGROUP == 'Internal'){
                $custGroupID = 17;
            }
            $reqArray = array(
                "requestName" => "saveCustomer",
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "fullName" => $customer->NAME,
                // "lastName" => '',//$customer->ciLastName,
                "groupID" => $custGroupID,
                "email" => $customer->EMAIL,
                "phone" => $customer->PHONE,
                // "mobile" => $customer->ciMobile,
                // "website" => $customer->ciWebsite,
                "countryID" => 25, //25 Australia 
                "trimInputData" => 1,
                "credit" => (integer)$customer->CREDITMAX,
                "attributeName1" => "PSW_SMMCUSTACCOUNT",
                "attributeType1" => 'text',
                "attributeValue1" => $customer->PSW_SMMCUSTACCOUNT,
                "attributeName2" => "SAB_RBOSTOREPRIMARY",
                "attributeType2" => 'text',
                "attributeValue2" => $customer->SAB_RBOSTOREPRIMARY,
                "attributeName3" => "STATUS",
                "attributeType3" => 'text',
                "attributeValue3" => $customer->STATUS,
                "attributeName4" => "ADDRESS",
                "attributeType4" => 'text',
                "attributeValue4" => $customer->ADDRESS,
                "attributeName5" => "STREET",
                "attributeType5" => 'text',
                "attributeValue5" => $customer->STREET,
                "attributeName6" => "CITY",
                "attributeType6" => 'text',
                "attributeValue6" => $customer->CITY,
                "attributeName7" => "ZIPCODE",
                "attributeType7" => 'text',
                "attributeValue7" => $customer->ZIPCODE,
                "attributeName8" => "STATE",
                "attributeType8" => 'text',
                "attributeValue8" => $customer->STATE, 
            );

            if($customer->STATUS == "Inactive"){
                $reqArray["notes"] = "This customer is currently disabled, Do not sell on Credit";
            }else{
                $reqArray["notes"] = "";
            }


            if($storeID > 0){
                $reqArray["homeStoreID"] = $storeID;
            }

            $customerID = $this->checkCustomer($customer->PSW_SMMCUSTACCOUNT);
            if($customerID != ''){
                $reqArray['customerID'] = $customerID;
            }

            //NOW ADDING ATTRIBUTES
            // $index = 1;
            // $tt = $customer->toArray();
            // foreach($tt as $key => $c){
            //     if($key == "ACCOUNTNUM"){
            //         echo $key;
            //         die;
            //     }else{
            //         echo " no";
            //         die;
            //     }
            //     // echo $key;
            //     // die;
            //     if($key == 'ACCOUNTNUM' || $key == 'SAB_RBOSTOREPRIMARY' || $key == 'STATUS'){
            //         $param["attributeName".$index] = $key;
            //         $param["attributeType".$index] = 'text';
            //         $param["attributeValue".$index] = $c;
            //         $index++;
            //     }
            // }
            

            array_push($BundleArray,$reqArray );
                
        }

        // dd($BundleArray);
        if(count($BundleArray) < 1){
            info("Synccare to Erply : All Customer Syncced");
            return response(" Synccare to Erply : All Customer Synced");
        }

        $BundleArray = json_encode($BundleArray, true);

        $param = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );
        $res = $this->api->sendRequest($BundleArray, $param, 1);

        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($customers as $key => $c){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    $c->customerID = $res['requests'][$key]['records'][0]['customerID'];
                    $c->pendingProcess = 0;
                    $c->save();
                }
            }
            info("Customer Created or Updated to Erply");
        }

        return response()->json($res);

    }


    protected function checkCustomer($an){
        $param = array(
            "searchAttributeName" => "PSW_SMMCUSTACCOUNT",
            "searchAttributeValue" => $an,
            "sessionKey" => $this->api->client->sessionKey,
        );

        $res = $this->api->sendRequest("getCustomers", $param,0,0,0);
        // dd($res);
        if($res['status']['errorCode'] == 0 && !empty($res['records'])){
            info("Customer exist ID".$res['records'][0]['customerID']);
            return $res['records'][0]['customerID'];
        }

        return '';

    }

    public function syncCustomerAddress(){
        $customers = LiveCustomerRelation::
                        // ->join("newsystem_customer_business_relations", "newsystem_customer_business_relations.PSW_SMMCUSTACCOUNT", "newsystem_customer_flag.ACCOUNTNUM")
                        where("pendingProcess", 0)
                        ->where("ADDRESS",'<>', '')
                        ->where("STREET",'<>', '')
                        ->where("CITY",'<>', '')
                        ->where("ZIPCODE",'<>', '')
                        ->where("STATE",'<>', '')
                        ->where("addressPending", 1)
                        ->limit(10)
                        ->get();
        if($customers->isEmpty()){
            info("Synccare to Erply : All Customer Address Synced to Erply.");
            return response("All Customer Address Synced to Erply.");
        }
        $bulk = array();
        foreach($customers as $c){
            
            $param = array(
                "requestName" => "saveAddress",
                "sessionKey" => $this->api->client->sessionKey,
                "clientCode" => $this->api->client->clientCode,
                "ownerID" => $c->customerID,
                "typeID" => 1,
                "street" => $c->STREET ? $c->STREET : '', 
                "city" => $c->CITY ? $c->CITY : '',
                "postalCode" => $c->ZIPCODE ? $c->ZIPCODE : '',
                "state" => $c->STATE ? $c->STATE : '',
                "country" => 'Australia', 
                "attributeName1" => "Address",
                "attributeType1" => "text",
                "attributeValue1" => $c->ADDRESS 
                
            );

            $checkErply = $this->getAddress($c->customerID);
            if($checkErply > 0){
                $param["addressID"] = $checkErply;
            }
            $bulk[] = $param;
            
        }
        // dd($bulk);
        if(count($bulk) < 1){
            info("All Customer Address Synced to Erply.");
            return response("All Customer Address Synced to Erply.");
        }

        // dd($bulk);
        $bulk = json_encode($bulk, true);
        
        $param = array(
            "lang" => 'eng',
            "responseType" => "json", 
            "sessionKey" => $this->api->client->sessionKey,
        );
        
        $res = $this->api->sendRequest($bulk, $param, 1);

        if($res['status']['errorCode'] == 0 && !empty($res['requests'])){
            foreach($customers as $key => $c){
                if($res['requests'][$key]['status']['errorCode'] == 0){
                    $c->addressID = $res['requests'][$key]['records'][0]['addressID'];
                    $c->addressPending = 0;
                    $c->save();
                }
            }
            info("Customer Address Created or Updated to Erply");
        }

        return response()->json($res);
    }

    public function getAddress($cid){
        $param = array(
            "sessionKey" => $this->api->client->sessionKey,
            "typeID" => 1,
            "ownerID" => $cid,
            // "recordsOnPage" => 100
        );

        $res = $this->api->sendRequest("getAddresses", $param);
        // dd($res);
        $isDeliveryExist = false;
        $aID = 0;
        if($res["status"]["errorCode"] == 0 && !empty($res["records"])){

            foreach($res["records"] as $add){
                $aID = $add["addressID"];
            }
        }

        return $aID;
    }
 
 
}
