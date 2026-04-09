<?php

namespace App\Models\PAEI;

use App\Classes\UserLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'newsystem_warehouse_locations';
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
        $old = self::where('clientCode', $clientCode)->where('warehouseID', $id)->where('deleted', 0)->first();
        if($old){
            $change = self::where('clientCode', $clientCode)->where('warehouseID', $id)->update(['deleted' => 1]);
            UserLogger::setChronLogNew($old ? json_encode($old, true) : '', json_encode($change, true), "Warehouse Deleted");    
        }
    } 
    
}
