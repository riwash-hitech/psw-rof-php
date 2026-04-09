<?php
namespace App\Http\Controllers\Paei\API\AxMagic\Services;

use App\Classes\Except; 
use App\Models\PswClientLive\Local\LiveItemByLocation;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Traits\ResponseTrait;

class ProductMagicService{

    protected $cashin;
    protected $api;
    use ResponseTrait;
  
    public function updateMatrixErplyEnabled($req){

        if($req->WEBSKU && isset($req->erplyEnabled) == true){
            $skus = explode(",", $req->WEBSKU);
            $erplyEnabled = $req->erplyEnabled;
            foreach($skus as $sku){
                LiveProductMatrix::where("WEBSKU", $sku)->update(["erplyEnabled" => $erplyEnabled, "erplyPending" => 1, "variationPending" => 1]);
                LiveProductVariation::where("WEBSKU", $sku)->update(["erplyEnabled" => $erplyEnabled, "erplyPending" => 1]);
            }
            return $this->successWithMessage("Product Erply Enabled Status Updated Successfully.");
        } 
        return $this->successWithMessage("Error while updating product!");
        // return response()->json(["status"=>200, "records" => $cashins]);
    }

    public function updateVariationErplyEnabled($req){

        if($req->type == "onlyVariation"){
            return $this->updateOnlyVariationErplyEnabled($req);
            die;
        }

        if($req->WEBSKU && isset($req->erplyEnabled) == true){
            $skus = explode(",", $req->WEBSKU);
            $erplyEnabled = $req->erplyEnabled;

            if(isset($req->colourID) == true){
                foreach($skus as $sku){
                    LiveProductVariation::where("WEBSKU", $sku)->where("ColourID", $req->colourID)->update(["erplyEnabled" => $erplyEnabled, "erplyPending" => 1]);
                    LiveProductMatrix::where("WEBSKU", $sku)->update(["variationPending" => 1]);
                }
            }

            if(isset($req->sizeID) == true){
                foreach($skus as $sku){
                    LiveProductVariation::where("WEBSKU", $sku)->where("SizeID", $req->sizeID)->update(["erplyEnabled" => $erplyEnabled, "erplyPending" => 1]);
                    LiveProductMatrix::where("WEBSKU", $sku)->update(["variationPending" => 1]);
                }
            } 

            return $this->successWithMessage("Product Erply Enabled Status Updated Successfully.");
        } 
        return $this->successWithMessage("Error while updating product!");
        // return response()->json(["status"=>200, "records" => $cashins]);
    }

    public function updateOnlyVariationErplyEnabled($req){ 

        if($req->ERPLYSKU && isset($req->erplyEnabled) == true){
            $skus = explode(",", $req->ERPLYSKU);
            $erplyEnabled = $req->erplyEnabled;

            foreach($skus as $sku){
                $vd = LiveProductVariation::where("ERPLYSKU", $sku)->first();
                LiveProductVariation::where("ERPLYSKU", $sku)->update(["erplyEnabled" => $erplyEnabled, "erplyPending" => 1]);
                if($vd){
                    LiveProductMatrix::where("WEBSKU", $vd->WEBSKU)->update(["variationPending" => 1]);
                }
            }
             
            return $this->successWithMessage("Only Variation Product Erply Enabled Status Updated Successfully.");
        } 
        return $this->successWithMessage("Error while updating product!");
        // return response()->json(["status"=>200, "records" => $cashins]);
    }


}
