<?php
/**
 * Created by PhpStorm.
 * User: mrbsk
 * Date: 02.05.19
 * Time: 12:03
 */

namespace App\components\campaing;

use App\components\campaing\chargevalue\BaseCharge;
use App\Helpers\ErrorLog;
use App\Model\{AppAbandonedCheckouts,
    AppClientData,
    AppClientSmsLog,
    AppMessage,
    AppMessageCategory,
    AppSmsPrice,
    BaseClientLog};
use App\components\cache\StatisticCache\sms\{ SmsStatistic, SmsStatisticParams };
use App\components\webhook\{AbandonedWebhook, LogDataObject};
use App\components\campaing\parsedata\BaseParse;
use App\components\smsgoals\SmsGoals;
use App\components\FpHub;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use League\ISO3166\Exception\DomainException;
use Stripe\Charge;
use Stripe\Stripe;

class CampaignMessage
{
    /**
     * @param AppAbandonedCheckouts $abandonedModel
     */
    public function create(AppAbandonedCheckouts $abandonedModel): void
    {
        $abandonedCheckouts = $abandonedModel::with(['shop' => function ($query) use ($abandonedModel) {
            $query->with(['clientMessage' => function ($query) use ($abandonedModel) {
                if ($abandonedModel->phone && $abandonedModel->email) {
                    $query->whereIn('category_id', [
                        AppMessageCategory::CATEGORIES['SMS_CATEGORY_ABANDONED_CART'],
                        AppMessageCategory::CATEGORIES['EMAIL_CATEGORY_ABANDONED_CART'],
                    ]);
                } elseif ($abandonedModel->phone) {
                    $query->where('category_id', AppMessageCategory::CATEGORIES['SMS_CATEGORY_ABANDONED_CART']);
                } elseif ($abandonedModel->email) {
                    $query->where('category_id', AppMessageCategory::CATEGORIES['EMAIL_CATEGORY_ABANDONED_CART']);
                }

                $query->where('status', AppMessage::STATUSES['ACTIVE']);
            }]);
        }])
            ->whereId($abandonedModel->id)
            ->first()
            ->toArray();

        $abandonedLists = Arr::get($abandonedCheckouts, 'shop.client_message');
        $abandonedCheckoutId = Arr::get($abandonedCheckouts, 'id');

        $this->addNotificationLists($abandonedCheckoutId, $abandonedLists);
    }

    /**
     * @param int $idOrder
     * @param array $campaignList
     */
    public function addNotificationLists(int $idOrder, array $campaignList): void
    {
        foreach ($campaignList as $abandonedList) {
            $abandonedDataObj = new LogDataObject();
            $webhookObj = AbandonedWebhook::init();

            $abandonedDataObj->setAbandonedId($idOrder);
            $abandonedDataObj->setMessageId($abandonedList['id']);
            $abandonedDataObj->setSendData(Arr::get($abandonedList, 'delay'));

            switch (Arr::get($abandonedList, 'category_id')) {
                case AppMessageCategory::CATEGORIES['SMS_CATEGORY_ABANDONED_CART']:
                    $webhookObj->saveSmsLog($abandonedDataObj);
                    break;
                case AppMessageCategory::CATEGORIES['EMAIL_CATEGORY_ABANDONED_CART']:
                    $webhookObj->saveEmailLog($abandonedDataObj);
                    break;
            }
        }
    }

    public function sendAbandonedNotification(): void
    {
        $abandonedNotification = $this->getAbandonedNotification();
        $abandonedData = $abandonedNotification->get()->toArray();

        $denySendSmsFor = $this->getDenyCustomerForSendMessages($abandonedData);

        $sendMessageIds = [];
        $cancelMessageIds = [];
        foreach ($abandonedData as $data) {

            $isSendPayment = $this->saveOrderPayment($data, $denySendSmsFor);
            if($isSendPayment)
            {
                $this->sendMessageToGoalsAndFbHub($data);
                $this->saveToStatisticData($data);
                $sendMessageIds[] = $data['id'];
            } else {
                $cancelMessageIds[] = $data['id'];
            }

        }

        $this->updateStatusMessages($sendMessageIds, AppClientSmsLog::STATUSES['SEND']);
        $this->updateStatusMessages($cancelMessageIds, AppClientSmsLog::STATUSES['CANCELED']);
    }

