<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveItemByICSC extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_item_by_icsc';
    protected $fillable = [];
    protected $guarded = [];
}
