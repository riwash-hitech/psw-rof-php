<?php

namespace App\Http\Controllers\Paei\Services;

use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\ErplySync;
use App\Models\PAEI\Payment;

class GetPaymentService
{

    protected $payment;
    protected $api;

    public function __construct(Payment $c, EAPIService $api)
    {
        $this->payment = $c;
        $this->api = $api;
    }

    public function saveUpdate($customers, $isResync = 0)
    {
        $lastModified = $this->api->flag == true ? strtotime(ErplySync::where("id", 1)->first()->acad_payment) : strtotime(ErplySync::where("id", 1)->first()->psw_payment);
        $erplyDocumentIDZeros = '';
        foreach ($customers as $c) {
            $paymentDocumentID = @$c["documentID"] ?? 0;
            if ($paymentDocumentID == 0) {
                $erplyDocumentIDZeros .= $c['paymentID'] . ',';
            }
            if ($c["lastModified"] > $lastModified) {
                $lastModified = $c["lastModified"];
            }
            $this->saveUpdatePayment($c, $this->api->client->clientCode);
        }
        if ($lastModified > 0 && $isResync == 0) {
            if ($this->api->flag == true) {
                ErplySync::where("id", 1)->update(["acad_payment" => date('Y-m-d H:i:s', $lastModified)]);
            } else {
                ErplySync::where("id", 1)->update(["psw_payment" => date('Y-m-d H:i:s', $lastModified)]);
            }
        }

        //resync payment if document id 0 once
        if($erplyDocumentIDZeros != ''){
            $this->getPaymentsByIds($erplyDocumentIDZeros);
        }

        // info("Get Payment Synced Erply to Synccare.");
        return response()->json($customers);
        return response()->json(['status' => 200, 'message' => "Payment fetched Successfully.", "data" => json_encode($customers)]);
    }

    public function saveUpdatePayment($product, $clientCode)
    {

        Payment::updateOrCreate(
            [
                "clientCode" => $clientCode,
                "paymentID"  =>  $product['paymentID']
            ],
            [
                "clientCode" => $clientCode,
                "paymentID" => @$product["paymentID"],
                "documentID" => @$product["documentID"],
                "customerID" => @$product["customerID"],
                "typeID" => @$product["typeID"],
                "type" => @$product["type"],
                "date" => @$product["date"],
                "sum" => @$product["sum"],
                "currencyCode" => @$product["currencyCode"],
                "currencyRate" => @$product["currencyRate"],
                "cashPaid" => @$product["cashPaid"],
                "cashChange" => @$product["cashChange"],
                "info" => @$product["info"],
                "cardHolder" => @$product["cardHolder"],
                "cardNumber" => @$product["cardNumber"],
                "cardType" => @$product["cardType"],
                "authorizationCode" => @$product["authorizationCode"],
                "referenceNumber" => @$product["referenceNumber"],
                "isPrepayment" => @$product["isPrepayment"],
                "bankTransactionID" => @$product["bankTransactionID"],
                "bankAccount" => @$product["bankAccount"],
                "bankDocumentNumber" => @$product["bankDocumentNumber"],
                "bankDate" => @$product["bankDate"],
                "bankPayerAccount" => @$product["bankPayerAccount"],
                "bankPayerName" => @$product["bankPayerName"],
                "bankPayerCode" => @$product["bankPayerCode"],
                "bankSum" => @$product["bankSum"],
                "bankReferenceNumber" => @$product["bankReferenceNumber"],
                "bankDescription" => @$product["bankDescription"],
                "bankCurrency" => @$product["bankCurrency"],
                "archivalNumber" => @$product["archivalNumber"],
                "storeCredit" => @$product["storeCredit"],
                "paymentServiceProvider" => @$product["paymentServiceProvider"],
                "aid" => @$product["aid"],
                "applicationLabel" => @$product["applicationLabel"],
                "pinStatement" => @$product["pinStatement"],
                "cryptogramType" => @$product["cryptogramType"],
                "cryptogram" => @$product["cryptogram"],
                "expirationDate" => @$product["expirationDate"],
                "entryMethod" => @$product["entryMethod"],
                "transactionType" => @$product["transactionType"],
                "transactionNumber" => @$product["transactionNumber"],
                "transactionId" => @$product["transactionId"],
                "transactionType2" => @$product["transactionType"],
                "transactionTime" => date('H:i:s', @$product['transactionTime']),
                "klarnaPaymentID" => @$product["klarnaPaymentID"],
                "certificateBalance" => @$product["certificateBalance"],
                "statusCode" => @$product["statusCode"],
                "statusMessage" => @$product["statusMessage"],
                "giftCardVatRateID" => @$product["giftCardVatRateID"],
                "signature" => @$product["signature"],
                "signatureIV" => @$product["signatureIV"],
                "attributes" => !empty($product['attributes']) ? json_encode($product['attributes'], 1) : '',
                "added" =>  date('Y-m-d H:i:s', $product['added']),
                "lastModified" => date('Y-m-d H:i:s', $product['lastModified']),
                // "axPending" =>  1, 
            ]
        );
    }


    public function getLastUpdateDate()
    {
        // echo "im call";
        // $latest = $this->payment->where("clientCode", $this->api->client->clientCode)->orderBy('lastModified', 'desc')->first();
        if ($this->api->flag == true) {
            $tempDate = ErplySync::where("id", 1)->first()->acad_payment;
            return strtotime($tempDate);
        } else {
            $tempDate = ErplySync::where("id", 1)->first()->psw_payment;
            return strtotime($tempDate);
        }
        return 0; // strtotime($latest);
    }

    public function getPaymentsByIds($ids)
    {
        $ids = rtrim($ids, ',');
        $param = array(
            "paymentIDs" => $ids,
        );
        $res = $this->api->sendRequest("getPayments", $param);
        if ($res['status']['errorCode'] == 0 && !empty($res['records'])) {
           $this->saveUpdate($res['records']);
        }
    }
}
