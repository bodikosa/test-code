<?php


namespace App\components\campaing\query;


use Carbon\Carbon;

class DateQuery extends BaseQuery
{
    public function getSelected(string $fieldName): string
    {
        return parent::getSelected($fieldName) . ', status';
    }

    public function getCondition(string $operator, string $value): string
    {
        return $this->fieldName . ' > "' .  $this->getParseData($value) . '"';
    }

    protected function getParseData($value):string
    {
        switch ($value){
            case "week":
                return Carbon::now()->subWeek()->toDateTimeString();
            case "month":
                return Carbon::now()->subMonth()->toDateTimeString();
            case "3months":
                return Carbon::now()->subMonth(3)->toDateTimeString();
            case "year":
                return Carbon::now()->subYear()->toDateTimeString();
        }
    }


}