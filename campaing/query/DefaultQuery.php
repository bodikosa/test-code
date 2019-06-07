<?php


namespace App\components\campaing\query;


class DefaultQuery extends BaseQuery
{
    public function getCondition(string $operator, string $value): string
    {
        return $this->fieldName . ' ' . $operator . ' "' . $value . '"';
    }
}