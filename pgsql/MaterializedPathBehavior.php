<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedPath\pgsql;

use paulzi\materializedPath\MaterializedPathBehavior as BaseMaterializedPathBehavior;

/**
 * @author Alexey Rogachev <arogachev90@gmail.com>
 */
class MaterializedPathBehavior extends BaseMaterializedPathBehavior
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->depthAttribute = null;
        $this->delimiter = '.';
    }

    /**
     * @inheritdoc
     */
    public function getDescendants($depth = null, $andSelf = false)
    {
        $path = $this->owner->getAttribute($this->pathAttribute);
        $query = $this->owner
            ->find()
            ->where(['~', $this->pathAttribute, "$path.*{,$depth}"])
            ->orderBy([$this->pathAttribute => SORT_ASC]);

        if ($andSelf) {
            $query->orWhere([$this->pathAttribute => $this->owner->getAttribute($this->pathAttribute)]);
        }

        return $query;
    }
}
