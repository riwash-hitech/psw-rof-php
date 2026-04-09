<?php
namespace App\Http\Controllers\EmailSMS\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Message;
use App\Models\PAEI\MessageNotification;
use App\Models\PAEI\MessageNotificationHistory;
use App\Models\PAEI\SalesDocument;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use App\Traits\ResponseTrait;

class MessageMediaService {

    use ResponseTrait;
    protected $authUserName = '';
    protected $authPassword = '';

    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $baseUri = 'https://api.messagemedia.com';//'https://api.synchmm.com';
    protected $headers;
    protected $api;
    public $callbackUrl = "https://pswstaging.synccare.com.au/php/public/sms-callback";
    
    public function __construct(EAPIService $api)
    {
        $this->api = $api;
        $this->apiKey = config('sendgrid.SMS_API_KEY');// env('SMS_API_KEY');
        $this->apiSecret = config('sendgrid.SMS_API_SECRET'); //env('SMS_API_SECRET');
        $this->client = new Client(['base_uri' => $this->baseUri]);
        $this->headers =  [
            'Authorization' => 'Basic ' . base64_encode($this->apiKey.":".$this->apiSecret),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]; 
        if($this->api->isLiveEnv() == 1){
            $this->callbackUrl = "https://psw.synccare.com.au/php/sms-callback";
        }

    }
      
    public function sendSms($req)
    {

        return $this->sendSmsV2($req);
        die;
        // dd($this->apiKey, $this->apiSecret);
        $callbackUrl = "https://pswstaging.synccare.com.au/php/public/sms-callback";
        if($this->api->isLiveEnv() == 1){
            $callbackUrl = "https://psw.synccare.com.au/php/sms-callback";
        }
         
        $data = [
            'messages' => [
                [
                    'callback_url' => $callbackUrl,
                    'content' => 'Hello Dev SMS Test, LIVE Server',
                    'destination_number' => "+61430831237",
                    'format' => 'SMS',
                    // "delivery_report" => true
                ],
            ],
        ];
        // dd($this->headers);

        try {
            $response = Http::withHeaders($this->headers)->post($this->baseUri."/v1/messages", $data);
            // dd($response);
            // Handle the response
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            
            if ($statusCode == 201 || $statusCode == 202) {
                $response = json_decode($responseBody, true);

                $details = array(
                    // "clientCode" => $
                    "callbackUrl" => $response["messages"][0]["callback_url"],
                    "delivery_report" => $response["messages"][0]["delivery_report"] == true ? 1 : 0,
                    "destination_number" => $response["messages"][0]["destination_number"],
                    "format" => $response["messages"][0]["format"],
                    "message_expiry_timestamp" => $response["messages"][0]["message_expiry_timestamp"],
                    "message_flags" => empty($response["messages"][0]["message_flags"]) ? '' : json_encode($response["messages"][0]["message_flags"], true),
                    "messageID" => $response["messages"][0]["message_id"],
                    "metadata" => $response["messages"][0]["metadata"],
                    "scheduled" => $response["messages"][0]["scheduled"],
                    "status" => $response["messages"][0]["status"],
                    "content" => $response["messages"][0]["content"],
                    "source_number" => $response["messages"][0]["source_number"],
                    "rich_link" => $response["messages"][0]["rich_link"],
                    "media" => $response["messages"][0]["media"],
                    "subject" => $response["messages"][0]["subject"],
                );



                MessageNotification::create($details);
                // Message sent successfully
                return $response;
            } else {
                // Failed to send message
                throw new Exception('Failed to send SMS: ' . $responseBody);
            }
        } catch (Exception $e) {
            // Handle any exceptions
            throw new Exception('Failed to send SMS: ' . $e->getMessage());
        }
    }

