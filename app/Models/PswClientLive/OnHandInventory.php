<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder; // <-- add this
use Illuminate\Support\Facades\DB;

class OnHandInventory extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'ERPLY_OnHandInventory';

    protected static function booted()
    {
        static::addGlobalScope('nolock', function (Builder $builder) {
            $builder->from(DB::raw('[ERPLY_OnHandInventory] WITH (NOLOCK)'));
        });
    }
}

