<?php
namespace App\components\campaing\parsedata;

use App\Model\AppMessage;
use Illuminate\Support\Arr;

class DefaultParse extends BaseParse
{
    public function parseSendData():array
    {
        return $this->parseSendDataForHub($this->parseParams);
    }

    public function getSendRecipients():array
    {
        $userData = Arr::get($this->parseParams, 'customer', []) ?:[];
        return $this->getRecipients($userData);
    }

    public function getParseDataForLogTable(): array
    {
        $parseData = parent::getParseDataForLogTable();
        $parseData['parent'] = Arr::get($this->parseParams, 'get_message.store_id');
        $parseData['sms_id'] = Arr::get($this->parseParams, 'get_message.id');
        $parseData['campaign_id'] = 0;

        return $parseData;
    }

    public function getParamsName(array $dataSend, $typeVariable): array
    {
          if($typeVariable == AppMessage::getVariable('firstName')){
             $firstName = Arr::get($this->parseParams, 'customer.first_name');
             return [$firstName, $typeVariable];
          }

        return parent::getParamsName($dataSend, $typeVariable);
    }

}