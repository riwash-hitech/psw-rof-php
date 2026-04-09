<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempOnHandInventory extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'temp_on_hand_inventory';
    protected $fillable = [];
    protected $guarded = [];
}
