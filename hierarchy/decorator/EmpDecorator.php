<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 14.12.18
 * Time: 11:58
 */

namespace frontend\components\sampling\conditionquery\hierarchy\decorator;

class EmpDecorator extends Decorator
{
    public function handle()
    {
        $cnd = parent::handle();
        $cnd[] = 'main.id = child_' . $this->hashValue . '.record_id';
        $cnd[] = 'main.id = child_' . $this->hashValue . '.record_pay_id';

        return $cnd;
    }

    public function getRelation()
    {
        $this->relation = parent::getRelation();

        $this->relation[] = "( {$this->relativePath}.lft > {$this->basePath}.lft
                     AND {$this->relativePath}.rgt < {$this->basePath}.rgt
                     AND {$this->relativePath}.depth - 1 = {$this->basePath}.depth
                     AND {$this->relativePath}.tree = {$this->basePath}.tree )";

        return $this->relation;

    }
}