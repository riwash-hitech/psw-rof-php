<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveDiscountCode extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_discount_codes';
    protected $fillable = [];
    protected $guarded = [];
}

 