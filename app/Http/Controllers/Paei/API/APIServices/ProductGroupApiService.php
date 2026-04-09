<?php
namespace App\Http\Controllers\Paei\API\APIServices;

use App\Classes\Except;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\ProductGroup;
use App\Models\PAEI\Warehouse;
use App\Models\PswClientLive\Local\LiveProductGroup;

class ProductGroupApiService{

    protected $group;
    protected $api;

    public function __construct(ProductGroup $w, EAPIService $api){
        $this->group = $w;
        $this->api = $api;
    }

    public function getByGroupsID($id){

        $warehouse = $this->group->where('clientCode', $this->api->client->clientCode)->where("productGroupID", $id)->get();
        return response()->json(["status"=>200, "records" => $warehouse]);

    }

    public function getGroups($req){

        return $this->getGroupsV2($req);
        die;
        
        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'name';
        }

         

        $pagination = $req->recordsOnPage == '' ? 20 : $req->recordsOnPage;
        $requestData = $req->except(Except::$except);

         
        // $groups = $this->group->paginate($pagination);
        $groups = $this->group->where(function ($q) use ($requestData, $req) {
            $q->where('clientCode', $this->api->client->clientCode);
            foreach ($requestData as $keys => $value) {
                if ($value != null && $value != 'undefined') { 
                    if((bool)$req->strictFilter == true){
                        $q->Where($keys, $value);
                    }else{
                        $q->Where($keys, 'LIKE', '%'.$value.'%');
                    }
                    // 'like', '%' . $value . '%'); 
                }
            }
        })->orderBy($req->sort_by, $req->direction)->paginate($pagination);
        
        return response()->json(["status"=>200,"success" => true, "records" => collect($groups)]);
    }

    public function getGroupsV2($req){

        if(isset($req->direction) == 0){
            $req->direction = 'asc';
        }
        if(isset($req->sort_by) == 0){
            $req->sort_by = 'SchoolName';
        }
        if(isset($req->strictFilter) == 0){
            $req->strictFilter = false;
        }

        $pagination = $req->recordsOnPage == '' ? 2000 : $req->recordsOnPage;
        // $categories = $this->category->paginate($pagination);
        $requestData = $req->except(Except::$except);
        
        $datas = LiveProductGroup::select('SchoolID','SchoolName')->where(function ($q) use ($requestData, $req) {
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

        return response()->json(["status"=>200, "success" => true, "records" => collect($datas)]);
    }


}
