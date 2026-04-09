<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AxSyncDatetime extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'ax_sync_datetime';
    protected $fillable = [];
    protected $guarded = [];

   
}

 