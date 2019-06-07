<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 14.12.18
 * Time: 14:58
 */
namespace frontend\components\sampling\conditionquery\hierarchy\typePosition;

use frontend\components\errors\Errors;
use frontend\components\exceptions\ClientException;
use frontend\components\sampling\conditionquery\hierarchy\position\AllPosition;
use frontend\components\sampling\conditionquery\hierarchy\position\CurrentPosition;
use yii\helpers\ArrayHelper;

abstract class BaseHierarchyType
{
    const TYPE_POSITION =[
        'POS' => 1,
        'POS_EPL' => 2,
        'POS_EPL_CHIEFS' => 3,
        'POS_EPL_CHIEF' => 4,
        'POS_CHIEFS' => 5,
        'POS_CHIEF' => 6,
    ];

    const TYPE_POSITION_CLASS =[
        self::TYPE_POSITION['POS'] => PositionType::class,
        self::TYPE_POSITION['POS_EPL'] => PositionEmpType::class,
        self::TYPE_POSITION['POS_EPL_CHIEFS'] => PositionEmpChiefsType::class,
        self::TYPE_POSITION['POS_EPL_CHIEF'] => PositionEmpChiefType::class,
        self::TYPE_POSITION['POS_CHIEFS'] => PositionChiefsType::class,
        self::TYPE_POSITION['POS_CHIEF'] => PositionChiefType::class,
    ];

    public $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public static function getConditionClass($query, $condition)
    {
        $positionVal = ArrayHelper::getValue($condition, 'hierarchy.countRespondent');

        $classPosition = '';
        switch ($positionVal) {
            case 1:
                $classPosition = CurrentPosition::class;
                break;
            case 2:
                $classPosition = AllPosition::class;
                break;
        }

        if(!$classPosition)
        {
            throw new ClientException(new Errors([
                'error' => "Not found hierarchy countRespondent params",
            ]));
        }

        return (new $classPosition($query, $condition))->getFilterData();
    }

    abstract public function addCondition(array $cnd);

}