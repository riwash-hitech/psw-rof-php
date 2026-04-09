<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveProductGenericMatrix extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_product_generic_matrix_live';
    protected $fillable = [];
    protected $guarded = [];


    public function variations(){
        return $this->hasMany(LiveProductGenericVariation::class, "ERPLYSKU", "ERPLYSKU");
    }
}

 