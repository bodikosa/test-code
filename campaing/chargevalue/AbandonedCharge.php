<?php
namespace App\components\campaing\chargevalue;


use App\components\charges\ClientsUsageChargesDataObject;
use App\Helpers\CountryCode;
use App\Model\AppClientsUsageCharges;
use Illuminate\Support\Arr;
use League\ISO3166\Exception\DomainException;

class AbandonedCharge extends DefaultCharge
{
    public function getCountSmsAndCountryCode($smsData): array
    {
        $countSms = Arr::get($smsData, 'get_message.message_length');
        $userPhone = Arr::get($smsData, 'abandoned_checkout.phone');
        $countryCode = CountryCode::getByPhone($userPhone);

        if (!$countryCode) {
            throw new DomainException('NotFound country code for phone' . $userPhone);
        }

        $countryCode = $countryCode['country_code'];

        return [$countSms, $countryCode];
    }
}