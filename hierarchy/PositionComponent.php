<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 14.12.18
 * Time: 11:42
 */
namespace frontend\components\sampling\conditionquery\hierarchy;

use frontend\models\base\HierarchyRecordsStatuses;

class PositionComponent extends BaseQuery
{
    public function handle()
    {
        return ["main.id = child_". $this->hashValue .".record_id"];
    }

    public function getRelation()
    {
        $statusAlias = "pro_status_{$this->hashValue}";
        $this->query->leftJoin(HierarchyRecordsStatuses::tableName() ." ". $statusAlias, 'main.id='. $statusAlias . '.record_id');

        return ["( {$this->relativePath}.lft = {$this->basePath}.lft
                     AND {$this->relativePath}.rgt = {$this->basePath}.rgt
                     AND {$this->relativePath}.tree = {$this->basePath}.tree )"];
    }
}