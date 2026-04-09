<?php

namespace App\Models\PAEI;

use App\Models\PswClientLive\Local\LiveProductVariation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ClientCodeTrait;

class SalesDocumentDetail extends Model
{
    use HasFactory;//, ClientCodeTrait; 
    protected $table = 'newsystem_sales_document_details';
    protected $fillable = [];
    protected $guarded = [];
    // protected $hidden = ['clientCode'];


    public function axRelation(){
        return $this->hasOne(LiveProductVariation::class, 'erplyID', 'productID')
        ->select('erplyID','ItemName','ColourID','ColourName','SizeID',"SchoolID","imageUrl","EANBarcode","RetailSalesPrice", "WEBSKU", "ERPLYSKU");
    }

    protected function getCreatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }
    protected function getUpdatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }
}
