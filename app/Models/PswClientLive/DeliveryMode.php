<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryMode extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'DMX_OUT_DLVMODE';
    protected $fillable = [];
    protected $guarded = [];

    public $timestamps = false;
}

 