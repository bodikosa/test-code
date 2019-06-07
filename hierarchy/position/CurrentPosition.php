<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 26.12.18
 * Time: 12:27
 */
namespace frontend\components\sampling\conditionquery\hierarchy\position;

use frontend\components\errors\Errors;
use frontend\components\exceptions\ClientException;
use frontend\components\sampling\conditionquery\hierarchy\typePosition\BaseHierarchyType;

class CurrentPosition extends BasePosition
{
    public function getFilterData()
    {
        if(!isset(BaseHierarchyType::TYPE_POSITION_CLASS[$this->hierarchyParams['hierarchy']['levelPosition']])){
            throw new ClientException(new Errors(['error' => "Not found current levelPosition"]));
        }

        $class = BaseHierarchyType::TYPE_POSITION_CLASS[$this->hierarchyParams['hierarchy']['levelPosition']];

        return (new $class($this->query))->addCondition($this->hierarchyParams);
    }

}