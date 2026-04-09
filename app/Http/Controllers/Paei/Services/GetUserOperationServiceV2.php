<?php
namespace App\Http\Controllers\Paei\Services;

use App\Classes\UserLogger;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use App\Models\PAEI\CustomerGroup;
use App\Models\PAEI\MatrixProduct;
use App\Models\PAEI\UserOperationLog;
use App\Models\PAEI\VariationProduct;
use App\Traits\ResponseTrait;
class GetUserOperationServiceV2 {

    use ResponseTrait; 
    protected $customer; 
    protected $letsLog;
  

    public function __construct(UserLogger $logger){//UserOperationInterface $uoi){
 
        $this->letsLog = $logger;
       
 
    }
    
    public function handleDelete($erplyID, $table, $clientCode){

        
            if($table == "customers"){
                $old = Customer::where('clientCode', $clientCode)->where('customerID', $erplyID)->where('deleted', 0)->first();
                if($old){
                    $change = Customer::where('clientCode', $clientCode)->where('customerID', $erplyID)->update(['deleted' => 1]);
                    UserLogger::setChronLogNew($old ? json_encode($old, true) : '', json_encode($change, true), "Customer Deleted");    
                }
            }

            if($table == "customerGroups"){
                $old = CustomerGroup::where('clientCode', $clientCode)->where('customerGroupID', $erplyID)->where('deleted', 0)->first();
                if($old){
                    $change = CustomerGroup::where('clientCode', $clientCode)->where('customerGroupID', $erplyID)->update(['deleted' => 1]);
                    UserLogger::setChronLogNew($old ? json_encode($old, true) : '', json_encode($change, true), "Customer Group Deleted");    
                }
            }

            if($table == "products"){
                VariationProduct::deleteProduct($clientCode, $erplyID);
                MatrixProduct::deleteProduct($clientCode, $erplyID);
            }
            
        // return response()->json(["status" => 200, "message" => "Customer Operation Fetched Successfully."]);
    }
    
    

   


}
 