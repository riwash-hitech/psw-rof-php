<?php

namespace App\Http\Controllers\PswClientLive\Services;

use App\Models\PswClientLive\Customer;
use App\Models\PswClientLive\CustomerRelation;
use App\Models\PswClientLive\Local\LiveCustomer;
use App\Models\PswClientLive\Local\LiveCustomerRelation;
use App\Models\PswClientLive\Local\LiveProductMatrix;
use App\Models\PswClientLive\Local\LiveProductVariation;
use App\Models\PswClientLive\Local\TempCustomer;
use App\Models\PswClientLive\Local\TempCustomerRelation; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Traits\ResponseTrait;

class PswImageService{

    use ResponseTrait;
     

    public function checkImage(){
        info("Variation product Image checking cron called...");
        //get variation products
        $datas = LiveProductVariation::where("imagePending", 1)->limit(200)->get();
        // dd($datas);
        if($datas->isEmpty()){
            //again check product image 
            LiveProductVariation::where("imagePending", 0)->update(["imagePending" => 1]);
            info("All Product Image Updated.");
            return response("All Product Image Updated.");
        }
        foreach($datas as $data){

            //now checking image
            $url = "https://pswdata.retailcare.com.au/magic/uploads/images/".$data->ITEMID."_".$data->ColourID."_".$data->CONFIGID."_F.jpg";
            $url2 = "https://pswdata.retailcare.com.au/magic/uploads/images/".$data->ITEMID."_".$data->ColourID."_0_F.jpg";

             
            $data->imageUrl = $this->getUrl($url) == true ? $url : ($this->getUrl($url2) == true ? $url2 : "https://www.psw.com.au/media/catalog/product/placeholder/default/Image-Coming-Soon.png");
            $data->imagePending = 0;
            $data->save();

            // LiveProductMatrix::where("WEBSKU", $data->WEBSKU)->update([""])
             
        }

        return $this->successWithMessage("Product Image Updated Successfully.");
    }

    public function checkMatrixImage(){
        $datas = LiveProductMatrix::where("imagePending", 1)->limit(200)->get();

        if($datas->isEmpty()){
            LiveProductMatrix::where("imagePending", 0)->update(["imagePending" => 1]);
            info("All Matrix Product Image Updated.");
            return response("All Matrix Product Image Updated.");
        }


        foreach($datas as $data){

            //now checking image
            $url = "https://pswdata.retailcare.com.au/magic/uploads/images/".$data->ITEMID."_".$data->ColourID."_".$data->CONFIGID."_F.jpg";
            $url2 = "https://pswdata.retailcare.com.au/magic/uploads/images/".$data->ITEMID."_".$data->ColourID."_0_F.jpg";

             
            $data->imageUrl = $this->getUrl($url) == true ? $url : ($this->getUrl($url2) == true ? $url2 : "https://www.psw.com.au/media/catalog/product/placeholder/default/Image-Coming-Soon.png");
            $data->imagePending = 0;
            $data->save();

            // LiveProductMatrix::where("WEBSKU", $data->WEBSKU)->update([""])
             
        }
        return $this->successWithMessage("Matrix Product Image Updated Successfully.");
    }
    protected function getUrl($url){
        // $url = "https://pswdata.retailcare.com.au/magic/uploads/images/".$data->ITEMID."_".$data->ColourID."_".$data->CONFIGID."_F.jpg";

        $firstCheck = @get_headers($url);

        if (!$firstCheck || strpos($firstCheck[0], '404 Not Found')) {
            // return false;
             
            return false;
            
        }

        return true;
    }
     


}
