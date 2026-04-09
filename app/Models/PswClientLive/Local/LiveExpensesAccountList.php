<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveExpensesAccountList extends Model
{
    use HasFactory;
    protected $connection = 'mysql2'; 
    protected $table = 'newsystem_expenses_lists';
    protected $fillable = [];
    protected $guarded = [];
}
