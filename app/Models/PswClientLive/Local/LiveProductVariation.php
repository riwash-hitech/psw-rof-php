<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveProductVariation extends Model
{
    use HasFactory;
    use \TishoTM\Eloquent\Concerns\HasCiRelationships;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_product_variation_live';
    protected $fillable = [];
    protected $guarded = [];



    public function location()
    {
        return $this->hasOne(LiveWarehouseLocation::class, "LocationID", "DefaultStore")->select(["erplyAssortmentID"]);
    }

    public function stocks()
    {
        return $this->hasOne(LiveItemByLocation::class, "ICSC", "ICSC");
    } 

    public function stocks2()
    {
        return $this->belongsTo(LiveItemByLocation::class);
    }

    public function secondaryLocation()
    {
        return $this->hasOne(LiveWarehouseLocation::class, "LocationID", "SecondaryStore")->select(["erplyAssortmentID"]);
    }
}
