<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 26.12.18
 * Time: 12:27
 */
namespace frontend\components\sampling\conditionquery\hierarchy\position;

abstract class BasePosition
{
    public $hierarchyParams;
    public $query;

    public function __construct($query, $params)
    {
        $this->query = $query;
        $this->hierarchyParams = $params;
    }

    abstract public function getFilterData();
}