<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 26.12.18
 * Time: 12:27
 */
namespace frontend\components\sampling\conditionquery\hierarchy\position;

use frontend\modules\hierarchy\models\ModHierarchyPositionsModel;
use yii\helpers\ArrayHelper;

class AllPosition extends BasePosition
{
    public function getFilterData()
    {
        $idPosition = ArrayHelper::getValue($this->hierarchyParams, 'hierarchy.idPosition');
        $hierarchyListPositions = ModHierarchyPositionsModel::find()
            ->where(['position_list_id' => $idPosition])
            ->asArray()
            ->all();

        $this->hierarchyParams["hierarchy"]["idPosition"] = ArrayHelper::getColumn($hierarchyListPositions, 'id') ?: '"-"';

        return (new CurrentPosition($this->query, $this->hierarchyParams))->getFilterData();
    }
}