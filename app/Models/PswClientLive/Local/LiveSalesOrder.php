<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveSalesOrder extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'live_sales_orders';
    protected $fillable = [];
    protected $guarded = [];


    public function location(){
        return $this->hasOne(LiveWarehouseLocation::class, 'LocationID', 'INVENTLOCATIONID');
    }
}
