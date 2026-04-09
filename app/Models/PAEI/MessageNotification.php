<?php

namespace App\Models\PAEI;

use App\Classes\UserLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageNotification extends Model
{
    use HasFactory;
    protected $table = 'newsystem_message_logs';
    protected $fillable = [];
    protected $guarded = [];

    
   

    //getting order details
    public function invoice(){
        // info("hello this is value of client code .................................... ".$this->clientCode);

        return $this->hasOne(SalesDocument::class, "salesDocumentID", "orderID")
                    ->join("newsystem_sales_documents", "newsystem_sales_documents.clientCode", "newsystem_message_logs.clientCode");
    }

    public function history(){
        return $this->hasMany(MessageNotificationHistory::class, "parentID", "id");
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
        $old = self::where('clientCode', $clientCode)->where('employeeID', $id)->where('deleted', 0)->first();
        if($old){
            $change = self::where('clientCode', $clientCode)->where('employeeID', $id)->update(['deleted' => 1]);
            UserLogger::setChronLogNew($old ? json_encode($old, true) : '', json_encode($change, true), "Employee Deleted");    
        }
    } 
    
    
}
