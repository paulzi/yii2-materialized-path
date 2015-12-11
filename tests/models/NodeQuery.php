<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedPath\tests\models;

use paulzi\materializedPath\MaterializedPathQueryTrait;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
class NodeQuery extends \yii\db\ActiveQuery
{
    use MaterializedPathQueryTrait;
}