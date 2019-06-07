<?php


namespace App\components\campaing\query;


abstract class BaseQuery
{
    protected $fieldName;
    public static $fieldsParse = [
        "money-spend"  => 'total_inc_tax',
        "number-orders" => 'id_order',
        "placed-order" => 'date_created',
        "abandoned-order" => 'date_created',
        "accepts-marketing" => 'is_email_opt_in',
        "located-order" => 'geoip_country',
        "growth-tools" => 'is_popup',
        "groups-order" => 'customer_group_id',
    ];

    public function getSelected(string $fieldName): string
    {
        $this->setFieldParse($fieldName);
        return 'app_customer_orders.' . $this->fieldName . ' AS ' . $this->fieldName;
    }

    public function setFieldParse(string $fieldName): void
    {
        $this->fieldName = self::$fieldsParse[$fieldName];
    }

    abstract function getCondition(string $operator, string $value):string;

    public static function init(string $typeQuery): self
    {
        switch ($typeQuery){
            case  "placed-order":
                return new PlacedQuery();
            case  "abandoned-order":
                return new AboundedQuery();
            case  "money-spend":
                return new MoneySpendQuery();
            case  "number-orders":
                return new NumberOrderQuery();
            case  "groups-order":
                return new GroupQuery();
            default:
                return new DefaultQuery();
        }
    }

}