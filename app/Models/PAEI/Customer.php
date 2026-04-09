<?php

namespace App\Models\PAEI;

use App\Classes\UserLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $table = 'newsystem_customers';
    protected $fillable = [];
    protected $guarded = [];

    protected $hidden = ["clientCode"];

    protected function getCreatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();

    }
    protected function getUpdatedAtAttribute($val)
    {
        return Carbon::parse($val)->setTimezone('Australia/Sydney')->toDateTimeString();

    }

    public function scopeFilter($query, $params){
        if (isset($params['customerID']) && trim($params['customerID']) !== '' )
        {
            $query->where('customerID', '=', $params['customerID']);//->orWhere('code2', '=', $params['productCode']);
        }

        if (isset($params['fullName']) && trim($params['fullName']) !== '' )
        {
            $query->where('fullName', '=', $params['fullName']);//->orWhere('code2', '=', $params['productCode']);
        }

        if (isset($params['groupID']) && trim($params['groupID']) !== '' )
        {
            $query->where('groupID', '=', $params['groupID']);//->orWhere('code2', '=', $params['productCode']);
        }

        if (isset($params['email']) && trim($params['email']) !== '' )
        {
            $query->where('email', '=', $params['email']);//->orWhere('code2', '=', $params['productCode']);
        }
    }

    static public function deleteRecords($clientCode, $id){
        $old = self::where('clientCode', $clientCode)->where('customerID', $id)->where('deleted', 0)->first();
        if($old){
            $change = self::where('clientCode', $clientCode)->where('customerID', $id)->update(['deleted' => 1]);
            UserLogger::setChronLogNew($old ? json_encode($old, true) : '', json_encode($change, true), "Customer Deleted");
        }
    }
}
