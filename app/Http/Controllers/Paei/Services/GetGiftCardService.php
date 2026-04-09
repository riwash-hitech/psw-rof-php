<?php
namespace App\Http\Controllers\Paei\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\GiftCard;
use App\Traits\ResponseTrait;

class GetGiftCardService{

    use ResponseTrait;
    protected $giftcard;
    protected $api;

    public function __construct(GiftCard $c, EAPIService $api){
        $this->giftcard = $c;
        $this->api = $api;
    }

    public function saveUpdate($customers){

        foreach($customers as $c){
            $this->saveUpdateGiftCard($c);
        }

        return $this->successWithMessage("Gift Card fetched Successfully.");
        // return response()->json(['status'=>200, 'message'=>"Gift Card fetched Successfully."]);
    }

    protected function saveUpdateGiftCard($product){

        $this->giftcard->updateOrCreate(
                [
                    "clientCode" => $this->api->client->clientCode,
                    "giftCardID"  =>  $product['id']
                ],
                [
                    "clientCode" => $this->api->client->clientCode,
                    "giftCardID" => $product['id'],
                    "typeID" => $product['customerType'],
                    "code" => $product['fullName'],
                    "value" => @$product['companyName'],
                    "balance"  => @$product['companyTypeID'] == '' ? 0 : $product['companyTypeID'],
                    "purchasingCustomerID"  => $product['firstName'],
                    "purchaseDateTime"  => @$product['lastName'],
                    "redeemingCustomerID"  => @$product['titleId'] == '' ? 0 : $product['titleId'],
                    "redemptionDateTime"  => @$product['gender'],
                    "expirationDate"  =>  @$product['customerGroupId'] == '' ? 0 : $product['customerGroupId'],
                    "purchaseInvoiceID"  =>  @$product['countryId'] == '' ? 0 : $product['countryId'],
                    "vatrateID"  =>  @$product['groupName'],
                    "information"  =>  @$product['payerId'] == '' ? 0 : $product['payerId'],
                    "added"  => $product['phone'],
                    "addedby"  => $product['mobile'],
                    "lastModified"  => @$product['mail'],
                    "changedby"  => @$product['fax'],
                     
                ]
            );
    }


    public function getLastUpdateDate(){
        // echo "im call";
         $latest = $this->giftcard->orderBy('added', 'desc')->first();
        if($latest){
            return strtotime($latest->added);
        }
        return 0;// strtotime($latest);
    }
}
