<?php


namespace App\components\campaing\query;


use App\Model\AppAbandonedCartDetail;

class AboundedQuery extends DateQuery
{
    public function getCondition(string $operator, string $value): string
    {
//        return parent::getCondition($operator, $value) . ' AND status = ' . AppAbandonedCartDetail::ABANDONED_CART_STATUSES['ACTIVE'];
        return parent::getCondition($operator, $value) ;
    }
}