    public function sendSmsV2($req)
    {
        // dd($this->apiKey, $this->apiSecret);
        // dd($this->apiKey, $this->apiSecret);
        if($req->type == "resend"){
            return $this->resendSMS($req);
            die;
        }

        //getting pending message notifications

        $datas = MessageNotification::where("pendingProcess", 1)->limit(1)->get();

        foreach($datas as $data){

            $number = $data->destination_number;
            $count = strlen($number);
            if($count == 10){
                // remove zero 
                $number = ltrim($number, "0");
            }

            if(strlen($number) == 9){
                $number = "+61".$number;
            }

            if(strlen($number) == 12){


                $payload = [
                    'messages' => [
                        [
                            'callback_url' => $this->callbackUrl,
                            'content' => $data->content,
                            'destination_number' => $number,
                            'format' => 'SMS',
                            "delivery_report" => true
                        ],
                    ],
                ];
                // dd($payload);
        
                try {
                    $response = Http::withHeaders($this->headers)->post($this->baseUri."/v1/messages", $payload);
                    // dd($response);
                    // Handle the response
                    $statusCode = $response->getStatusCode();
                    $responseBody = $response->getBody()->getContents();
                    
                    if ($statusCode == 201 || $statusCode == 202) {
                        $response = json_decode($responseBody, true);
        
                        $details = array(
                            // "clientCode" => $
                            "callbackUrl" => $response["messages"][0]["callback_url"],
                            "delivery_report" => $response["messages"][0]["delivery_report"] == true ? 1 : 0,
                            "destination_number" => $response["messages"][0]["destination_number"],
                            "format" => $response["messages"][0]["format"],
                            "message_expiry_timestamp" => $response["messages"][0]["message_expiry_timestamp"],
                            "message_flags" => empty($response["messages"][0]["message_flags"]) ? '' : json_encode($response["messages"][0]["message_flags"], true),
                            "messageID" => $response["messages"][0]["message_id"],
                            "metadata" => $response["messages"][0]["metadata"],
                            "scheduled" => $response["messages"][0]["scheduled"],
                            "status" => $response["messages"][0]["status"],
                            "content" => $response["messages"][0]["content"],
                            "source_number" => $response["messages"][0]["source_number"],
                            "rich_link" => $response["messages"][0]["rich_link"],
                            "media" => $response["messages"][0]["media"],
                            "subject" => $response["messages"][0]["subject"],
                            "pendingProcess" => 0
                        );  
                        // MessageNotification::create($details);
                        MessageNotification::where("id", $data->id)->update(
                            $details
                        );

                        //now adding this notification to history

                        $historyDetails = array(
                            "parentID" => $data->id,
                            "isDaily" => 2,
                            "callbackUrl" => $response["messages"][0]["callback_url"],
                            "delivery_report" => $response["messages"][0]["delivery_report"] == true ? 1 : 0,
                            "destination_number" => $response["messages"][0]["destination_number"],
                            "format" => $response["messages"][0]["format"],
                            "message_expiry_timestamp" => $response["messages"][0]["message_expiry_timestamp"],
                            "message_flags" => empty($response["messages"][0]["message_flags"]) ? '' : json_encode($response["messages"][0]["message_flags"], true),
                            "messageID" => $response["messages"][0]["message_id"],
                            "metadata" => $response["messages"][0]["metadata"],
                            "scheduled" => $response["messages"][0]["scheduled"],
                            "status" => $response["messages"][0]["status"],
                            "content" => $response["messages"][0]["content"],
                            "source_number" => $response["messages"][0]["source_number"],
                            "rich_link" => $response["messages"][0]["rich_link"],
                            "media" => $response["messages"][0]["media"],
                            "subject" => $response["messages"][0]["subject"],
                            // "pendingProcess" => 0
                        );  
                         
                        MessageNotificationHistory::updateOrcreate(
                            [
                                "parentID" => $data->id,
                                "messageID" => $response["messages"][0]["message_id"],
                            ],
                            $historyDetails
                        );

                        // Message sent successfully
                        return $response;
                    } else {
                        $data->errorMsg = $responseBody;
                        $data->save();
                        // Failed to send message
                        throw new Exception('Failed to send SMS: ' . $responseBody);
                    }
                } catch (Exception $e) {
                    $data->errorMsg = $e->getMessage();
                    $data->save();
                    // Handle any exceptions
                    throw new Exception('Failed to send SMS: ' . $e->getMessage());
                }
            }else{
                //invalid phone number
                $data->pendingProcess = 3;
                $data->status = 'failed';
                $data->save();
            }
        }

        return response("SMS Sent Successfully.");
         
        
    }

