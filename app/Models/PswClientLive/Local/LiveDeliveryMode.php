<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveDeliveryMode extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_delivery_modes';
    protected $fillable = [];
    protected $guarded = [];
}

 