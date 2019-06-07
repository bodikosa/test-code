<?php
namespace App\components\campaing\parsedata;

use App\Model\AppMessage;
use App\Model\AppMessageCategory;
use Illuminate\Support\Arr;

abstract class BaseParse
{
    public $parseParams;

    public function __construct($parseParams)
    {
        $this->parseParams = $parseParams;
    }

    public static function init(array $params)
    {
        if (AppMessageCategory::isPromo($params['category_id'])) {
            return (new PromoParse($params));
        } elseif (AppMessageCategory::isAbandoned($params['category_id'])){
            return (new AbandonedParse($params));
        } else {
            return (new DefaultParse($params));
        }
    }

    abstract function parseSendData();
    abstract function getSendRecipients();

    public function getRecipients(array $orderData = []): array
    {
        return [[
            "phone" => Arr::get($orderData, 'phone'),
//            "code" => Arr::get($orderData, 'customer.country_code'),
        ]];
    }

    private function getSmsVariable(array $dataSend = []): array
    {
        $listVariable = [];
        $stringMessage = Arr::get($dataSend, 'get_message.message');
        foreach (AppMessage::LIST_VARIABLE as $typeVariable) {
            if (strpos($stringMessage, $typeVariable) !== false) {
                list($typeValue, $typeVariable) = $this->getParamsName($dataSend, $typeVariable);

                $listVariable[$typeVariable] = $typeValue;
            }
        }

        return $listVariable;
    }

    public function parseSendDataForHub($data): array
    {
        return [
            "sender" => Arr::get($data, 'get_message.from'),
            "message" => Arr::get($data, 'get_message.message'),
            "sms_variables" => $this->getSmsVariable($data),
            "info" => [
                'client_id' => Arr::get($data, 'get_message.store_id')
            ]
        ];
    }

    public function getParseData():array
    {
        $parseData = $this->parseSendData();
        $parseData["recipients"] = $this->getSendRecipients();

        return $parseData;
    }


    public function getParamsName(array $dataSend, $typeVariable): array
    {
        $typeValue = '';
        switch ($typeVariable) {
            case AppMessage::getVariable('brandName') :
                $typeValue = Arr::get($dataSend, 'get_message.brand_name');
                break;
            case AppMessage::getVariable('redirectToURL') :
                $dataLink = Arr::get($dataSend, 'get_message.client_details.information.secure_url', '');
                $typeValue = $dataLink ? $dataLink.'/cart.php' : '';
                $typeVariable = "redirectToURL";
                break;
            case AppMessage::getVariable('firstName') :
                $typeValue = Arr::get($dataSend, 'abandoned_checkout.customer_name');
                break;
        }

        return [$typeValue, $typeVariable];
    }

    public function setParseData(array $parseParams, $hashValue):array
    {
        if(isset($parseParams['sms_variables']['redirectToURL']))
        {
            $parseParams['sms_variables']['redirectToURL'] .= "?hash={$hashValue}";
        }

        return $parseParams;
    }

    public function getParseDataForLogTable(): array
    {
        $dataParse = [
            'parent' => Arr::get($this->parseParams, 'get_promo_message.store_id'),
            'sms_id' => Arr::get($this->parseParams, 'get_promo_message.type_id'),
            'campaign_id' => Arr::get($this->parseParams, 'get_promo_message.id'),
            'send' => now(),
            'log_id' => Arr::get($this->parseParams, 'id'),
        ];

        $dataParse['log_hash'] = $this->getHash($dataParse);

        return $dataParse;
    }

    public function getHash(array $logData):string
    {
        return md5(serialize(['client_id' => $logData['parent'],
            'message_type_id' => $logData['sms_id'],
            'send' => $logData['send'],
            'campaign_id' => $logData['campaign_id'],
            'log_id' => $logData['log_id']]));
    }
}