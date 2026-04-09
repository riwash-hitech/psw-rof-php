<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveCustomerRelation extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_customer_business_relations';
    protected $fillable = [];
    protected $guarded = [];
}
