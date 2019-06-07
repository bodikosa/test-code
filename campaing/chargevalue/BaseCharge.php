<?php

namespace App\components\campaing\chargevalue;

use App\components\charges\ClientsUsageCharges;
use App\Model\AppClientData;
use App\Model\AppMessageCategory;
use App\Model\AppSmsPrice;
use League\ISO3166\Exception\DomainException;

abstract class BaseCharge
{
    public $smsData;
    public $customerId;

    const MIN_CHARGE_PAYMENT = 0.5;

    public function __construct($smsData)
    {
        $this->smsData = $smsData;
        $this->setCustomerId();
    }

    public static function init(array $params)
    {
        if (AppMessageCategory::isPromo($params['category_id'])) {
            return (new PromoCharge($params));
        } elseif (AppMessageCategory::isAbandoned($params['category_id'])){
            return (new AbandonedCharge($params));
        } else {
            return (new DefaultCharge($params));
        }
    }

    abstract function parseSaveData();

    abstract function setCustomerId();

    public function saveParseData()
    {
        $saveData = $this->parseSaveData();

        ClientsUsageCharges::create($saveData);
        $this->updateUserBalance($saveData);
    }

    protected function getCostData($smsData, $currentBalance): array
    {
        $smsCost = $this->getSmsCost($smsData);

        if ($smsCost > $currentBalance) {
            throw new DomainException("Balance is too low! Current balance is  {$currentBalance}. Cost sms is {$smsCost}");
        }

        $creditsAfter = $currentBalance - $smsCost;

        return [$smsCost, $creditsAfter];
    }

    protected function getSmsCost($smsData)
    {
        list($countSms, $countryCode) = $this->getCountSmsAndCountryCode($smsData);

        $smsPrice = AppSmsPrice::where('country_code', $countryCode)->first();

        if (!$smsPrice) {
            throw new DomainException('Not found price for country '. $countryCode . '. ' . json_encode($smsData));
        }

        return $smsPrice->price * $countSms;
    }

    private function updateUserBalance($saveData): void
    {
        AppClientData::where('id', $saveData->parent)->update(['balance' => $saveData->credits_after]);
    }

    public function getCustomerId()
    {
        return $this->customerId;
    }
}