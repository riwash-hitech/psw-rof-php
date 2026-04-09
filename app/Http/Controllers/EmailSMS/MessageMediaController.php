<?php

namespace App\Http\Controllers\EmailSMS;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailSMS\Services\MessageMediaService;
use App\Models\PAEI\Message;
use App\Models\PAEI\MessageNotification;
use Illuminate\Http\Request;

class MessageMediaController extends Controller
{
    //

    protected $service;

    public function __construct(MessageMediaService $service){
        $this->service = $service;
    }

    public function sendSMS(Request $req){
        return $this->service->sendSMS($req);
    }

    public function callBack(Request $req){
        MessageNotification::where("id", 1)->update(["message_flags" => json_encode($req, true)]);
    }

    public function checkSmsStatus(Request $req){
        return $this->service->checkSmsStatus($req);
    }


    public function pushDailyMessage(Request $req){
        return $this->service->pushDailyMessage($req);
    }
}
