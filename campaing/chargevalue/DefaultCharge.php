<?php
namespace App\components\campaing\chargevalue;


use App\components\charges\ClientsUsageChargesDataObject;
use App\Model\AppClientsUsageCharges;
use Illuminate\Support\Arr;

class DefaultCharge extends BaseCharge
{
    public function parseSaveData():ClientsUsageChargesDataObject
    {
        $messageId = Arr::get($this->smsData, 'message_id');
        $currentBalance = Arr::get($this->smsData, 'get_message.client_details.balance');
        $campaignId = 0;

        list($price, $creditsAfter) = $this->getCostData($this->smsData, $currentBalance);

        return (new ClientsUsageChargesDataObject(
            $this->getCustomerId(),
            AppClientsUsageCharges::TYPE['CREDIT_SPEND'],
            $messageId,
            $campaignId,
            $currentBalance,
            $creditsAfter,
            $price,
            'Abandoned, win-back, reward spend'
        ));
    }


    public function getCountSmsAndCountryCode($smsData): array
    {
        $countSms = Arr::get($smsData, 'get_promo_message.message_length');
        $countryCode = Arr::get($smsData, 'customer.country_code');

        return [$countSms, $countryCode];
    }

    public function setCustomerId()
    {
        $this->customerId = Arr::get($this->smsData, 'get_message.store_id');
    }
}