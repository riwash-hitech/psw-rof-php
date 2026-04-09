<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductGeneric extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'ERPLY_ItemMaster_Generic';

    protected $fillable = [];
    protected $guarded = [];
    // public $timestamps = false;
}

 