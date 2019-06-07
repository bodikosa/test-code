<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 14.12.18
 * Time: 15:06
 */
namespace frontend\components\sampling\conditionquery\hierarchy\typePosition;

use frontend\components\sampling\conditionquery\hierarchy\decorator\EmpDecorator;
use frontend\components\sampling\conditionquery\hierarchy\PositionComponent;

class PositionEmpType extends BaseHierarchyType
{
    public function addCondition(array $cnd = [])
    {
        $positionComponent = new PositionComponent($this->query, $cnd);

        return new EmpDecorator($positionComponent);
    }
}