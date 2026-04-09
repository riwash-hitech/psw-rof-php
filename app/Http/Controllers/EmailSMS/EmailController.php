<?php

namespace App\Http\Controllers\EmailSMS;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailSMS\Services\EmailService;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    //
    protected $service;

    public function __construct(EmailService $service){
        $this->service = $service;
    }

    public function sendEmail(Request $req){
        return $this->service->sendEmail($req);
        return response("Email Sent Successfully.");
    }

    public function pushDailyEmail(Request $req){
        return $this->service->pushDailyEmail($req);
        // return response("Email Sent Successfully.");
    }
}
