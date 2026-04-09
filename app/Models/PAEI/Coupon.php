<?php

namespace App\Models\PAEI;

use App\Classes\UserLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $table = 'newsystem_coupons';
    protected $fillable = [];
    protected $guarded = [];

    protected function getCreatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }
    protected function getUpdatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();
         
    }

    static public function deleteRecords($clientCode, $id){
        $old = self::where('clientCode', $clientCode)->where('couponID', $id)->where('deleted', 0)->first();
        if($old){
            $change = self::where('clientCode', $clientCode)->where('couponID', $id)->update(['deleted' => 1]);
            UserLogger::setChronLogNew($old ? json_encode($old, true) : '', json_encode($change, true), "Coupon Deleted");    
        }
    } 
    

    
}
