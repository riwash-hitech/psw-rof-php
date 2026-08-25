<?php

namespace App\Http\Controllers\Paei;

use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Paei\Services\GetCustomerService;
use App\Http\Controllers\Services\EAPIService;
use Illuminate\Http\Request;
use App\Traits\UserOperationTrait;

class GetCustomerController extends Controller
{
    //
    protected $service;
    protected $api;
    protected $userOperationInterface;
    use UserOperationTrait;

    public function __construct(GetCustomerService $service, EAPIService $api, UserOperationInterface $userOperationInterface)
    {
        $this->service = $service;
        $this->api = $api;
        $this->userOperationInterface = $userOperationInterface;
    }

    public function getCustomer(Request $req)
    {
        info("Customer Cron Sync " . $this->api->client->ENTITY);
        $isAdded = 0;
        $limit = request('limit', 200);
        if (@$req->type == "added") {
            $isAdded = 1;
        }

        if (@$req->is_bulk == 1) {
            $bulkLimit = request('limit', 500);
            $param = array(
                "orderBy" => $isAdded == 1 ? "added" : "changed",
                "orderByDirection" => "ASC",
                "take" => $bulkLimit,
            );

            if ($isAdded == 1) {
                $param["addedFrom"] = $this->service->getLastUpdateDate(1);
            } else {
                $param["match"] = ">=";
                $param["changed"] = $this->service->getLastUpdateDate(0);
            }

            dump($param, $req->all());

            $res = $this->api->sendRequestBySwagger("https://api-crm-au.erply.com/v1/customers", $param);

            if (isset($req->debug) && $req->debug == 1) {
                dd($res);
            }

            if (!empty($res) && is_array($res)) {
                return $this->service->saveUpdateBulk($res, $isAdded);
            }

            dump('customer saved');
            return response()->json(["status" => 200, "message" => "All Customers Up-to-date"]);
        }



        $param = array(
            "orderBy" => $isAdded == 1 ? "customerID" : "lastChanged",
            "orderByDir" => "asc",
            "recordsOnPage" => $limit,
            "getAddresses" => 1,
            "getContactPersons" => 1,
            "responseMode" => "detail",
            // "changedSince" => $this->service->getLastUpdateDate(),
            "sessionKey" => $this->api->client->sessionKey
        );



        //dd($this->api->client);

        if ($isAdded == 1) {
            $param["createdUnixTimeFrom"] = $this->service->getLastUpdateDate(1);
        }
        if ($isAdded == 0) {
            $param["changedSince"] = $this->service->getLastUpdateDate(0);
        }

        dump($param, $req->all());

        $res = $this->api->sendRequest("getCustomers", $param);

        if (isset($req->debug) && $req->debug == 1) {
            dd($res);
        }

        dump($res['status']);


        if ($res['status']['errorCode'] == 0 && !empty($res['records'])) {
            return $this->service->saveUpdate($res['records']);
        }

        dump('customer saved');
        //   [
        //   'take' => '200',
        //   'sort' => json_encode([
        //     "selector" => "changed",
        //     "desc" => false,
        //     ]),
        //   'match' => '>=',
        //     'changed' => $lastModified,
        //     'orderBy' => 'changed',
        //     'orderByDirection' => 'ASC',
        //    ]
        //SWAGGER URL
        // $param = array(
        //     "take" => "100",
        //     "sort" => json_encode([
        //         "selector" => "changed",
        //         "desc" => false
        //     ]),
        //     "match" => ">=",
        //     "changed" => $this->service->getLastUpdateDate(),
        //     "orderBy" => 'changed',
        //     "getAddresses" => 1,
        //     "orderByDirection" => 'ASC'
        //  );

        //  $res = $this->api->sendRequestBySwagger("https://api-crm-au.erply.com/v1/customers", $param);
        //  dd($res);
        //  if(count($res) > 0){
        //    return $this->service->saveUpdate($res);
        //  }

        //  print_r($param);
        //  die;
        //  $res = $this->api->sendRequest("getCustomers", $param);
        //  dd($res);
        //  if($res['status']['errorCode'] == 0 && !empty($res['records'])){
        //     $this->service->saveUpdate($res['records']);
        //  }
    }

    public function getOperationLogCustomer()
    {

        $param = array(
            "orderBy" => "added",
            "orderByDir" => "asc",
            "recordsOnPage" => "200",
            "tableName" => "customers",
            // "getRowsForAllInvoices" => 1,
            // "active" => 1,
            // "pageNo" => $this->page,
            "addedFrom" => $this->getLastUpdateDateDelete("customers"),
        );
        // dd($this->api->client);
        $res = $this->api->sendRequest("getUserOperationsLog", $param);
        //  dd($res);
        if ($res['status']['errorCode'] == 0) {

            if (empty($res['records'])) {
                info("All Customers Operation Log Up-to-date");
                return response()->json(["status" => 200, "message" => "All Product Operation Log Up-to-date"]);
            }

            $this->userOperationInterface->deleteRecords($res['records'], $this->api->client->clientCode);
        }
        info("Customers Operation Log Fetched Successfully.");
        return response()->json(["status" => 200, "message" => "Customers Operation Log Fetched Successfully."]);
    }
}
