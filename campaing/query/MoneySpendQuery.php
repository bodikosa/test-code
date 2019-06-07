<?php


namespace App\components\campaing\query;


class MoneySpendQuery extends BaseQuery
{
    public function getSelected(string $fieldName): string
    {
        $this->setFieldParse($fieldName);

        return 'SUM(app_customer_orders.' . $this->fieldName .') as ' . $this->fieldName;
    }

    public function getCondition(string $operator, string $value): string
    {
        return $this->fieldName . " " . $operator . " " . $value;
    }
}