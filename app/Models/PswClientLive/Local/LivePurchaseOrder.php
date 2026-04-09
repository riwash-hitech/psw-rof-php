<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LivePurchaseOrder extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_purchase_orders';
    protected $fillable = [];
    protected $guarded = [];
}

 