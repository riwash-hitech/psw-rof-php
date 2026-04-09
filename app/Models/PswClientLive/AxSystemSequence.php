<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AxSystemSequence extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'SYSTEMSEQUENCES';

    public $timestamps = false;
}

 