    public function sendSmsV3($payload){
        try {
            $response = Http::withHeaders($this->headers)->post($this->baseUri."/v1/messages", $payload);
            // dd($response);
            // Handle the response
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            
            if ($statusCode == 201 || $statusCode == 202) {
                $response = json_decode($responseBody, true); 
                // Message sent successfully
                return [ "status" => 1, "response"=> $response];
            }  
        } catch (Exception $e) {
            return ["status" => 0, "error" => $e->getMessage()];
           
        }
    }

    public function resendSMS($req){    

        //first getting order details
        if($req->id && $req->id != ''){
            $data = MessageNotification::where("id", $req->id)->first();
            // dd($data);
            if($data){
                $payload = [
                    'messages' => [
                        [
                            'callback_url' => $this->callbackUrl,
                            'content' => $data->content,
                            'destination_number' => $data->destination_number,
                            'format' => 'SMS',
                            "delivery_report" => true
                        ],
                    ],
                ];

                $response = $this->sendSmsV3($payload);
                if($response["status"] == 1){
                    $response = $response["response"];

                    $details = array(
                        "parentID" => $data->id,
                        "isDaily" => 0,
                        "callbackUrl" => $response["messages"][0]["callback_url"],
                        "delivery_report" => $response["messages"][0]["delivery_report"] == true ? 1 : 0,
                        "destination_number" => $response["messages"][0]["destination_number"],
                        "format" => $response["messages"][0]["format"],
                        "message_expiry_timestamp" => $response["messages"][0]["message_expiry_timestamp"],
                        "message_flags" => empty($response["messages"][0]["message_flags"]) ? '' : json_encode($response["messages"][0]["message_flags"], true),
                        "messageID" => $response["messages"][0]["message_id"],
                        "metadata" => $response["messages"][0]["metadata"],
                        "scheduled" => $response["messages"][0]["scheduled"],
                        "status" => $response["messages"][0]["status"],
                        "content" => $response["messages"][0]["content"],
                        "source_number" => $response["messages"][0]["source_number"],
                        "rich_link" => $response["messages"][0]["rich_link"],
                        "media" => $response["messages"][0]["media"],
                        "subject" => $response["messages"][0]["subject"],
                        // "pendingProcess" => 0
                    );  
                     
                    MessageNotificationHistory::updateOrcreate(
                        [
                            "parentID" => $data->id,
                            "messageID" => $response["messages"][0]["message_id"],
                        ],
                        $details
                    );

                    return $this->successWithMessage("SMS Re-sent successfully.");
                }  
                
                return $this->failWithMessage("Failed while sending SMS!!!");
            }
        } 
    }

