<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedpath\pgsql;
use yii\db\Expression;

/**
 * @author Alexey Rogachev <arogachev90@gmail.com>
 */
trait MaterializedPathQueryTrait
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function roots()
    {
        /* @var \yii\db\ActiveQuery $this */
        $class = $this->modelClass;
        $model = new $class;


        return $this->andWhere(new Expression("nlevel({$model->pathAttribute}) = 1"));
    }
}
