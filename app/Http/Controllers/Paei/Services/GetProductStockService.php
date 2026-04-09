<?php

namespace App\Http\Controllers\Paei\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Cashin;
use App\Models\PAEI\ProductStock;

class GetProductStockService
{

    protected $stock;
    protected $api;

    public function __construct(ProductStock $c, EAPIService $api)
    {
        $this->stock = $c;
        $this->api = $api;
    }

    public function saveUpdate($cashins, $wid)
    {

        foreach ($cashins as $c) {
            $this->saveUpdateCashin($c, $wid);
        }
    }

    protected function saveUpdateCashin($product, $wid)
    {

        $details = array(
            "clientCode" => $this->api->client->clientCode,
            "productID" => $product["productID"],
            "warehouseID" => $wid,
            "amountInStock" => $product["amountInStock"],
            "amountReserved" => $product["amountReserved"],
            "suggestedPurchasePrice" => $product["suggestedPurchasePrice"],
            "firstPurchaseDate" => $product["firstPurchaseDate"],
            "lastSoldDate" => $product["lastSoldDate"],
            
        );

        ProductStock::updateOrcreate(
            [
                "clientCode" => $this->api->client->clientCode,
                "warehouseID" => $wid,
                "productID" => $product["productID"]
            ],

            $details
        );
    }


    public function getLastUpdateDate()
    {
        // echo "im call";
        $latest = $this->stock->orderBy('lastSoldDate', 'desc')->first();
        if ($latest) {
            return strtotime($latest->lastSoldDate);
        }
        return 0; // strtotime($latest);
    }
}
