<?php
namespace App\components\campaing\chargevalue;

use App\components\charges\ClientsUsageChargesDataObject;
use App\Model\AppClientsUsageCharges;
use Illuminate\Support\Arr;

class PromoCharge extends BaseCharge
{
    public function parseSaveData():ClientsUsageChargesDataObject
    {
        $messageId = Arr::get($this->smsData, 'message_id');
        $currentBalance = Arr::get($this->smsData, 'get_promo_message.client_details.balance');
        $campaignId = Arr::get($this->smsData, 'id');

        list($price, $creditsAfter) = $this->getCostData($this->smsData, $currentBalance);

        return (new ClientsUsageChargesDataObject(
            $this->getCustomerId(),
            AppClientsUsageCharges::TYPE['CREDIT_SPEND'],
            $messageId,
            $campaignId,
            $currentBalance,
            $creditsAfter,
            $price,
            'Promo spend'
        ));
    }


    public function getCountSmsAndCountryCode($smsData): array
    {
        $countSms = Arr::get($smsData, 'get_promo_message.count-sms');
        $countryCode = Arr::get($smsData, 'customer.country_code');

        return [$countSms, $countryCode];
    }

    public function setCustomerId()
    {
        $this->customerId = Arr::get($this->smsData, 'get_promo_message.store_id');;
    }

}