<?php
namespace App\Http\Controllers\Paei\Services;

use App\Classes\UserLogger;
use App\Contracts\UserOperationInterface;
use App\Http\Controllers\Services\EAPIService;
use App\Models\PAEI\Customer;
use App\Models\PAEI\ErplySync;
use App\Traits\UserOperationTrait;

class GetCustomerService implements UserOperationInterface{

    protected $customer;
    protected $letsLog;
    protected $api;
    use UserOperationTrait;

    public function __construct(Customer $c,UserLogger $logger, EAPIService $api){
        $this->customer = $c;
        $this->letsLog = $logger;
        $this->api = $api;
    }

    public function saveUpdate($customers){

        foreach($customers as $c){
            $this->saveUpdateCustomerOldApi($c);
        }

        if($this->api->client->ENTITY == "PSW"){
            $pp = collect($customers);
            // dd($pp);
            $forUpdate = $pp->last();
             
            ErplySync::where("id", 1)->update(["psw_customer_added" => date('Y-m-d H:i:s', $forUpdate['added'])]);
            
        }

        return response()->json(['status'=>200, 'message'=>"Customer fetched Successfully."]);
    }

    public function saveUpdateCustomer($product, $isIndividual = false, $clientCode = 0){

        $old = $this->customer->where('clientCode',$isIndividual == false ? $this->api->client->clientCode : $clientCode)->where('customerID', $product['id'])->first();
        $change = $this->customer->updateOrCreate(
                [
                    "clientCode" => $isIndividual == false ? $this->api->client->clientCode : $clientCode,
                    "customerID"  =>  $product['id']
                ],
                [
                    "clientCode" => $isIndividual == false ? $this->api->client->clientCode : $clientCode,
                    "customerID" => $product['id'],
                    "customerType" => $product['customerType'],
                    "fullName" => trim($product['fullName']),
                    "companyName" => @$product['companyName'],
                    "companyTypeID"  => @$product['companyTypeID'] == '' ? 0 : $product['companyTypeID'],
                    "firstName"  => trim($product['firstName']),
                    "lastName"  => trim(@$product['lastName']),
                    "personTitleID"  => @$product['titleId'] == '' ? 0 : $product['titleId'],
                    "gender"  => @$product['gender'],
                    "groupID"  =>  @$product['customerGroupId'] == '' ? 0 : $product['customerGroupId'],
                    "countryID"  =>  @$product['countryId'] == '' ? 0 : $product['countryId'],
                    "groupName"  =>  @$product['groupName'],
                    "payerID"  =>  @$product['payerId'] == '' ? 0 : $product['payerId'],
                    "phone"  => $product['phone'],
                    "mobile"  => $product['mobile'],
                    "email"  => trim(@$product['mail']),
                    "fax"  => @$product['fax'],
                    "code"  => @$product['code'],
                    "birthday"  => @$product['birthDate']  == '' ? '0000-00-00' : @$product['birthDate'],
                    "integrationCode"  => @$product['integrationCode'],
                    "flagStatus"  => @$product['flagStatus'],
                    "doNotSell"  => @$product['doNotSell'],
                    "colorStatus"  => @$product['colorStatus'],
                    "image"  => @$product['image'],
                    "taxExempt"  => @$product['taxExempt'],
                    "partialTaxExemption"  => @$product['partialTaxExemption'],
                    "factoringContractNumber"  => @$product['factoringContractNumber'],
                    "paysViaFactoring"  => @$product['paysViaFactoring'],
                    "rewardPoints"  => @$product['rewardPoints'] == '' ? 0 : $product['rewardPoints'],
                    "twitterID"  => @$product['twitterId'],
                    "facebookName"  => @$product['facebookName'],
                    "creditCardLastNumbers"  => @$product['creditCardLastNumbers'],
                    "isPOSDefaultCustomer"  => @$product['isPOSDefaultCustomer'],
                    "euCustomerType"  => @$product['type'],
                    "credit"  => @$product['credit'] == '' ? 0 : $product['credit'],
                    "salesBlocked"  => @$product['salesBlocked'],
                    "referenceNumber"  => @$product['referenceNumber'],
                    "customerCardNumber"  => @$product['customerCardNumber'],//today date time
                    "rewardPointsDisabled"  => @$product['rewardPointsDisabled'],
                    "customerBalanceDisabled"  => @$product['customerBalanceDisabled'],
                    "posCouponsDisabled"  => @$product['posCouponsDisabled'],// today date
                    "emailOptOut"  => @$product['emailOptOut'],
                    "lastModifierUsername"  => @$product['changedBy'],//@$product['lastModifierUsername'],
                    "shipGoodsWithWaybills"  => @$product['shipGoodsWithWaybills'],
                    "addresses"  => !empty($product['addresses']) ? json_encode($product['addresses'], true) : '',
                    "contactPersons"  => !empty($product['contactPersons']) ? json_encode($product['contactPersons'], true) : '',
                    "defaultAssociationID"  => @$product['defaultAssociationID'] == '' ? 0 : $product['defaultAssociationID'],
                    "defaultAssociationName"  => @$product['defaultAssociationName'],
                    "defaultProfessionalID"  => @$product['defaultProfessionalID'] == '' ? 0 : $product['defaultProfessionalID'],
                    "defaultProfessionalName"  => @$product['defaultProfessionalName'],
                    "associations"  => !empty($product['associations']) ? json_encode($product['associations'], true) : '',
                    "professionals"  => !empty($product['professionals']) ? json_encode($product['professionals'], true) : '',
                    "attributes"  => !empty($product['attributes']) ? json_encode($product['attributes'], true) : '',
                    "longAttributes"  => !empty($product['longAttributes']) ? json_encode($product['longAttributes'], true) : '',
                    "externalIDs"  => !empty($product['externalIDs']) ? json_encode($product['externalIDs'], true) : '',
                    "actualBalance"  => @$product['actualBalance'] == '' ? 0 : $product['actualBalance'],
                    "creditLimit"  => @$product['creditLimit'] == '' ? 0 : $product['creditLimit'],
                    "availableCredit"  => @$product['availableCredit'] == '' ? 0 : $product['availableCredit'],
                    "creditAllowed"  => @$product['creditAllowed'],
                    "vatNumber"  => @$product['vatNumber'],
                    "skype"  => @$product['skype'],
                    "website"  => @$product['website'],
                    "webshopUsername"  => @$product['webShopUsername'],
                    "webshopLastLogin"  => @$product['webshopLastLogin'],
                    "bankName"  => @$product['bankName'],
                    "bankAccountNumber"  => @$product['bankAccountNumber'],
                    "bankIBAN"  => @$product['bankIban'],
                    "bankSWIFT"  => @$product['bankSwiftCode'],
                    "jobTitleID"  => @$product['jobTitleId'] == '' ? 0 : $product['jobTitleId'],
                    "jobTitleName"  => @$product['jobTitleName'],
                    "companyID"  => @$product['companyID'] == '' ? 0 : $product['companyID'],
                    "employerName"  => @$product['employerName'],
                    "customerManagerID"  => @$product['customerManagerId'] == '' ? 0 : $product['customerManagerId'],
                    "customerManagerName"  => @$product['customerManagerName'],
                    "paymentDays"  => @$product['paymentDays'] == '' ? 0 : $product['paymentDays'],
                    "penaltyPerDay"  => @$product['penaltyForOverdue'],
                    "priceListID"  => @$product['priceListId'] == '' ? 0 : $product['priceListId'],
                    "priceListID2"  => @$product['priceListId2'] == '' ? 0 : $product['priceListId2'],
                    "priceListID3"  => @$product['priceListId3'] == '' ? 0 : $product['priceListId3'],
                    "priceListID4"  => @$product['priceListID4'] == '' ? 0 : $product['priceListID4'],
                    "priceListID5"  => @$product['priceListID5'] == '' ? 0 : $product['priceListID5'],
                    "outsideEU"  => @$product['outsideEU'] == 1 ? 1 : 0,
                    "businessAreaID"  => @$product['businessAreaId'] == '' ? 0 : $product['businessAreaId'],
                    "businessAreaName"  => @$product['businessAreaName'],
                    "deliveryTypeID"  => @$product['deliveryTypeId'] == '' ? 0 : $product['deliveryTypeId'],
                    "signUpStoreID"  => @$product['signUpStoreId'] == '' ? 0 : $product['signUpStoreId'],
                    "homeStoreID"  => @$product['homeStoreId'] == '' ? 0 : $product['homeStoreId'],
                    "taxOfficeID"  => @$product['taxOfficeId'] == '' ? 0 : $product['taxOfficeId'],
                    "notes"  => @$product['notes'],
                    "lastModified"  => isset($product['changed']) == 1 ? date('Y-m-d H:i:s',$product['changed']) : '0000-00-00 00:00',
                    "lastModifierEmployeeID"  => @$product['lastModifierEmployeeID'] == '' ? 0 : $product['lastModifierEmployeeID'],
                    "added"  => isset($product['added']) == 1 ? date('Y-m-d H:i:s',$product['added']) : '0000-00-00 00:00',
                    "emailEnabled"  => @$product['invoicesViaEmailEnabled'],
                    "eInvoiceEnabled"  => @$product['eInvoicesViaEmailEnabled'],
                    "docuraEDIEnabled"  => @$product['docuraEDIEnabled'],
                    "eInvoiceEmail"  => @$product['eInvoiceEmail'],
                    "eInvoiceReference"  => @$product['eInvoiceReference'],
                    "mailEnabled"  => @$product['mailEnabled'],
                    "operatorIdentifier"  => @$product['operatorId'],
                    "EDI"  => @$product['ediCode'],
                    "PeppolID"  => @$product['PeppolID'],
                    "GLN"  => @$product['GLN'],
                    "ediType"  => @$product['ediType'],
                    "address"  => @$product['address'],
                    "street"  => @$product['street'],
                    "address2"  => @$product['address2'],
                    "city"  => @$product['city'],
                    "postalCode"  => @$product['postalCode'],
                    "state"  => @$product['state'],
                    "country"  => @$product['country'],
                    "addressTypeID"  => @$product['addressTypeID'] == '' ? 0 : $product['addressTypeID'],
                    "addressTypeName"  => @$product['addressTypeName'],
                    "axPending" => 1

                ]
            );
        $this->letsLog->setChronLog($old ? json_encode($old, true) : '', json_encode($change, true), $old  ? "Customer Updated" : "Customer Created");        
    }