    private function getAbandonedNotification()
    {
        return AppClientSmsLog::where(function ($query)
            {
                $query->where(function ($query){
                    $query->where('send_data', '>=', now())
                          ->where('send_data', '<=', Carbon::now()->addMinutes())
                          ->where('status', '=', AppClientSmsLog::STATUSES['AWAITING']);
                })->orWhere(function ($query){
                    $query->where('status', '=', AppClientSmsLog::STATUSES['CANCELED'])
                          ->where('repeat_count_send', '<=', BaseClientLog::LIMIT_REPEAT_COUNT);
                });
            })
            ->with('abandonedCheckout')
            ->with('customer')
            ->with([
                'getMessage' => function ($queryMes) {
                    $queryMes->with([
                        'clientDetails' => function ($queryShop) {
                            $queryShop->with('information');
                        }
                    ]);
                }
            ])
            ->with([
                'getPromoMessage' => function ($queryMes) {
                    $queryMes->with([
                        'clientDetails' => function ($queryShop) {
                            $queryShop->with('information');
                        }
                    ]);
                }
            ]);
    }

    private function updateStatusMessages(array $updateStatusForIds = [], $sendStatus): void
    {
        AppClientSmsLog::whereIn('id', $updateStatusForIds)
            ->update([
                'repeat_count_send'=> DB::raw('repeat_count_send+1'),
                'status' => $sendStatus
            ]);
    }

    private function sendMessageToGoalsAndFbHub($data): void
    {
        $parseDataClass = BaseParse::init($data);
        $logSmsData = $parseDataClass->getParseDataForLogTable();

        (new SmsGoals())->saveItem($logSmsData);

        $parseData = $parseDataClass->setParseData($parseDataClass->getParseData(), $logSmsData['log_hash']);

        (new FpHub())->sendMessage($parseData);
    }

    private function saveToStatisticData($data): void
    {
        $statisticParams = new SmsStatisticParams();
        $statisticParams->sent = 1;
        SmsStatistic::makeInstance(BaseCharge::init($data)->getCustomerId(), $statisticParams)->save();
    }

    private function saveOrderPayment($data, $denySendSmsFor = []): bool
    {
        DB::beginTransaction();

        try {
            $chargeClass = BaseCharge::init($data);

            if($this->isDenySendMessage($denySendSmsFor, $chargeClass->getCustomerId()))
            {
                return false;
            }

            $chargeClass->saveParseData();
            DB::commit();
        } catch (DomainException $exception) {
            ErrorLog::write($exception->getMessage(), ErrorLog::PAY_ERROR_SEND_SMS);
            DB::rollBack();

            return false;
        }

        return true;
    }

    private function getDenyCustomerForSendMessages($abandonedData): array
    {
        $countryList = $this->getCountryLists();

        $costsSms = [];
        foreach ($abandonedData as $sendLog) {
            $baseCharge = BaseCharge::init($sendLog);
            list($countSms, $countryCode) = $baseCharge->getCountSmsAndCountryCode($sendLog);
            $clientId = $baseCharge->getCustomerId();

            if(!isset($costsSms[$clientId])){
                $costsSms[$clientId] = 0;
            }

            $costsSms[$clientId] += Arr::get($countryList, $countryCode, 0) * $countSms;
        }

        return $this->getDenyAndMakeCharge($costsSms);
    }


    public function makeCharge($sum = 0, string $customerName = '')
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        Charge::create([
            "amount" => $this->getChargeSum($sum),
            "currency" => "usd",
            'customer' => $customerName
        ]);
    }

    private function getCountryLists()
    {
        $countryList = AppSmsPrice::select('country_code', 'price')
            ->get()
            ->pluck('price', 'country_code')
            ->toArray();

        return $countryList;
    }

    private function getDenyAndMakeCharge(array $costsSms): array
    {
        $customerIds = array_keys($costsSms);
        $appClientsData = AppClientData::whereIn('id', $customerIds)->get()->toArray();

        $denyClientForSend = [];
        foreach ($appClientsData as $appClient) {
            $smsCosts = $costsSms[$appClient['id']];

            if ($appClient['balance'] < $smsCosts) {
                $denyClientForSend[] = $appClient['id'];
                $this->makeCharge($smsCosts, $appClient['stripe_id']);

            }
        }

        return $denyClientForSend;
    }

    private function isDenySendMessage($denySendSmsFor, $currentSms): bool
    {
        return in_array($currentSms, $denySendSmsFor);
    }

    /**
     * @param $sum
     * @return float|int
     */
    private function getChargeSum($sum)
    {
        $sum = $sum < BaseCharge::MIN_CHARGE_PAYMENT ? BaseCharge::MIN_CHARGE_PAYMENT : $sum;

        return round($sum, 1, PHP_ROUND_HALF_UP) * 100;
    }
}