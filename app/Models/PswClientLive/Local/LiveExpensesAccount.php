<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveExpensesAccount extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_expenses_accounts';
    protected $fillable = [];
    protected $guarded = [];
}

 