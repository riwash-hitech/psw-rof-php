<?php

namespace App\Http\Controllers\Alert;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetSalesDocumentService;
use App\Http\Controllers\Services\EAPIService;
use App\Mail\SendFailCronMail;
use App\Models\PAEI\{ErplySync, SalesDocument};
use App\Models\PswClientLive\{AxSalesOrder, SalesOrder};
use App\Models\PswClientLive\Local\LiveSalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FailCronAlertController extends Controller
{

    protected $service;
    protected $api;

    public function __construct(GetSalesDocumentService $service, EAPIService $api)
    {
        $this->service = $service;
        $this->api = $api;
    }

    public function sendMail(Request $request)
    {
        if(isset($request->check)){
         return $request->check;
        }


        try {

            // check erply data to database and send mail
            $erplySync = $this->getSalesDocuments();
            dump($erplySync,'server sync');

            // check sale order
            $saleOrderData =  $this->saleOrder();
            dump($saleOrderData,'database sync');

            // check Product Sync

        } catch (\Exception $e) {
            \Log::error('Error in sendMail function', [
                'message' => $e->getMessage()
            ]);
            dd($e);
        }
    }

    public function saleOrder()
    {
        // Latest ERPLY record
        $erplyOrder = SalesDocument::orderByDesc('lastModified')->first();

        // Latest AX record
        $axOrder = AxSalesOrder::orderByDesc('MODIFIEDDATEANDTIME')->first();

        if (!$erplyOrder || !$axOrder) {
            \Log::warning('Sync check skipped: missing ERPLY or AX data');
            return 'No Data';
        }

        // Parse times (server default timezone)
        $erplyTime = Carbon::parse($erplyOrder->lastModified);
        $axTime = Carbon::parse($axOrder->CREATEDDATETIME);
        $currentTime = Carbon::now();

        // Difference in minutes
        $diffInMinutes = $erplyTime->diffInMinutes($axTime, false);

        // Check if AX is behind ERPLY by >1 hour OR dates are different
        $erplyDate = $erplyTime->format('Y-m-d');
        $axDate = $axTime->format('Y-m-d');

        if ($diffInMinutes <= 60 && $erplyDate === $axDate) {
            dump('AX is not behind ERPLY enough, skipping...');
            return 'No Delay';
        }

        // Prevent duplicate emails within 30 minutes
        $cacheKey = 'sync_alert_sent_2';
        if (cache()->has($cacheKey)) {
            \Log::info('Sync alert already sent recently, skipping...');
            return 'Recently Sent';
        }

        // Format delay in H:i:s
        $delay = gmdate("H:i:s", abs($diffInMinutes) * 60);

        // Get emails from env
        $emails = array_map('trim', explode(',', env('SYNC_ALERT_EMAILS', '')));
        if (empty($emails)) {
            \Log::warning('No email recipients defined in SYNC_ALERT_EMAILS');
            return 'No Recipients';
        }

        // To and CC
        $to = array_shift($emails);
        $cc = $emails;
        $message = '⚠️ Middle server database  to AX database sync delay detected. Cron job may not be running properly.';

        // Send email
        Mail::to($to)
            ->cc($cc)
            ->send(new SendFailCronMail($erplyTime, $axTime, $currentTime, $delay, $message));

        // Cache lock
        cache()->put($cacheKey, true, now()->addMinutes(30));

        \Log::warning('Sync delay detected and email sent', [
            'current_time' => $currentTime->format('Y-m-d H:i:s'),
            'erply_time' => $erplyTime->format('Y-m-d H:i:s'),
            'ax_time' => $axTime->format('Y-m-d H:i:s'),
            'delay' => $delay,
            'to' => $to,
            'cc' => $cc
        ]);

        $res = [
            'type' => 'Mail Sent',
            'to' => $to,
            'cc' => $cc,
            'delay' => $delay
        ];

        return $res;
    }

    public function getSalesDocuments()
    {
        $param = [
            "orderBy" => "lastChanged",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "getRowsForAllInvoices" => 1,
            "changedSince" => $this->service->getLastUpdateDate(),
            "getAddedTimestamp" => 1
        ];

        try {

            $res = $this->api->sendRequest("getSalesDocuments", $param);

            if ($res['status']['errorCode'] != 0 || empty($res['records'])) {
                \Log::warning('ERPLY API failed or empty');
                return 'No Data';
            }

            $latestData = $res['records'][0];

            // Convert UNIX timestamp → Carbon (server default timezone)
            $lastModifiedTime = Carbon::createFromTimestamp($latestData['lastModified']);
            $currentTime = Carbon::now();

            // older than 3 hours?
            if ($lastModifiedTime > $currentTime->copy()->subHours(3)) {
                dump('No delay - within 3 hours');
                return 'No Delay';
            }

            // Prevent duplicate emails
            $cacheKey = 'erply_to_ax_alert';
            if (cache()->has($cacheKey)) {
                \Log::info('ERPLY delay alert already sent recently');
                return 'Recently Sent';
            }

            // Delay in minutes
            $diffInMinutes = $currentTime->diffInMinutes($lastModifiedTime);

            // Format delay
            $delay = gmdate("H:i:s", $diffInMinutes * 60);

            // Emails
            $emails = array_map('trim', explode(',', env('SYNC_ALERT_EMAILS', '')));
            if (empty($emails)) {
                return 'No Recipients';
            }

            $to = array_shift($emails);
            $cc = $emails;
            $message = '⚠️ ERPLY Server to Middle Server database sync delay detected. Cron job may not be running properly.';

            // Send mail
            Mail::to($to)
                ->cc($cc)
                ->send(new SendFailCronMail($lastModifiedTime, null, $currentTime, $delay, $message));

            // Set cache lock
            cache()->put($cacheKey, true, now()->addMinutes(30));

            \Log::warning('ERPLY delay detected and email sent', [
                'current_time' => $currentTime->format('Y-m-d H:i:s'),
                'last_modified' => $lastModifiedTime->format('Y-m-d H:i:s'),
                'delay' => $delay,
                'to' => $to,
                'cc' => $cc
            ]);

            dump('Mail Sent', $lastModifiedTime, $delay);

            return 'Erply cron not run Mail Sent';
        } catch (\Exception $e) {
            \Log::error('Error in getSalesDocuments', ['message' => $e->getMessage()]);
            dump($e->getTraceAsString());
            return 'Failed';
        }
    }
}
