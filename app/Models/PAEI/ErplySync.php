<?php

namespace App\Models\PAEI;

use App\Classes\UserLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErplySync extends Model
{
    use HasFactory;
    protected $table = 'erply_sync_datetimes';
    protected $fillable = [];
    protected $guarded = [];
 
    

    
}
