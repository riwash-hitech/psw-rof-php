<?php

namespace App\Http\Controllers\LivePushErply;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LivePushErply\Services\ProductGroupService;
use Illuminate\Http\Request;

class ProductGroupController extends Controller
{
    //
    protected $service;

    public function __construct(ProductGroupService $service){
        $this->service = $service;
    }

    public function syncProductGroup(Request $req){
        return $this->service->syncProductGroup($req);
    }

    public function updateParentGroup(){
        return $this->service->updateParentGroup();
    }

    public function deleteProductGroup(){
        return $this->service->deleteProductGroup();
    }

    public function checkSecondarySchool(Request $req){
        return $this->service->checkSecondarySchool($req);
    }


}
