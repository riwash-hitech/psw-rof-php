<?php
namespace App\Http\Controllers\PswClientLive\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use App\Models\PswClientLive\AxCustomer;
use App\Models\PswClientLive\AxSystemSequence;
use Illuminate\Support\Facades\DB;
use App\Traits\AxTrait;
use App\Classes\UserLogger;
use App\Http\Controllers\Paei\Services\GetCustomerService;
use App\Models\Client;
use App\Models\PAEI\Warehouse;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveWarehouseLocation;
use Illuminate\Support\Facades\Http;

class AxCustomerService{

    use AxTrait;
    protected $ax_customer;
    protected $mi_customer;
    protected $api; 
    protected $get_customer_service;

    public function __construct(AxCustomer $ax_customer, Customer $mi_customer, EAPIService $api, GetCustomerService $get_customer_service){
        $this->ax_customer = $ax_customer;
        $this->mi_customer = $mi_customer;
        $this->api = $api; 
        $this->get_customer_service = $get_customer_service;
    }

    public function syncMiddlewareToAx(){
        
        //first getting customer from middleware server

        $mi_customers = $this->mi_customer
                        ->join("newsystem_customer_groups", "newsystem_customer_groups.customerGroupID", "newsystem_customers.groupID")
                        ->where('newsystem_customer_groups.clientCode', $this->api->client->clientCode)
                        ->where('newsystem_customers.clientCode', $this->api->client->clientCode)
                        ->where("newsystem_customers.axPending", 1)
                        ->select(["newsystem_customers.*","newsystem_customer_groups.name as groupName"])
                        ->limit(5)
                        ->get();
        // dd($mi_customers);
        foreach($mi_customers as $cu){
            
            $details = array(
                "STOREID" => $cu->homeStoreID,
                "STATUS" => 1,
                "ACCOUNTNUM" => "ERPLY".$cu->customerID,
                "NAME" => $cu->fullName,
                // "CUSTGROUP" => $cu->groupName,
                "FIRSTNAME" => $cu->firstName,
                "LASTNAME" => $cu->lastName,
                "DELIVERYNAME" => $cu->fullName,
                "DELIVERYSTREET" => $cu->street ? $cu->street : "",
                "DELIVERYCITY" => $cu->city ? $cu->city : "",
                "DELIVERYSTATE" => $cu->state ? $cu->state : "",
                "DELIVERYCOUNTRYREGIONID" => "AU",
                "DELIVERYZIPCODE" => $cu->postalCode ? $cu->postalCode : "",
                "DELIVERYCELLULARPHONE" => $cu->mobile ? $cu->mobile : $cu->phone,
                "DELIVERYEMAIL" => $cu->email ? $cu->email : "",
                "TAXEMAIL" => $cu->email ? $cu->email : "",
                "TAXCELLULARPHONE" => $cu->mobile ? $cu->mobile : $cu->phone,
                "TAXZIPCODE" => $cu->postalCode ? $cu->postalCode : "",
                "TAXCOUNTRYREGIONID" => "AU",
                "TAXSTATE" => $cu->state ? $cu->state : "",
                "TAXCITY" => $cu->city ? $cu->city : "",
                "TAXSTREET" => $cu->street ? $cu->street : "",
                "TAXNAME" => $cu->fullName ? $cu->fullName : "",
                // "MIDDLENAME" => $cu->mobile,
                "TAXCITY" => $cu->city ? $cu->city : "",
                // "DBACTION" => 1,
                "MODIFIEDDATETIME" => $cu->lastModified,
                "MODIFIEDBY" => "ERPLY",
                "CREATEDDATETIME" => $cu->added,
                "CREATEDBY" => "ERPLY",
                "DATAAREAID" => "psw",
                // "RECVERSION" => 1, 
                "ENTITY" => "Academy",
                "TRANSACTIONID" => $cu->customerID,
                // "TERMINALID" => "Academy",
                "DELIVERYADDRESS" => $cu->address ? $cu->address : "",
                // "TAXADDRESS" => $cu->address ? $cu->address : "",
                
            );
            // dd($details);
            if($cu->axID > 1){

                // echo "Im exist";
                $details["DBACTION"] = 2;
                $checkCustomer = AxCustomer::where("RECID", $cu->axID)->first(); 
                if($checkCustomer){  
                    AxCustomer::where("RECID", $cu->axID)->update($details);
                }
                $cu->axPending = 0;
                $cu->save(); 

                UserLogger::setChronLogNew($checkCustomer ? json_encode($checkCustomer, true) : '', json_encode($details, true),  "Ax Customer Updated");        


                
            }else{ 
                //first getting RECID
                $recid = $this->getRecID(50274); 
                $details["DBACTION"] = 1;
                $details["RECID"] = $recid["NEXTVAL"];
                 
                AxCustomer::create($details);

                $verify = AxCustomer::where("RECID", $recid["NEXTVAL"])->first();
                if($verify){
                    //Now Updating NextVal
                    $rowCount = AxCustomer::count();
                    $nextVal = $rowCount + $recid["NEXTVAL"];
                    $updateNextval = $this->updateRecID(50274, $nextVal);
                    if($updateNextval == true){
                        info("SystemSequence Table Updated");
                        $cu->axID = $recid["NEXTVAL"];
                        $cu->save();

                        UserLogger::setChronLogNew('', json_encode($verify, true),  "Ax Customer Created");        

                    }else{
                        info("SystemSequence Table Update Failed");
                    }


                }
            }
            
    
            
        }

        return response()->json(["status" => "success"]);

    }

