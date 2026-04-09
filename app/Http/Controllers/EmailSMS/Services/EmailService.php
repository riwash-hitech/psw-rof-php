<?php
namespace App\Http\Controllers\EmailSMS\Services;

use App\Mail\SendGridMailV2;
use App\Models\PAEI\EmailNotification;
use App\Models\PAEI\EmailNotificationHistory;
use App\Models\PAEI\SalesDocument;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Traits\ResponseTrait;

// use App\Mail\SendGridMailV2;
// use Illuminate\Support\Facades\Mail;

class EmailService {

    use ResponseTrait;

    public function sendEmail($req){
        
        $type = $req->type ? $req->type : '';
        if($type == "resend"){
             
            return $this->resendEmail($req);
            die;
        }
        // dd(config("mail.mailers.smtp"));

        //first getting pending email notifications

        $notificatiosn = EmailNotification::whereIn("pendingProcess", [1,2])->limit(1)->get();

        if($notificatiosn->isEmpty()){
            return response("All Email Notification Sent Successfully.");
        }

        // status 1 Pending
        // status 2 processing
        // status 3 empty customer email 

        foreach($notificatiosn as $notif){
            $notif->pendingProcess = 2;
            $notif->save();

            if(@$notif->toEmail == '' || is_null(@$notif->toEmail) == true){
                $notif->pendingProcess = 3;
                $notif->save();
            }
            try{

                $from = $notif->fromEmail;
                $title = $from == "notifications@psw.com.au" ? "PSW" : "PSW Academy";
                // $from2 = "notifications@academyuniforms.com.au";
                $recipients = [$notif->toEmail];
                $payload = [
                    "customer" => "Lawa Joshi", 
                ];

                Mail::to($recipients)->send((new SendGridMailV2("Pickup Order Notification" , $notif->message, $payload))->from($from, $title));
                $notif->pendingProcess = 0;
                $notif->errorMsg = '';
                $notif->save();

                //recording history
                $historyDetails = array(
                    "parentID" => $notif->id,
                    "isDaily" => 2,
                    "toEmail" => $notif->toEmail,
                    "fromEmail" => $notif->fromEmail,
                    "subject" => $notif->subject,
                    "message" => $notif->message,
                ); 
                EmailNotificationHistory::create(
                    $historyDetails
                );

            }catch(Exception $e){
                $notif->errorMsg = $e->getMessage();
                $notif->save();

                info($e);
            }

        }


        return response("Email Sent Successfully.");

  
    }
    
    public function resendEmail($req){
        $id = $req->id ? $req->id : '';
        
        // if($id == ''){
        //     return $this->failWithMessage("Invalid ID!!!");
        //     // return response("Invalid ID!!!");
        // } 
        //first getting pending email notifications

        $notif = EmailNotification::where("id", $req->id)->first();

        if(!$notif){
            return $this->failWithMessage("Invalid ID!!!");
            return $this->failWithMessage("Email Notification Not Found!");
            // return response("Email Notification Not Found!");
        }
        
        // dd($notif);
        // dd(" hello im from resend email");
        try{

            $from = $notif->fromEmail;
            $title = $from == "notifications@psw.com.au" ? "PSW" : "PSW Academy";
            // $from2 = "notifications@academyuniforms.com.au";
            $recipients = [$notif->toEmail];
            $payload = [
                "customer" => "Lawa Joshi", 
            ]; 

            
            Mail::to($recipients)->send((new SendGridMailV2("Pickup Order Notification" , $notif->message, $payload))->from($from, $title));
             
            $details = array(
                "parentID" => $notif->id,
                "isDaily" => 0,
                "toEmail" => $notif->toEmail,
                "fromEmail" => $notif->fromEmail,
                "subject" => $notif->subject,
                "message" => $notif->message,
            );

            EmailNotificationHistory::create(
                $details
            );

            return $this->successWithMessage("Email Re-Sent Successfully.");
            
            

        }catch(Exception $e){ 
            info($e->getMessage());

            return $this->successWithMessage("Failed while sending Email!!!");
        } 
  
    }
 
    public function pushDailyEmail($req){
        // dd(now()->toDateString());
        $datas = EmailNotification::with(
                    [
                        "history" => function($q){
                            $q->where("isDaily", 1);
                        }
                    ]
                )
                ->where("isDailyNotify", 1)
                ->where("pendingProcess", 0)
                ->where("isPicked", 0)
                ->whereDate('created_at','<',  now()->toDateString())
                ->orderBy("updated_at", "asc")
                ->limit(5)
                ->get();

        // dd($datas);

        $maxSMSHistoryLimit = 2;

        foreach($datas as $data){
            // dd($data);
            //if history count 6 or greater then don't need to push sms notification
            $countHistory = 0;
            // dd($data->history);
            foreach($data->history as $history){
                $countHistory = $countHistory + 1;
            }

            $isMaxLimitCrossed = 0;
            $isTodayNotify = 0;

            if($countHistory >= $maxSMSHistoryLimit){
                //now stop push sms notification max limit
                $data->isDailyNotify = 0;
                $data->save();
                $isMaxLimitCrossed = 1;
            }

            //now check is today 
            $checkToday = EmailNotification::
                with(
                [
                    "history" => function($q){
                        $q->whereDate('created_at',  now()->toDateString())
                        ->where("isDaily", 1);
                    }
                ]) 
                ->where("isDailyNotify", 1)
                ->where("pendingProcess", 0)
                ->where("isPicked", 0)
                ->where("id", $data->id)
                ->first();
            
            if(count($checkToday->history) > 0){
                $isTodayNotify = 1;
            }

            // dd($isTodayNotify);
            // if($checkToday->history)

            if($isMaxLimitCrossed == 0 && $isTodayNotify == 0){ 

                $check = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->orderID)->where("pickedOrder", 0)->first();
                if($check){
                    //first check if the daily notification already pushed then do not need to send notification
                    //this order is still not picked so lets send sms notification
                    //now we should only send sms notification between 10 AM to 11 AM 

                    if(  date("H:i") >= "10:00" && date("H:i") <= "11:00"){
                        //now sending sms

                        try{

                            $from = $data->fromEmail;
                            $title = $from == "notifications@psw.com.au" ? "PSW" : "PSW Academy";
                            // $from2 = "notifications@academyuniforms.com.au";
                            $recipients = [$data->toEmail];
                            $payload = [
                                "customer" => "Lawa Joshi", 
                            ]; 
                
                            
                            Mail::to($recipients)->send((new SendGridMailV2("Pickup Order Notification" , $data->message, $payload))->from($from, $title));
                             
                            $details = array(
                                "parentID" => $data->id,
                                "isDaily" => 1,
                                "toEmail" => $data->toEmail,
                                "fromEmail" => $data->fromEmail,
                                "subject" => $data->subject,
                                "message" => $data->message,
                            );
                
                            EmailNotificationHistory::create(
                                $details
                            );
                
                            // return $this->successWithMessage("Email Re-Sent Successfully.");
                            
                            
                
                        }catch(Exception $e){ 
                            info($e->getMessage());
                
                            // return $this->successWithMessage("Failed while sending Email!!!");
                        }
                         
        
                         
                    }else{
                        info("All Ok But Time Out");
                    }

                }else{
                    $data->isPicked = 1;
                    $data->save();
                }
            }
            $data->updated_at = date("Y-m-d H:i:s");
            $data->save();
        }

        info("Daily Push Email Notification Cron Called.");
        return response("Daily Push Email Notification Cron Called.");
    }
 
}


