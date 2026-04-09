<?php

namespace App\Http\Controllers\Paei\API\APIServices;

use App\Classes\Except;
use App\Http\Controllers\Services\EAPIService;
use App\Models\Client; 
use App\Models\PAEI\SalesDocument; 
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class SalesDocumentApiService
{

    protected $sales;
    protected $api;
    use ResponseTrait;
    public function __construct(SalesDocument $w, EAPIService $api)
    {
        $this->sales = $w;
        $this->api = $api;
    }

    public function getSalesDocuments($req)
    {
        if ($req->type == 'update') {
            return $this->updateSalesDocumentErrorStatus($req);
        }
        if ($req->type == 'export') {
            return $this->exportCSV($req);
        }

        if (isset($req->direction) == 0) {
            $req->direction = 'desc';
        }
        if (isset($req->sort_by) == 0) {
            $req->sort_by = 'lastModified';
        }
        if (isset($req->strictFilter) == 0) {
            $req->strictFilter = true;
        }

        $pagination = $req->recordsOnPage ? $req->recordsOnPage : 20;
        $customExcept = Except::$except;
        $customExcept[] = "ENTITY";
        $requestData = $req->except($customExcept);

        $erplyClientCodeByEntity = [];
        if ($req->ENTITY != '') {
            $erplyClientCodeByEntity = Client::where("ENTITY", $req->ENTITY)->first();
        }

        $select = $req->select ?
            explode(",", $req->select)
            :
            array(
                'id',
                'salesDocumentID',
                'type',
                'exportInvoiceType',
                'currencyCode',
                'currencyRate',
                'warehouseID',
                'warehouseName',
                'pointOfSaleID',
                'pointOfSaleName',
                'pricelistID',
                'number',
                'date',
                'inventoryTransactionDate',
                'time',
                'clientID',
                'clientName',
                'clientEmail',
                'clientCardNumber',
                'addressID',
                'address',
                'clientFactoringContractNumber',
                'clientPaysViaFactoring',
                'payerID',
                'payerName',
                'payerAddressID',
                'payerAddress',
                'payerFactoringContractNumber',
                'payerPaysViaFactoring',
                'shipToID',
                'shipToName',
                'shipToAddressID',
                'shipToAddress',
                'contactID',
                'contactName',
                'shipToContactID',
                'shipToContactName',
                'employeeID',
                'employeeName',
                'projectID',
                'invoiceState',
                'paymentType',
                'paymentTypeID',
                'paymentDays',
                'paymentStatus',
                'baseDocuments',
                'followUpDocuments',
                'previousReturnsExist',
                'printDiscounts',
                'algorithmVersion',
                'algorithmVersionCalculated',
                'confirmed',
                'notes',
                'internalNotes',
                'netTotal',
                'vatTotal',
                'netTotalsByRate',
                'vatTotalsByRate',
                'netTotalsByTaxRate',
                'vatTotalsByTaxRate',
                'rounding',
                'total',
                'paid',
                'externalNetTotal',
                'externalVatTotal',
                'externalRounding',
                'externalTotal',
                'taxExemptCertificateNumber',
                'otherCommissionReceivers',
                'packerID',
                'referenceNumber',
                'webShopOrderNumbers',
                'trackingNumber',
                'fulfillmentStatus',
                'customReferenceNumber',
                'cost',
                'reserveGoods',
                'reserveGoodsUntilDate',
                'deliveryDate',
                'deliveryTypeID',
                'deliveryTypeName',
                'shippingDate',
                'packingUnitsDescription',
                'penalty',
                'triangularTransaction',
                'purchaseOrderDone',
                'transactionTypeID',
                'transactionTypeName',
                'transportTypeID',
                'transportTypeName',
                'deliveryTerms',
                'deliveryTermsLocation',
                'euInvoiceType',
                'deliveryOnlyWhenAllItemsInStock',
                'eInvoiceBuyerID',
                'workOrderID',
                'lastModified',
                'lastModifierUsername',
                'added',
                'invoiceLink',
                'receiptLink',
                'returnedPayments',
                'amountAddedToStoreCredit',
                'amountPaidWithStoreCredit',
                'applianceID',
                'applianceReference',
                'assignmentID',
                'vehicleMileage',
                'customNumber',
                'advancePayment',
                'advancePaymentPercent',
                'printWithOriginalProductNames',
                'hidePrices',
                'hideAmounts',
                'hideTotal',
                'isFactoringInvoice',
                'taxOfficeID',
                'periodStartDate',
                'periodEndDate',
                'orderArrived',
                'orderInvoiced',
                'ediStatus',
                'ediText',
                'documentURL',
                'hidePaymentDays',
                'creditInvoiceType',
                'issuedCouponIDs',
                'attributes',
                'longAttributes',
                'jdoc',
                'errorFlag',
                'errorMsg',
                'isIgnored',
            );


        // $groups = $this->group->paginate($pagination);
        $salesDocs = $this->sales
            ->select($select)
            ->when($req->ENTITY != '', function ($q) use ($req, $erplyClientCodeByEntity) {
                return $q->where('clientCode', $erplyClientCodeByEntity->clientCode);
            })
            ->with('SalesDetails')
            ->where(function ($q) use ($requestData, $req) {
                // $q->where('clientCode', $this->api->client->clientCode);
                foreach ($requestData as $keys => $value) {
                    if ($value != null) {
                        if ($req->strictFilter == true) {
                            $q->Where($keys, $value);
                        } else {
                            $q->Where($keys, 'LIKE', '%' . $value . '%');
                        }
                    }
                }
            })->orderBy($req->sort_by, $req->direction)->paginate($pagination);

        return response()->json(["status" => 200, "records" => $salesDocs]);
    }

    public function updateSalesDocumentErrorStatus($req)
    {
        if ($req->ids) {
            $bulkOrderIds = explode(",", $req->ids);
            $resync = $req->isReprocessed ?? 0;
            $ignore = $req->isIgnored ?? 0;
            if ($resync == 1) {
                SalesDocument::whereIn('id', $bulkOrderIds)->update(
                    [
                        'errorFlag' => 0
                    ]
                );
            }
            if ($ignore == 1) {
                SalesDocument::whereIn('id', $bulkOrderIds)->update(
                    [
                        'isIgnored' => 1
                    ]
                );
            }
            return $this->successWithMessage('Order updated successfully.');
        }
        return $this->failWithMessage('Invalid Order ID.');
    }

    public function exportCSV()
    {
        $filename = "sales_errors_" . now()->format('YmdHis') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'ID',
                'Client Code',
                'Sales Document ID',
                'Sales Ax ID',
                'Date',
                'Type',
                'Warehouse ID',
                'Warehouse Name',
                'Total',
                'Payment Type',
                'Client ID',
                'Client Name',
                'Ax Pending',
                'Error Flag',
                'Error Message'
            ]);

            // Fetch data
            $salesDocuments = DB::table('newsystem_sales_documents')
                ->select(
                    'id',
                    'clientCode',
                    'salesDocumentID',
                    'salesAxID',
                    'DATE',
                    'type',
                    'warehouseID',
                    'warehouseName',
                    'total',
                    'paymentType',
                    'clientID',
                    'clientName',
                    'axPending',
                    'errorFlag',
                    'errorMsg'
                )
                ->where('errorFlag', 1)
                ->where('erplyDeleted', 0)
                ->whereNotIn('invoiceState', ['pending'])
                ->whereNull('salesAxID')
                ->cursor(); // Efficient for large datasets

            // Write data rows
            foreach ($salesDocuments as $row) {
                fputcsv($handle, (array)$row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
