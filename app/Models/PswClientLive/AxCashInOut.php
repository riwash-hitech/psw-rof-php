<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AxCashInOut extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'DMX_IN_INCOMEEXPENSETRANS50316';
    protected $fillable = [];
    protected $guarded = [];

    public $timestamps = false;
}

 