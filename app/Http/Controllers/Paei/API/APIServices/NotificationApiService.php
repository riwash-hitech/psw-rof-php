<?php
namespace App\Http\Controllers\Paei\API\APIServices;

use App\Classes\Except;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use App\Models\PAEI\EmailNotification;
use App\Models\PAEI\InventoryTransfer;
use App\Models\PAEI\MessageNotification;
use App\Models\PAEI\SalesDocument;
use App\Models\PAEI\SalesDocumentDetail;
use App\Models\PAEI\Warehouse;
use App\Models\PswClientLive\Local\LiveWarehouseLocation; 
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\WmsValidationTrait;

class NotificationApiService{

    use ResponseTrait, WmsValidationTrait;
    protected $warehouse;
    protected $api;

    public function __construct( EAPIService $api){
        $this->api = $api;
    }

    public function getSmsNotifications($req){

        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }

        if($req->warehouseID){
            $limit = $req->recordsOnPage ? $req->recordsOnPage : 20;
            $datas = MessageNotification::with("history")->where("clientCode", $currentWarehouse["clientCode"])->where("warehouseID", $currentWarehouse["warehouseID"])->orderBy("created_at", "desc")->paginate($limit);

            return $this->successWithData($datas);
        }
    }

    public function getEmailNotification($req){
        $currentWarehouse = $this->getCurrentWarehouse($req->warehouseID);
        if($currentWarehouse["status"] == 0){
            return $this->failWithMessage("Invalid Warehouse ID!");    
        }

        if($req->warehouseID){
            $limit = $req->recordsOnPage ? $req->recordsOnPage : 20;
            $datas = EmailNotification::with("history")->where("clientCode", $currentWarehouse["clientCode"])->where("warehouseID", $currentWarehouse["warehouseID"])->orderBy("created_at", "desc")->paginate($limit);

            return $this->successWithData($datas);
        }
    }

     

}
