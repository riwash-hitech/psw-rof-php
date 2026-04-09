<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveOnHandInventory extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_on_hand_inventory';
    protected $fillable = [];
    protected $guarded = [];

    public function warehouse(){
        return $this->hasOne(LiveWarehouseLocation::class, "LocationID", "Warehouse");
    }
}
