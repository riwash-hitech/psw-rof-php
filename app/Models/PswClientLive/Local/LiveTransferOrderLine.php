<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveTransferOrderLine extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_transfer_order_lines';
    protected $fillable = [];
    protected $guarded = [];


    public function fromWarehouse(){
        return $this->hasOne(LiveWarehouseLocation::class, "LocationID", "FromWarehouse");
    }

    public function toWarehouse(){
        return $this->hasOne(LiveWarehouseLocation::class, "LocationID", "ToWarehouse");
    }

    public function fromDetails(){
        return $this->hasOne(LiveWarehouseLocation::class, "LocationID", "FromWarehouse");
    }

    public function toDetails(){
        return $this->hasOne(LiveWarehouseLocation::class, "LocationID", "ToWarehouse");
    }

}
