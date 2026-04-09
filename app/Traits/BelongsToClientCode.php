<?php

namespace App\Traits;

use App\Http\Controllers\Services\EAPIService;

trait BelongsToClientCode{

     
    public static function bootBelongsToClientCode()
    {
        
        static::addGlobalScope(function ($query) {
            // Your boot query logic goes here
            $api = app()->make(EAPIService::class);
            $query->where('clientCode', $api->client->clientCode);
        });
    }
  
    

}