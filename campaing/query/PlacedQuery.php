<?php


namespace App\components\campaing\query;


use App\Model\AppCustomerOrders;

class PlacedQuery extends DateQuery
{
    public function getCondition(string $operator, string $value): string
    {
        return parent::getCondition($operator, $value) . " AND status = " . AppCustomerOrders::ORDER_STATUS['Completed	Client'];
    }
}