    public function pushDailyMessage($req){

        //first getting message notification
        $datas = MessageNotification::
            // with("history")
            // with(
            // [
            //     "history" => function($q){
            //         $q->whereDate('created_at', '!=', now()->toDateString());
            //     }
            // ])
            with(
            [
                "history" => function($q){
                    $q->where('isDaily', 1);
                }
            ])
            ->where("isDailyNotify", 1)
            ->where("pendingProcess", 0)
            ->where("isPicked", 0)
            ->orderBy("updated_at", "asc")
            ->limit(5)
            ->get();
        
        
        
        $maxSMSHistoryLimit = 6;

        foreach($datas as $data){
            // dd($data);
            //if history count 6 or greater then don't need to push sms notification
            $countHistory = 0;
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
            $checkToday = MessageNotification::
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

            // if($checkToday->history)

            if($isMaxLimitCrossed == 0 && $isTodayNotify == 0){ 

                $check = SalesDocument::where("clientCode", $data->clientCode)->where("salesDocumentID", $data->orderID)->where("pickedOrder", 0)->first();
                if($check){
                    //first check if the daily notification already pushed then do not need to send notification
                    //this order is still not picked so lets send sms notification
                    //now we should only send sms notification between 10 AM to 11 AM 

                    if(date("H:i") >= "10:00" && date("H:i") <= "12:00"){
                        //now sending sms

                        $payload = [
                            'messages' => [
                                [
                                    'callback_url' => $this->callbackUrl,
                                    'content' => $data->content,
                                    'destination_number' => $data->destination_number,
                                    'format' => 'SMS',
                                    "delivery_report" => true
                                ],
                            ],
                        ];
        
                        $response = $this->sendSmsV3($payload);
                        if($response["status"] == 1){
                            $response = $response["response"];
        
                            $details = array(
                                "parentID" => $data->id,
                                "isDaily" => 1,
                                "callbackUrl" => $response["messages"][0]["callback_url"],
                                "delivery_report" => $response["messages"][0]["delivery_report"] == true ? 1 : 0,
                                "destination_number" => $response["messages"][0]["destination_number"],
                                "format" => $response["messages"][0]["format"],
                                "message_expiry_timestamp" => $response["messages"][0]["message_expiry_timestamp"],
                                "message_flags" => empty($response["messages"][0]["message_flags"]) ? '' : json_encode($response["messages"][0]["message_flags"], true),
                                "messageID" => $response["messages"][0]["message_id"],
                                "metadata" => $response["messages"][0]["metadata"],
                                "scheduled" => $response["messages"][0]["scheduled"],
                                "status" => $response["messages"][0]["status"],
                                "content" => $response["messages"][0]["content"],
                                "source_number" => $response["messages"][0]["source_number"],
                                "rich_link" => $response["messages"][0]["rich_link"],
                                "media" => $response["messages"][0]["media"],
                                "subject" => $response["messages"][0]["subject"],
                                // "pendingProcess" => 0
                            );  
                             
                            MessageNotificationHistory::updateOrcreate(
                                [
                                    "parentID" => $data->id,
                                    "messageID" => $response["messages"][0]["message_id"],
                                ],
                                $details
                            ); 
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

        info("Daily Push SMS Notification Cron Called.");
        return response("Daily Push SMS Notification Cron Called.");
    }



    public function checkSmsStatus($req){
        $type = $req->type ? $req->type : '';
        
        if($type == "history"){
            return $this->checkSmsStatusHistory($req);
            die;
        }
        $datas = MessageNotification::where("pendingProcess", 0)->whereNotIn("status", ["submitted", "delivered"])->orderBy("updated_at", "asc")->limit(3)->get();
        
        foreach($datas as $data){
            $data->updated_at = date("Y-m-d H:i:s");
            $data->save();

            $response = Http::withHeaders($this->headers)->get($this->baseUri."/v1/messages/".$data->messageID);
            $res =json_decode($response->getBody()->getContents(), true);

            // dd(json_decode($response->getBody()->getContents(), true));
            // dd($res);
            if(@$res["status"]){
                // dd("hi");
                $data->status = @$res["status"];
                $data->save();
            }
            // dd($res);

        }
                
        return response("Checking Queued Messages Status..."); 
    }

    public function checkSmsStatusHistory($req){

        $datas = MessageNotificationHistory::whereNotIn("status", ["submitted","delivered"])->orderBy("updated_at", "asc")->limit(3)->get();
        
        foreach($datas as $data){
            $data->updated_at = date("Y-m-d H:i:s");
            $data->save();

            $response = Http::withHeaders($this->headers)->get($this->baseUri."/v1/messages/".$data->messageID);
            $res =json_decode($response->getBody()->getContents(), true);

            // dd(json_decode($response->getBody()->getContents(), true));
            // dd($res);
            if(@$res["status"]){
                // dd("hi");
                $data->status = @$res["status"];
                $data->save();
            }
            // dd($res);
        }

        return response("Checking Queued Messages History Status..."); 
    } 

    protected function api($type, $url, $payload=[]){

        $res = $this->client->sendRequest($type, $url, $payload); 
        $responseBody = $res->getBody()->getContents();

        return array("statusCode" => $res->getStatusCode(), "records" => json_decode($responseBody, true));
    }

    
}


