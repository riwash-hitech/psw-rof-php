<?php

namespace App\Http\Controllers\Paei\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\API\APIServices\CustomerAPIService;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseTrait;

class CustomerAPIController extends Controller
{
    //
    protected $service;
    protected $api;
    use ResponseTrait;

    public function __construct(CustomerAPIService $service, EAPIService $api){
        $this->service = $service;
        // $this->variation = $vp;
        $this->api = $api;
    }

    public function getCustomers(Request $req){

        if(isset($req->direction) == 0){
            $req['direction'] = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req['sort_by'] = 'fullName';
        }
        if(isset($req->deleted) == 0){
            $req['deleted'] = 0;
        }

        if($req->id){
            return $this->service->getByCustomerID($req->id);
        }

        return $this->service->getCustomer($req);

    }
    public function getAllCustomers(Request $req){
        return $this->service->getAllCustomers($req);
    }
    

    public function getCustomersByID(Request $req){
        if($req->id){
            return $this->service->getByCustomerID($req->id);
        }
        return response()->json(["status" => 400, "message" => "ID Field is Required!"]);

    }

    public function saveCustomer(Request $req){

        

        $customRules = array(
            // 'firstName' => 'required',
            // 'lastName' => 'required',
            // 'groupID' => 'required',
            // 'countryID' => 'required',
            // 'groupName' => 'required',
            // 'phone' => 'required',
            // 'mobile' => 'required',
            // 'email' => 'required',
            'customerType' => 'required',
            // 'code' => 'required',
            // 'euCustomerType' => 'required',
        );
        if($req->customerType == 'PERSON'){
            $customRules['firstName'] = 'required';
            // $customRules['lastName'] ='required';
        }else{
            if(isset($req['companyName2']) == 0 && $req['companyName2'] == ''){
                $customRules['companyName'] = 'required';
            }
            
        }

        if(isset($req->notify) == 1 && $req->notify == true){
            if($req->email == '' && $req->mobile == ''){
                // $customRules['mobile'] = 'required|numeric|min:10';
                $customRules['mobile'] = 'required|numeric|min:10';
            }
            
             

            if($req->email && $req->email != ''){
                $customRules['email'] = 'required|email';
            }
            
            
        }



       

        if($req->mobile && $req->mobile != ''){
            if(strlen((string)$req->mobile) != 10){
                return $this->failWithMessage("Phone number must be at least 10 digits!");
            }
            $customRules['mobile'] = 'required|numeric|min:10';
        }
        // return $this->failWithMessage("Validation Checking");
        // die;
        $validator = Validator::make($req->all(),  $customRules); 
        if ($validator->fails()) {

            return $this->validationError($validator->errors()->messages());
            
        }
        // return $this->failWithMessage("Phone number must be at least 10 digits!");


        //now checking customer email locally

        if($req->email && $req->email != ''){
             
            $isExist = Customer::where("email", $req->email)->where("clientCode", $this->api->client->clientCode)->first();
            if($isExist){
                return response()->json(["status" => 400, "success" => false, "message" => "Email already exists", "records" => collect($isExist)]);
            } 
            //now checking customer in erply
            $cuParam = array( 
                "searchEmail" => $req->email,
            );
            $isExist = $this->api->sendRequest("getCustomers", $cuParam);
            if($isExist["status"]["errorCode"] == 0 && !empty($isExist["records"])){
                return response()->json(["status" => 400, "success" => false, "message" => "Email already exists"]);
            }
 
        }

        if($req->mobile){
            
            $mob = trim($req->mobile);
            $mob = str_replace('-','', $mob);

            $isExist = Customer::where("mobile", $mob)->where("clientCode", $this->api->client->clientCode)->first();
            if($isExist){
                return response()->json(["status" => 400, "success" => false, "message" => "Mobile already exists",  "records" => collect($isExist)]);
            }

            //now checking customer in erply 
            $cuParam = array( 
                "searchMobile" => $mob,
            );
            $isExist = $this->api->sendRequest("getCustomers", $cuParam);
            if($isExist["status"]["errorCode"] == 0 && !empty($isExist["records"])){
                return response()->json(["status" => 400, "success" => false, "message" => "Email already exists"]);
            }
        }





        // return response()->json(["status" => 200, "success" => true, "message" => "Email not exists"]);
        // if ($validator->fails()) {
        //     return response()->json([
        //         'error' => $validator->messages()//->first()
        //     ], 400);
        // }

        return $this->service->saveCustomer($req);
        
    }


    public function deleteCustomer(Request $req){
        // echo "hello"
        $validator = Validator::make($req->all(), [ 
            'id' => 'required', 
        ]); 

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages()//->first()
            ], 400);
        }

        return $this->service->deleteCustomer($req);
        
    }


    //ROF Search Customer
    public function searchCustomers(Request $req){
        return $this->service->searchCustomers($req);
    }

}
