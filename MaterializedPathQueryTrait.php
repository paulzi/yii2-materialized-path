<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedPath;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
trait MaterializedPathQueryTrait
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function roots()
    {
        /** @var \yii\db\ActiveQuery $this */
        $class = $this->modelClass;
        $model = new $class;
        return $this->andWhere([$model->depthAttribute => $model->rootDepthValue]);
    }
}
