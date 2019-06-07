<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 13.12.18
 * Time: 18:40
 */

namespace frontend\components\sampling\conditionquery\hierarchy;

use frontend\modules\hierarchy\models\ModHierarchyPositionsModel;
use yii\helpers\ArrayHelper;


abstract class BaseQuery
{
    public $query;
    public $hierarchyParams;

    protected $basePath;
    protected $relativePath;
    protected $hashValue;


    public function __construct($query, $hierarchyParams)
    {
        $this->query = $query;

        $this->setHierarchyParams($hierarchyParams);

        $this->hashValue = uniqid();
        $this->basePath = 'base_position_'. $this->hashValue;
        $this->relativePath = 'another_position_'. $this->hashValue;
    }

    public abstract function handle();

    public function getRelation()
    {
        $this->query;
    }

    public function getCondition(): string
    {
        $idStatus = ArrayHelper::getValue($this->hierarchyParams, 'hierarchy.idStatus');
        $conditionData = implode( " OR ", array_unique($this->handle()));
        $conditionData = "( {$conditionData} )";

        if($idStatus){
            $dataStatus = 'pro_status_' . $this->hashValue . '.status_id = ' . $idStatus;
            $conditionData = "({$conditionData} AND {$dataStatus})";
        }

        return $conditionData;
    }

    public function getHierarchyParams()
    {
        return $this->hierarchyParams;
    }

    public function setHierarchyParams(array $hierarchyParams): void
    {
        $this->hierarchyParams = $hierarchyParams;
    }

    public function getQuery($fullQuery = true){

        if ($fullQuery){
            $performAl = 'performer_'.$this->hashValue;
            $performPayAl = 'performer_pay_'.$this->hashValue;
            $relationCnd = implode(' OR ', $this->getRelation());

            $subQuery = ModHierarchyPositionsModel::find()
                ->alias($this->basePath)
                ->select([
                    "{$this->basePath}.id as id",
                    "{$performAl}.record_id as record_id",
                    "{$performPayAl}.record_id as record_pay_id",
                ])
                ->leftJoin("hierarchy_positions AS {$this->relativePath}",
                    $relationCnd
                )
                ->leftJoin("hierarchy_position_performers AS {$performAl}",
                    "{$performAl}.position_id = {$this->relativePath}.id"
                )
                ->leftJoin("hierarchy_records AS {$performPayAl}",
                    "{$performPayAl}.position_id = {$this->basePath}.id"
                );

            $idPosition = ArrayHelper::getValue($this->hierarchyParams, 'hierarchy.idPosition');
            $positionDataId = is_array($idPosition) ? ' IN (' . implode(',', $idPosition) . ')' : '=' . $idPosition ;

            $this->query->leftJoin(['child_'. $this->hashValue => $subQuery], 'child_'. $this->hashValue .'.id' . $positionDataId);
        }

        return $this->query;
    }

}