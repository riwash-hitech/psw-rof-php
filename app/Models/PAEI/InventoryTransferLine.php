<?php

namespace App\Models\PAEI;

use App\Models\PswClientLive\Local\LiveProductVariation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransferLine extends Model
{
    use HasFactory;
    protected $table = 'newsystem_inventory_transfers_lines';
    protected $fillable = [];
    protected $guarded = [];

    public function ProductDetails(){
        return $this->hasOne(LiveProductVariation::class, "erplyID", "productID")->select("ItemName", "ColourName", "SizeID", "erplyID");
    }
    // public function warehouse() {
    //     return $this->hasOne(Warehouse::class,'warehouseID', 'warehouseID');
    // }

    protected function getCreatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }
    protected function getUpdatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }

}
