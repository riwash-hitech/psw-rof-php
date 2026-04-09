<?php

namespace App\Traits;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PswClientLive\AxSystemSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

trait ClientCodeTrait{
    
    // protected $api;

    // public function __construct(EAPIService $api){
    //     $this->api = $api;
    // }

    protected static function bootClientCodeTrait()
    { 
        static::addGlobalScope('clientCode', function (Builder $builder)   {
            $api = resolve(EAPIService::class);

            // $api = $this->app->make(EAPIService::class);
            if($api->client->clientCode){ 
                    $builder->where('clientCode', $api->client->clientCode); 
            } 
        }); 
    }
    

}