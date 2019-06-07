<?php


namespace App\components\campaing;


use App\components\campaing\query\BaseQuery;
use App\Model\AppAbandonedCartDetail;
use App\Model\AppAbandonedCheckouts;
use App\Model\AppCustomer;
use App\Model\AppCustomerOrders;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FilterQueryClass
{
    protected $shopId;
    protected $aboundedParams;

    public function __construct($shopId)
    {
        $this->shopId = $shopId;
    }

    public function getResultQuery(array $conditionParams)
    {
       $dataValues = $this->getUserDataByParams($conditionParams);

       return $this->prepareViewData($dataValues);

    }

    private function getAbboundedData(){

        return AppCustomer::query()
            ->select( DB::raw("id_user , SUM(app_sms_prices.price) as sum_price, app_abandoned_checkouts.created_at as date_created"))
            ->leftJoin('app_abandoned_checkouts', 'app_customers.id_user', '=', 'app_abandoned_checkouts.shop_customer_id')
            ->leftJoin('app_sms_prices', 'app_customers.country_code', '=', 'app_sms_prices.country_code')
            ->where('id_store', '=', $this->shopId)
            ->whereNotNull('app_sms_prices.price')
            ->groupBy('id_user')
            ->havingRaw(DB::raw(implode(" AND ", $this->aboundedParams)))
            ->get()
            ->toArray();

    }

    private function getQueryData(array $selectedData, array $filterData):array
    {
        $selectParams = "id_user , app_sms_prices.price as sum_price";
        if($filterData){
            $selectParams .= ', ' . implode(" , ", $selectedData);
        }

        $queryParams = AppCustomer::query()
            ->select( DB::raw($selectParams))
            ->leftJoin('app_customer_orders', 'app_customers.id_user', '=', 'app_customer_orders.customer_id')
            ->leftJoin('app_sms_prices', 'app_customers.country_code', '=', 'app_sms_prices.country_code')
            ->where('id_store', '=', $this->shopId)
            ->whereNotNull('app_sms_prices.price')
            ->groupBy('id_user');

       if($filterData){
           $queryParams = $queryParams->havingRaw(DB::raw(implode(" AND ", $filterData)));
       }

       return $queryParams->get()->toArray();
    }

    private function prepareViewData(array $filterParams):array
    {
        $filterParams = collect($filterParams);

        return [
            'recipients' => $filterParams->count(),
            'costs' => $filterParams->sum('sum_price'),
        ];
    }

    private function getConditionData(array $conditionParams): array
    {
        $selectData = [];
        $filterData = [];
        foreach (Arr::get($conditionParams, 'condition', []) as $key => $conditionName) {

            $operator = Arr::get($conditionParams, "operator.{$key}");
            $value = Arr::get($conditionParams, "value_condition.{$key}");
            $classData = BaseQuery::init($conditionName);

            if($conditionName == "abandoned-order"){
                $classData->getSelected($conditionName);
                $this->aboundedParams[] = $classData->getCondition($operator, $value);
                continue;
            }

            $selectData[] = $classData->getSelected($conditionName);
            $filterData[] = $classData->getCondition($operator, $value);
        }

        return [$selectData, $filterData];
    }

    public function getFilterUser(array $filterParams): array
    {
        $groupedParams = [
            'condition' => [],
            'operator' => [],
            'value_condition' => [],
        ];
        foreach ($filterParams as $key => $filterParam) {
                $groupedParams['condition'][$key] = $filterParam['condition'];
                $groupedParams['operator'][$key] = $filterParam['operator'];
                $groupedParams['value_condition'][$key] = $filterParam['value_condition'];

        }

        return $this->getUserDataByParams($groupedParams);
    }

    private function getUserDataByParams(array $conditionParams): array
    {
        list($selectData, $filterData) = $this->getConditionData($conditionParams);
        $dataValues = $this->getQueryData($selectData, $filterData);

        if ($this->aboundedParams) {

            $abboundedVales = $this->getAbboundedData();
            $dataValues = collect($dataValues)->keyBy('customer_id')->toArray();
            foreach ($abboundedVales as $abboundedValue) {
                if (isset($dataValues[$abboundedValue['customer_id']])) {
                    $dataValues[$abboundedValue['customer_id']]['sum_price'] += $abboundedValue['sum_price'];
                    continue;
                }
                $dataValues[$abboundedValue['customer_id']] = $abboundedValue;
            }
        }

        return $dataValues;
    }
}