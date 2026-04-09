<?php

namespace App\Models\PswClientLive\Local;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LiveProductMatrix extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'newsystem_product_matrix_live';
    protected $fillable = [];
    protected $guarded = [];


    public function variations(){
        return $this->hasMany(LiveProductVariation::class, 'WEBSKU', 'WEBSKU');
        // ->selectRaw('CONCAT(ItemName, ColourName, SizeID) as productName')
        // ->select(
        //     [
        //         "",
        //         "newsystem_product_variation_live.erplyID as productID",
        //         DB::raw("CONCAT(ItemName,' ', ColourName, ' ',SizeID) as productName")
        //     ]
        // );
    }

    public function school(){
        return $this->hasMany(LiveProductVariation::class, 'SchoolID', 'SchoolID');
        // ->selectRaw('CONCAT(ItemName, ColourName, SizeID) as productName')
        // ->select(["newsystem_product_variation_live.*","newsystem_product_variation_live.erplyID as productID",DB::raw("CONCAT(ItemName,' ', ColourName, ' ',SizeID) as productName")]);
    }


    function mapStatus($label)
    {
        return [
            'active' => 1,
            'no longer ordered' => 2,
            'not for sale' => 3,
            'archived' => 0,
        ][strtolower($label)] ?? 0;
    }



}

