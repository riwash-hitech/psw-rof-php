<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AxTransferOrder extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'DMX_IN_TRANSFERORDER_ERPLY';
    protected $fillable = [];
    protected $guarded = [];

    public $timestamps = false;
}

 