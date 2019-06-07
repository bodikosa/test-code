<?php

namespace App\components\campaing\parsedata;

use Illuminate\Support\Arr;

class AbandonedParse extends BaseParse
{
    public function parseSendData(): array
    {
        return $this->parseSendDataForHub($this->parseParams);
    }

    public function getSendRecipients(): array
    {
        $userData = Arr::get($this->parseParams, 'abandoned_checkout', []) ?: [];
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
}