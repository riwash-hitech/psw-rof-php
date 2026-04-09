<?php

namespace App\Models\PswClientLive;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpensesAccount extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_psw_live';
    protected $table = 'ERPLY_ExpenseAccountsByLocation';

    protected $fillable = [];
    protected $guarded = [];
    // public $timestamps = false;
}

 