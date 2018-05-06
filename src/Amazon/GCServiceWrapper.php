<?php

/**
 * @author Johnny Chen <chz0321@gmail.com>
 * @desc This class is for making a request to Amazon GiftCode on Demand service API.
 *       amazon agcod official doc: https://s3.amazonaws.com/AGCOD/tech_spec/AGCODTechSpec.pdf
 *
 * sample code:
 *
 * //replace these configurations with your own settings in the \Amazon\Config\Account.php file
 * $__partnerId = 'YourCompanyID';
 * $__accessKey = 'findfromYourAwsAccountManagementPage';
 * $__secretKey = 'findYourAwsAccountManagementPage';
 *
 *
 * $gcSerivce = new \Amazon\GCServiceWrapper(\Amazon\Config\Region::US);
 * $giftcard = $gcService->createGiftCode(5); //request for a USD$5 giftcard code
 *
 * $gcService->cancelGiftCode($giftcard['gcId']); //cancel the code by Code ID
 */
namespace Amazon;

class GCServiceWrapper
{
    private $__awsService;

    public function __construct($region, $useSandbox = FALSE) {
        $serviceConfig = Config\Region::getServiceConf($region, $useSandbox);

        $this->__awsService = new AwsService($serviceConfig['regionCode'], $serviceConfig['host'], $serviceConfig['endpoint'], $serviceConfig['currencyCode']);
    }

    /**
     * @param $gcAmount
     * @return mixed
     */
    public function createGiftCode($gcAmount) {
        $op                       = 'CreateGiftCard';
        $currentTimestamp         = time();
        $iso8601FormattedDateTime = $this->__getIso8601TimeFormat($currentTimestamp);

        $data                      = [];
        $data['creationRequestId'] = $this->__generateRequestId();
        $data['partnerId']         = Config\Account::getPartnerId();
        $data['value']             = ['currencyCode' => $this->__awsService->getCurrency(), 'amount' => $gcAmount];

        $payload                = json_encode($data); // step1. gen json "PAYLOAD"
        $hashedPayload          = $this->__awsService->hashPayload($payload); // step2. hash $payload
        $hashedCanonicalRequest = $this->__awsService->hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime);
        $signature              = $this->__awsService->generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime);

        return json_decode($this->__awsService->sendRequest($payload, $signature, $op, $iso8601FormattedDateTime), TRUE);
    }

    public function cancelGiftCode($codeId) {
        $op                       = 'CancelGiftCard';
        $currentTimestamp         = time();
        $iso8601FormattedDateTime = $this->__getIso8601TimeFormat($currentTimestamp);

        $data                      = [];
        $data['creationRequestId'] = $this->__generateRequestId();
        $data['partnerId']         = Config\Account::getPartnerId();
        $data['gcId']              = $codeId;

        $payload                = json_encode($data);
        $hashedPayload          = $this->__awsService->hashPayload($payload);
        $hashedCanonicalRequest = $this->__awsService->hashCanonicalRequest($hashedPayload, $op, $iso8601FormattedDateTime);
        $signature              = $this->__awsService->generateSignature($hashedCanonicalRequest, $iso8601FormattedDateTime);

        return json_decode($this->__awsService->sendRequest($payload, $signature, $op, $iso8601FormattedDateTime), TRUE);
    }

    private function __generateRequestId() {
        return sprintf("%012s", Config\Account::getPartnerId() . substr(microtime(TRUE) * 10000, -7));
    }

    private function __getIso8601TimeFormat($timestamp) {
        return date('Ymd\THis\Z', $timestamp - date('Z', $timestamp));
    }
}