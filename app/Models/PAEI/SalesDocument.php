<?php

namespace App\Models\PAEI;

use App\Classes\UserLogger;
use App\Traits\BelongsToClientCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ClientCodeTrait;

class SalesDocument extends Model
{
    use HasFactory;
    // , BelongsToClientCode; 
    protected $table = 'newsystem_sales_documents';
    protected $fillable = [];
    protected $guarded = [];
    protected $hidden = ['clientCode','rows'];
    protected $casts = [
        "attributes" => 'array', 
    ];

    public function SalesDetails(){
        return $this->hasMany(SalesDocumentDetail::class, 'salesDocumentID', 'salesDocumentID')
        ->where("isDeleted", 0)
        ->select('id','salesDocumentID','productID','code','amount','price','discount','finalNetPrice','finalPriceWithVAT','rowNetTotal','rowTotal', 'itemName');
    }

    public function SalesDetailsV2(){
        return $this->hasMany(SalesDocumentDetail::class, 'salesDocumentID', 'salesDocumentID')->select(["productID","itemName","amount","price","discount"]);
    }

    public function Customer(){
        return $this->hasOne(Customer::class, 'customerID', 'clientID')->select('customerID','fullName','email','mobile');
        // ->where("customerID", '>', 0)
        // ->select('fullName','email','mobile');
    }


    // protected static function booted(){
    //     parent::boot();
    //     static::bootClientCode();
    // }



    public function payments(){
        return $this->hasMany(Payment::class, "documentID", "salesDocumentID");
        // ->whereColumn("newsystem_payments.clientCode","=","newsystem_sales_documents.clientCode");//->where("clientCode", "clientCode");
    }

    public function paymentsWithClientCode($query){
        return $query->whereHas('payment', function ($query) {
            $query->whereColumn('newsystem_payments.clientCode', '=', 'newsystem_sales_documents.clientCode');
        });
    }


    protected function getCreatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }
    protected function getUpdatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }

    static public function deleteRecords($clientCode, $id){
        $old = self::where('clientCode', $clientCode)->where('salesDocumentID', $id)->where('deleted', 0)->first();
        if($old){
            $change = self::where('clientCode', $clientCode)->where('salesDocumentID', $id)->update(['deleted' => 1]);
            UserLogger::setChronLogNew($old ? json_encode($old, true) : '', json_encode($change, true), "Sales Document Deleted");    
        }
    } 
}