    public function syncSingleCustomerMiddleServerToAX($id, $data, $preFix, $isSecond = false){
        
        $customer = Customer::
                        // join("newsystem_customer_groups", "newsystem_customer_groups.customerGroupID", "newsystem_customers.groupID")
                        // ->where('newsystem_customer_groups.clientCode', $data->clientCode)// $this->api->client->clientCode)
                        where('newsystem_customers.clientCode', $data->clientCode) //$this->api->client->clientCode)
                        // ->where("newsystem_customers.axPending", 0)
                        ->where("newsystem_customers.customerID", $id)
                        // ->where("newsystem_customers.email","<>", '')
                        ->select(["newsystem_customers.*"])
                        // ->limit(5)
                        ->first();
      

        if($customer){ 
            //Customer Exist in Synccare 
            //now getting store ID
            // $storeID = $customer->homeStoreID;
            // if($storeID < 30){
            // $checkIsAcademy = Client::where("clientCode", $data->clientCode)->first()->ENTITY;
            $wdata = Warehouse::where("clientCode", $data->clientCode)->where("warehouseID", $customer->homeStoreID)->first();
            if($wdata){
                // $wh = LiveWarehouseLocation::where("erplyID", $customer->homeStoreID)->first();
                $wh = LiveWarehouseLocation::where("LocationID", @$wdata->code)->first();
            }
            
            // $storeID = @$wh->StoreID;
            // }
            $details = array(
                "STOREID" => @$wdata ? @$wh->StoreID : '',
                "STATUS" => 1,
                "ACCOUNTNUM" => $preFix == "PA" ? "ERPLY".$customer->customerID : "UG".$customer->customerID,
                "NAME" => $customer->fullName,
                "CUSTGROUP" => "Parent",
                "FIRSTNAME" => $customer->firstName,
                "LASTNAME" => $customer->lastName,
                "DELIVERYNAME" => $customer->fullName,
                "DELIVERYSTREET" => $customer->street ? $customer->street : "",
                "DELIVERYCITY" => $customer->city ? $customer->city : "",
                "DELIVERYSTATE" => $customer->state ? $customer->state : "",
                "DELIVERYCOUNTRYREGIONID" => "AU",
                "DELIVERYZIPCODE" => $customer->postalCode ? $customer->postalCode : "",
                "DELIVERYCELLULARPHONE" => $customer->mobile ? $customer->mobile : $customer->phone,
                "DELIVERYEMAIL" => $customer->email ? $customer->email : "",
                "TAXEMAIL" => $customer->email ? $customer->email : "",
                "TAXCELLULARPHONE" => $customer->mobile ? $customer->mobile : $customer->phone,
                "TAXZIPCODE" => $customer->postalCode ? $customer->postalCode : "",
                "TAXCOUNTRYREGIONID" => "AU",
                "TAXSTATE" => $customer->state ? $customer->state : "",
                "TAXCITY" => $customer->city ? $customer->city : "",
                "TAXSTREET" => $customer->street ? $customer->street : "",
                "TAXNAME" => $customer->fullName ? $customer->fullName : "",
                // "MIDDLENAME" => $customer->mobile,
                "TAXCITY" => $customer->city ? $customer->city : "",
                // "DBACTION" => 1,
                "MODIFIEDDATETIME" => $customer->lastModified == "0000-00-00 00:00:00" ? $customer->added : $customer->lastModified,
                "MODIFIEDBY" => "ERPLY",
                "CREATEDDATETIME" => $customer->added,
                "CREATEDBY" => "ERPLY",
                "DATAAREAID" => "psw",
                // "RECVERSION" => 1,  
                "ENTITY" => $wdata ? @$wh->ENTITY : "",
                "TRANSACTIONID" => $customer->customerID,
                "TERMINALID" => $customer->homeStoreID,
                "DELIVERYADDRESS" => $customer->address ? $customer->address : "",
                "TAXADDRESS" => $customer->address ? $customer->address : "",
                
            );

            //check user exist is AX
            $isExist = false;
            if($customer->axID > 1){
                $checkCustomer = AxCustomer::where("RECID", $customer->axID)->first(); 
                if($checkCustomer){
                    $details["DBACTION"] = 2;

                    AxCustomer::where("RECID", $customer->axID)->update($details);
                    
                    return true;
                    // die;
                    // if exist db action will be 2 means customer update
                    $isExist = true;
                    
                }
            }

            if($isExist == false){
                $details["DBACTION"] = 1;
            }
            // dd($details);

            $recid = $this->getRecID(50274);  
            $details["RECID"] = $recid["NEXTVAL"];

            //now checking is the customer created in erply or AX if ax no need to create
            $checkAxCustomer = LiveCustomerRelation::where($preFix == "PA" ? "customerID" : "pswCustomerID", $id)->first();
            if($checkAxCustomer){
                //if ax customer no need to create
                return true;
            } 

            // dd($details);
            AxCustomer::create($details);

            $verify = AxCustomer::where("RECID", $recid["NEXTVAL"])->first();
            if($verify){
                //Now Updating NextVal
                $rowCount = AxCustomer::count();
                $nextVal = $rowCount + $recid["NEXTVAL"];
                $updateNextval = $this->updateRecID(50274, $nextVal);
                if($updateNextval == true){
                    info("SystemSequence Table Updated");
                    $customer->axID = $recid["NEXTVAL"];
                    $customer->axPending = 0;
                    $customer->save();

                    UserLogger::setChronLogNew('', json_encode($verify, true),  "Syncare to Ax Customer Created");      
                    // dd($verify);
                    
                    return true;
                    
                    
                }else{
                    info("SystemSequence Table Update Failed");
                }

                return false;


            }

            return false;


        }else{

            if($isSecond == true){
                echo " Customer Not Found";
                return false;
            }

            //now getting customer from erply according to client code
            
            
            $param = array(
                // "orderBy" => "changed",
                "orderByDir" => "asc",
                "recordsOnPage" => "200",
                "getAddresses" => 1,
                "getContactPersons" => 1,
                "responseMode" => "detail",
                "customerID" => $id,
                "request" => "getCustomers",
                "clientCode" => $data->clientCode,
                // "changedSince" => 0,//$this->service->getLastUpdateDate(), 
                "sessionKey" => Client::where("clientCode", $data->clientCode)->first()->sessionKey //$this->api->client->sessionKey
            );

            $res = Http::asForm()->post('https://' . $data->clientCode . '.erply.com/api/', $param);
            $res = json_decode($res, true);
            // dd($res);
            // $res = $this->api->sendRequest("getCustomers", $param);
            if($res['status']['errorCode'] == 0 && !empty($res['records'])){
                $this->get_customer_service->saveUpdateCustomerOldApi($res['records'][0], true, $data->clientCode);
                return $this->syncSingleCustomerMiddleServerToAX($id,  $data, $preFix, true);
            }
             

        }

        return false;

    }


}