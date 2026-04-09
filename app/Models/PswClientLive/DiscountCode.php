<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'ERPLY_DiscountCodes';
    protected $fillable = [];
    protected $guarded = [];

    public $timestamps = false;
}

 