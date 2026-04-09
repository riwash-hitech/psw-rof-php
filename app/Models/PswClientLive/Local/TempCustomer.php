<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempCustomer extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'temp_customer_flag';
    protected $fillable = [];
    protected $guarded = [];
}
