<?php


namespace App\components\campaing\query;


class GroupQuery extends BaseQuery
{
    public function getSelected(string $fieldName): string
    {
        $this->setFieldParse($fieldName);

        return $this->fieldName . ' as ' . $this->fieldName;
    }

    public function getCondition(string $operator, string $value): string
    {
        return $this->fieldName . " " . $operator . " " . $value;
    }
}