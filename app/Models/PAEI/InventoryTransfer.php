<?php

namespace App\Models\PAEI;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransfer extends Model
{
    use HasFactory;
    protected $table = 'newsystem_inventory_transfers';
    protected $fillable = [];
    protected $guarded = [];

    protected $casts = [
        'rows' => 'array',
        "attributes" => 'array',	
    ];


    // public function warehouse() {
    //     return $this->hasOne(Warehouse::class,'warehouseID', 'warehouseID');
    // }

    public function TransferLine(){
        return $this->hasMany(InventoryTransferLine::class , "transferID", "inventoryTransferID");
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
