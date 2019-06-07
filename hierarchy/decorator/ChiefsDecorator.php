<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 14.12.18
 * Time: 11:58
 */

namespace frontend\components\sampling\conditionquery\hierarchy\decorator;

class ChiefsDecorator extends Decorator
{
    public function handle()
    {
        $cnd = parent::handle();
        $cnd[] = 'main.id = child_' . $this->hashValue . '.record_id';

        return $cnd;
    }

    public function getRelation()
    {
        $this->relation = parent::getRelation();
        $this->relation[] = "( {$this->relativePath}.lft < {$this->basePath}.lft
                     AND {$this->relativePath}.rgt > {$this->basePath}.rgt
                     AND {$this->relativePath}.tree = {$this->basePath}.tree )";

      //  AND {$this->relativePath}.depth != 0

        return $this->relation;
    }
}