<?php

namespace App\Models\PAEI;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailNotification extends Model
{
    use HasFactory;
    protected $table = 'newsystem_email_notifications';
    protected $fillable = [];
    protected $guarded = [];

    public function history(){
        return $this->hasMany(EmailNotificationHistory::class, "parentID", "id");
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
