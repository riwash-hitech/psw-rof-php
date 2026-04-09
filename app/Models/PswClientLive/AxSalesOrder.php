<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AxSalesOrder extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'DMX_IN_SALESORDER_ERPLY';
    protected $fillable = [];
    protected $guarded = [];
    public $timestamps = false;
}

 