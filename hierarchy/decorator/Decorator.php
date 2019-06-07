<?php
/**
 * Created by PhpStorm.
 * User: user2
 * Date: 14.12.18
 * Time: 11:46
 */
namespace frontend\components\sampling\conditionquery\hierarchy\decorator;

use frontend\components\sampling\conditionquery\hierarchy\BaseQuery;

class Decorator extends BaseQuery
{
    protected $component;
    protected $relation;

    public function __construct(BaseQuery $component)
    {
        $this->component = $component;
        $this->query = $this->component->getQuery(false);
        $this->initPath();
        $this->setHierarchyParams($this->component->getHierarchyParams());
    }

    public function handle()
    {
       return $this->component->handle();
    }

    public function getRelation()
    {
        return $this->component->getRelation();
    }

    public function initPath()
    {
        $this->hashValue = $this->component->hashValue;
        $this->basePath = $this->component->basePath;
        $this->relativePath = $this->component->relativePath;

    }
}