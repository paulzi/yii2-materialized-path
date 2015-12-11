<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedPath\tests\models;

use paulzi\materializedPath\MaterializedPathBehavior;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 *
 * @property integer $id
 * @property string $path
 * @property integer $depth
 * @property integer $sort
 * @property integer $slug
 *
 * @property Node[] $parents
 * @property Node $parent
 * @property Node $root
 * @property Node[] $descendants
 * @property Node[] $children
 * @property Node[] $leaves
 * @property Node $prev
 * @property Node $next
 *
 * @method static AttributeModeNode|null findOne() findOne($condition)
 *
 * @mixin MaterializedPathBehavior
 */
class AttributeModeNode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%attribute_mode_tree}}';
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => MaterializedPathBehavior::className(),
                'itemAttribute' => 'slug',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @return NodeQuery
     */
    public static function find()
    {
        return new NodeQuery(get_called_class());
    }
}