    public function saveUpdateCustomerOldApi($product, $isIndividual = false, $clientCode = 0){
        $old = $this->customer->where('clientCode',  $isIndividual == false ? $this->api->client->clientCode : $clientCode)->where('customerID', $product['customerID'])->first();
        $change = $this->customer->updateOrCreate(
                [
                    "clientCode" => $isIndividual == false ? $this->api->client->clientCode : $clientCode,
                    "customerID"  =>  $product['customerID']
                ],
                [
                    "clientCode" => $isIndividual == false ? $this->api->client->clientCode : $clientCode,
                    "customerID" => $product['customerID'],
                    "customerType" => $product['customerType'],
                    "fullName" => trim($product['fullName']),
                    "companyName" => @$product['companyName'],
                    "companyTypeID"  => @$product['companyTypeID'] == '' ? 0 : $product['companyTypeID'],
                    "firstName"  => trim($product['firstName']),
                    "lastName"  => trim(@$product['lastName']),
                    "personTitleID"  => @$product['personTitleID'] == '' ? 0 : $product['personTitleID'],
                    "gender"  => @$product['gender'],
                    "groupID"  =>  @$product['groupID'] == '' ? 0 : $product['groupID'],
                    "countryID"  =>  @$product['countryID'] == '' ? 0 : $product['countryID'],
                    "groupName"  =>  @$product['groupName'],
                    "payerID"  =>  @$product['payerID'] == '' ? 0 : $product['payerID'],
                    "phone"  => $product['phone'],
                    "mobile"  => $product['mobile'],
                    "email"  => trim(@$product['email']),
                    "fax"  => @$product['fax'],
                    "code"  => @$product['code'],
                    "birthday"  => @$product['birthday']  == '' ? '0000-00-00' : @$product['birthday'],
                    "integrationCode"  => @$product['integrationCode'],
                    "flagStatus"  => @$product['flagStatus'],
                    "doNotSell"  => @$product['doNotSell'],
                    "colorStatus"  => @$product['colorStatus'],
                    "image"  => @$product['image'],
                    "taxExempt"  => @$product['taxExempt'],
                    "partialTaxExemption"  => @$product['partialTaxExemption'],
                    "factoringContractNumber"  => @$product['factoringContractNumber'],
                    "paysViaFactoring"  => @$product['paysViaFactoring'],
                    "rewardPoints"  => @$product['rewardPoints'] == '' ? 0 : $product['rewardPoints'],
                    "twitterID"  => @$product['twitterId'],
                    "facebookName"  => @$product['facebookName'],
                    "creditCardLastNumbers"  => @$product['creditCardLastNumbers'],
                    "isPOSDefaultCustomer"  => @$product['isPOSDefaultCustomer'],
                    "euCustomerType"  => @$product['euCustomerType'],
                    "credit"  => @$product['credit'] == '' ? 0 : $product['credit'],
                    "salesBlocked"  => @$product['salesBlocked'],
                    "referenceNumber"  => @$product['referenceNumber'],
                    "customerCardNumber"  => @$product['customerCardNumber'],//today date time
                    "rewardPointsDisabled"  => @$product['rewardPointsDisabled'],
                    "customerBalanceDisabled"  => @$product['customerBalanceDisabled'],
                    "posCouponsDisabled"  => @$product['posCouponsDisabled'],// today date
                    "emailOptOut"  => @$product['emailOptOut'],
                    "lastModifierUsername"  =>@$product['lastModifierUsername'],
                    "shipGoodsWithWaybills"  => @$product['shipGoodsWithWaybills'],
                    "addresses"  => !empty($product['addresses']) ? json_encode($product['addresses'], true) : '',
                    "contactPersons"  => !empty($product['contactPersons']) ? json_encode($product['contactPersons'], true) : '',
                    "defaultAssociationID"  => @$product['defaultAssociationID'] == '' ? 0 : $product['defaultAssociationID'],
                    "defaultAssociationName"  => @$product['defaultAssociationName'],
                    "defaultProfessionalID"  => @$product['defaultProfessionalID'] == '' ? 0 : $product['defaultProfessionalID'],
                    "defaultProfessionalName"  => @$product['defaultProfessionalName'],
                    "associations"  => !empty($product['associations']) ? json_encode($product['associations'], true) : '',
                    "professionals"  => !empty($product['professionals']) ? json_encode($product['professionals'], true) : '',
                    "attributes"  => !empty($product['attributes']) ? json_encode($product['attributes'], true) : '',
                    "longAttributes"  => !empty($product['longAttributes']) ? json_encode($product['longAttributes'], true) : '',
                    "externalIDs"  => !empty($product['externalIDs']) ? json_encode($product['externalIDs'], true) : '',
                    "actualBalance"  => @$product['actualBalance'] == '' ? 0 : $product['actualBalance'],
                    "creditLimit"  => @$product['creditLimit'] == '' ? 0 : $product['creditLimit'],
                    "availableCredit"  => @$product['availableCredit'] == '' ? 0 : $product['availableCredit'],
                    "creditAllowed"  => @$product['creditAllowed'],
                    "vatNumber"  => @$product['vatNumber'],
                    "skype"  => @$product['skype'],
                    "website"  => @$product['website'],
                    "webshopUsername"  => @$product['webShopUsername'],
                    "webshopLastLogin"  => @$product['webshopLastLogin'],
                    "bankName"  => @$product['bankName'],
                    "bankAccountNumber"  => @$product['bankAccountNumber'],
                    "bankIBAN"  => @$product['bankIBAN'],
                    "bankSWIFT"  => @$product['bankSWIFT'],
                    "jobTitleID"  => @$product['jobTitleID'] == '' ? 0 : $product['jobTitleID'],
                    "jobTitleName"  => @$product['jobTitleName'],
                    "companyID"  => @$product['companyID'] == '' ? 0 : $product['companyID'],
                    "employerName"  => @$product['employerName'],
                    "customerManagerID"  => @$product['customerManagerID'] == '' ? 0 : $product['customerManagerID'],
                    "customerManagerName"  => @$product['customerManagerName'],
                    "paymentDays"  => @$product['paymentDays'] == '' ? 0 : $product['paymentDays'],
                    "penaltyPerDay"  => @$product['penaltyPerDay'],
                    "priceListID"  => @$product['priceListID'] == '' ? 0 : $product['priceListID'],
                    "priceListID2"  => @$product['priceListID2'] == '' ? 0 : $product['priceListID2'],
                    "priceListID3"  => @$product['priceListID3'] == '' ? 0 : $product['priceListID3'],
                    "priceListID4"  => @$product['priceListID4'] == '' ? 0 : $product['priceListID4'],
                    "priceListID5"  => @$product['priceListID5'] == '' ? 0 : $product['priceListID5'],
                    "outsideEU"  => @$product['outsideEU'] == 1 ? 1 : 0,
                    "businessAreaID"  => @$product['businessAreaID'] == '' ? 0 : $product['businessAreaID'],
                    "businessAreaName"  => @$product['businessAreaName'],
                    "deliveryTypeID"  => @$product['deliveryTypeID'] == '' ? 0 : $product['deliveryTypeID'],
                    "signUpStoreID"  => @$product['signUpStoreID'] == '' ? 0 : $product['signUpStoreID'],
                    "homeStoreID"  => @$product['homeStoreID'] == '' ? 0 : $product['homeStoreID'],
                    "taxOfficeID"  => @$product['taxOfficeID'] == '' ? 0 : $product['taxOfficeID'],
                    "notes"  => @$product['notes'],
                    "lastModified"  => isset($product['lastModified']) == 1 ? date('Y-m-d H:i:s',$product['lastModified']) : '0000-00-00 00:00',
                    "lastModifierEmployeeID"  => @$product['lastModifierEmployeeID'] == '' ? 0 : $product['lastModifierEmployeeID'],
                    "added"  => date('Y-m-d H:i:s',$product['added']),
                    "emailEnabled"  => @$product['emailEnabled'],
                    "eInvoiceEnabled"  => @$product['eInvoiceEnabled'],
                    "docuraEDIEnabled"  => @$product['docuraEDIEnabled'],
                    "eInvoiceEmail"  => @$product['eInvoiceEmail'],
                    "eInvoiceReference"  => @$product['eInvoiceReference'],
                    "mailEnabled"  => @$product['mailEnabled'],
                    "operatorIdentifier"  => @$product['operatorIdentifier'],
                    "EDI"  => @$product['EDI'],
                    "PeppolID"  => @$product['PeppolID'],
                    "GLN"  => @$product['GLN'],
                    "ediType"  => @$product['ediType'],
                    "address"  => @$product['address'],
                    "street"  => @$product['street'],
                    "address2"  => @$product['address2'],
                    "city"  => @$product['city'],
                    "postalCode"  => @$product['postalCode'],
                    "state"  => @$product['state'],
                    "country"  => @$product['country'],
                    "addressTypeID"  => @$product['addressTypeID'] == '' ? 0 : $product['addressTypeID'],
                    "addressTypeName"  => @$product['addressTypeName'],
                    "axPending" => 1

                ]
            );
        $this->letsLog->setChronLog($old ? json_encode($old, true) : '', json_encode($change, true), $old  ? "Customer Updated" : "Customer Created");        
    }


    public function getLastUpdateDate($isAdded){

        if($this->api->client->ENTITY == "PSW"){
            $latest = ErplySync::where("id", 1)->first()->psw_customer_added;
            if($latest == "0000-00-00 00:00:00.000"){
                return 0;
            }
            return strtotime($latest);
        }

        // echo "im call";
        $latest = $this->customer->where("clientCode", $this->api->client->clientCode)->orderBy('lastModified', 'desc')->first();
        if($latest){
            return strtotime($latest->lastModified);
        }
        return 0;// strtotime($latest);
    }


    //for customer operation logs
    public function deleteRecords($res, $clientCode){

    //    dd("Hello im from get product service class");
        foreach($res as $l){
            $this->handleOperationLog($l,$clientCode,  $l['itemID']);
            if($l['operation'] == 'delete'){
                Customer::deleteRecords($clientCode,$l["itemID"]);
                // MatrixProduct::deleteProduct($clientCode,$l["itemID"]);
            }
        }
    }

    
}
