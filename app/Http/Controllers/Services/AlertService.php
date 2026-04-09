<?php

namespace App\Http\Controllers\Services;

use App\Mail\SendGridMailV2;
use App\Models\PAEI\ErplySync;
use App\Models\PAEI\SalesDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class AlertService
{  

    public function salesOrder($req)
    {
        $limit = $req->limit ?? 50;
        $debug = $req->debug ?? 0;
        $syncInfo = ErplySync::where("id", 1)->first();
        if($syncInfo->alertSalesOrder == 1 && $syncInfo->alertUpdated <= Carbon::now()->subHours(5)){
            $syncInfo->alertSalesOrder = 0;
            $syncInfo->save();
        }
        if($syncInfo->alertSalesOrder == 1){
            return response("Sales Document syncing issue already alerted and will trigger again after 5hrs.");
        }
        $checkOrder = SalesDocument::orderBy('id', 'desc')
        ->limit($limit)
        ->get(['id', 'salesAxID']);
        if($debug == 1){
            dd($checkOrder);
        }
        $allPending = $checkOrder->every(fn($row) => $row->salesAxID == null);
        if($allPending){
            //alert notification that there might be issue 
            $recipients = ['kiran@hitechvalley.com.au', 'lawa@retailcare.com.au'];
            $payload = [
                "customer" => "Lawa Joshi", 
            ]; 
            // Mail::to($recipients)->send((new SendGridMailV2("Alert Notification" , 'Hi, there might be an issue with transaction synchronization in AX!', $payload))->from('notifications@psw.com.au', 'Alert Notification'));
            $syncInfo->alertSalesOrder = 1;
            $syncInfo->alertUpdated = date('Y-m-d H:i:s');
            $syncInfo->save();
            return response("Hi, there might be an issue with transaction synchronization in AX!");
        }
        return response("Sales Document syncing into AX...");
    }
     
}
