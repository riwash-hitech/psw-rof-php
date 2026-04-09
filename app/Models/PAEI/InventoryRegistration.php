<?php

namespace App\Models\PAEI;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class InventoryRegistration extends Model
{
    
    use HasFactory;
    
    protected $table = 'newsystem_inventory_registrations';
    protected $fillable = [];
    protected $guarded = [];
    protected $hidden = ['rows'];

    

    public function warehouse() {
        return $this->hasOne(Warehouse::class,'warehouseID', 'warehouseID')->select('warehouseID','name', 'code');
    }

    public function lines() {
        return $this->hasMany(InventoryRegistrationLine::class,'inventoryRegistrationID', 'inventoryRegistrationID');
    }


    

    protected function getCreatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }
    protected function getUpdatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }

}
