<?php
namespace App\components\campaing\parsedata;

use App\Model\AppMessage;
use Illuminate\Support\Arr;

class PromoParse extends BaseParse
{
    public function parseSendData():array
    {
        $this->parseParams['get_message'] = Arr::get($this->parseParams, 'get_promo_message');


        unset($this->parseParams['get_promo_message']);

        return $this->parseSendDataForHub($this->parseParams);
    }

    public function getSendRecipients():array
    {
        return $this->getRecipients(Arr::get($this->parseParams, 'customer', []));
    }

    public function getParamsName(array $dataSend, $typeVariable): array
    {
        $typeValue = '';
        switch ($typeVariable) {
            case AppMessage::getVariable('brandName') :
                $typeValue = Arr::get($dataSend, 'get_message.brand_url');
                break;
            case AppMessage::getVariable('redirectToURL') :
                $typeValue = Arr::get($dataSend, 'get_message.redirect_to');
                $typeVariable = "redirectToURL";
                break;
            case AppMessage::getVariable('firstName') :
                $typeValue = Arr::get($dataSend, 'abandoned_checkout.customer_name');
                break;
        }

        return [$typeValue, $typeVariable];
    }
}