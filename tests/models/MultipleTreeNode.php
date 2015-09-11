<?php

namespace tests\models;

use paulzi\materializedpath\MaterializedPathBehavior;

/**
 * @property integer $id
 * @property integer $tree
 * @property string $path
 * @property integer $depth
 * @property integer $sort
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
 * @method static MultipleTreeNode|null findOne() findOne($condition)
 *
 * @mixin MaterializedPathBehavior
 */
class MultipleTreeNode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%multiple_tree}}';
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
                'treeAttribute' => 'tree',
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