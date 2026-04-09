<?php
namespace App\Http\Controllers\Paei\API\AxMagic\Services;

use App\Classes\Except;
use App\Models\PswClientLive\ItemByLocation;
use App\Models\PswClientLive\Local\LiveItemByLocation;
use App\Models\PswClientLive\Local\TempItemByLocation;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class ItemByLocationMagicService{

    protected $cashin;
    protected $api;
    use ResponseTrait;
  
    public function getItemByLocations($req){

        if(isset($req->direction) == 0){
            $req->direction = 'desc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'ModifiedDateTime';
        }
        if(isset($req->strictFilter) == 0){
            $req->strictFilter = true;
        }
        
        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $requestData = $req->except(Except::$except);

         
        // $groups = $this->group->paginate($pagination);
        $datas = LiveItemByLocation::where(function ($q) use ($requestData, $req) {
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
        
        return $this->successWithData($datas);
        // return response()->json(["status"=>200, "records" => $cashins]);
    }

    public function resyncItemByLocation($req){

        $isDebug = $req->debug ? $req->debug : 0;
        if($req->id){
            $data = LiveItemByLocation::where("id", $req->id)->first();

            if($data){
                $axData = ItemByLocation::where("ICSC", $data->ICSC)->where("Warehouse", $data->Warehouse)->get(); //DB::connection("sqlsrv_psw_live")->select("select top 1000 * from ERPLY_ItemsByLocation where ICSC = '.$data->ICSC.' and Warehouse = '.$data->Warehouse.' ORDER BY [Modified DateTime] ASC");
                if($isDebug == 1){
                    dd($axData, $data);
                }
                $this->saveUpdateItemByLocation($axData);
            }
        }

        return $this->successWithMessage("SOH Resynced Successfully.");
    }

    public function saveUpdateItemByLocation($datas){
        //save into temp table
        foreach($datas as $data){
            $payload = [
                "ICSC" => $data->ICSC,
                "Item" => $data->Item,
                "Configuration" => $data->Configuration,
                "Colour" => $data->Colour,
                "Size" => $data->Size,
                "Warehouse" => $data->Warehouse,
                "PhysicalInventory" => $data->{'Physical Inventory'},
                "PhysicalReserved" => $data->{'Physical Reserved'},
                "AvailablePhysical" => $data->{'Available Physical'},
                "OrderedInTotal" => $data->{'Ordered in Total'},
                "OnOrder" => $data->{'On Order'},
                "ModifiedDateTime" => $data->{'Modified DateTime'},
                "pendingProcess" => 1, 
            ];

            TempItemByLocation::create($payload);
        }
    }




}
