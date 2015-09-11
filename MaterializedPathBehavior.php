<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedpath;

use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;

/**
 * Materialized Path Behavior for Yii2
 * @author PaulZi <pavel.zimakoff@gmail.com>
 *
 * @property ActiveRecord $owner
 */
class MaterializedPathBehavior extends Behavior
{
    const OPERATION_MAKE_ROOT       = 1;
    const OPERATION_PREPEND_TO      = 2;
    const OPERATION_APPEND_TO       = 3;
    const OPERATION_INSERT_BEFORE   = 4;
    const OPERATION_INSERT_AFTER    = 5;
    const OPERATION_DELETE_ALL      = 6;


    /**
     * @var string
     */
    public $pathAttribute = 'path';

    /**
     * @var string
     */
    public $depthAttribute = 'depth';

    /**
     * @var string
     */
    public $sortAttribute = 'sort';

    /**
     * @var string
     */
    public $itemAttribute;

    /**
     * @var string|null
     */
    public $treeAttribute;

    /**
     * @var string
     */
    public $delimiter = '/';

    /**
     * @var int
     */
    public $step = 100;

    /**
     * @var string|null
     */
    protected $operation;

    /**
     * @var ActiveRecord|self|null
     */
    protected $node;

    /**
     * @var bool
     */
    protected $primaryKeyMode = false;


    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT   => 'beforeSave',
            ActiveRecord::EVENT_AFTER_INSERT    => 'afterInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE   => 'beforeSave',
            ActiveRecord::EVENT_AFTER_UPDATE    => 'afterUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE   => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_DELETE    => 'afterDelete',
        ];
    }

    /**
     * @param ActiveRecord $owner
     * @throws Exception
     */
    public function attach($owner)
    {
        if ($this->itemAttribute === null) {
            $primaryKey = $owner->primaryKey();
            if (!isset($primaryKey[0])) {
                throw new Exception('"' . $owner->className() . '" must have a primary key.');
            }
            $this->itemAttribute = $primaryKey[0];
            $this->primaryKeyMode = true;
        }
        parent::attach($owner);
    }

    /**
     * @param int|null $depth
     * @return \yii\db\ActiveQuery
     */
    public function getParents($depth = null)
    {
        $path  = $this->getParentPath();
        if ($path !== null) {
            $paths = explode($this->delimiter, $path);
            if (!$this->primaryKeyMode) {
                $path  = null;
                $paths = array_map(
                    function ($value) use (&$path) {
                        return $path = ($path !== null ? $path . $this->delimiter : '') . $value;
                    },
                    $paths
                );
            }
            if ($depth !== null) {
                $paths = array_slice($paths, -$depth);
            }
        } else {
            $paths = [];
        }

        $tableName = $this->owner->tableName();
        $condition = ['and'];
        if ($this->primaryKeyMode) {
            $condition[] = ["{$tableName}.[[{$this->itemAttribute}]]" => $paths];
        } else {
            $condition[] = ["{$tableName}.[[{$this->pathAttribute}]]" => $paths];
        }

        $query = $this->owner->find()
            ->andWhere($condition)
            ->andWhere($this->treeCondition())
            ->addOrderBy(["{$tableName}.[[{$this->pathAttribute}]]" => SORT_ASC]);
        $query->multiple = true;

        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        $query = $this->getParents(1)->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoot()
    {
        $path = explode($this->delimiter, $this->owner->getAttribute($this->pathAttribute));
        $path = array_shift($path);
        $tableName = $this->owner->tableName();
        $query = $this->owner->find()
            ->andWhere(["{$tableName}.[[{$this->pathAttribute}]]" => $path])
            ->andWhere($this->treeCondition())
            ->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * @param int|null $depth
     * @param bool $andSelf
     * @return \yii\db\ActiveQuery
     */
    public function getDescendants($depth = null, $andSelf = false)
    {
        $tableName = $this->owner->tableName();
        $path = $this->owner->getAttribute($this->pathAttribute);
        $like = strtr($path . $this->delimiter, ['%' => '\%', '_' => '\_', '\\' => '\\\\']);

        $query = $this->owner->find()
            ->andWhere(['like', "{$tableName}.[[{$this->pathAttribute}]]", $like . '%', false]);

        if ($andSelf) {
            $query->orWhere(["{$tableName}.[[{$this->pathAttribute}]]" => $path]);
        }

        if ($depth !== null) {
            $query->andWhere(['<=', "{$tableName}.[[{$this->depthAttribute}]]", $this->owner->getAttribute($this->depthAttribute) + $depth]);
        }

        $query
            ->andWhere($this->treeCondition())
            ->addOrderBy([
                "{$tableName}.[[{$this->depthAttribute}]]" => SORT_ASC,
                "{$tableName}.[[{$this->sortAttribute}]]"  => SORT_ASC,
            ]);
        $query->multiple = true;

        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->getDescendants(1);
    }

    /**
     * @param int|null $depth
     * @return \yii\db\ActiveQuery
     */
    public function getLeaves($depth = null)
    {
        $tableName = $this->owner->tableName();
        $condition = [
            'and',
            ['like', "leaves.[[{$this->pathAttribute}]]",  new Expression("CONCAT({$tableName}.[[{$this->pathAttribute}]], :delimiter, '%')", [':delimiter' => $this->delimiter])],
        ];

        if ($this->treeAttribute !== null) {
            $condition[] = ["leaves.[[{$this->treeAttribute}]]" => new Expression("{$tableName}.[[{$this->treeAttribute}]]")];
        }

        $query = $this->getDescendants($depth)
            ->leftJoin("{$tableName} leaves", $condition)
            ->andWhere(["leaves.[[{$this->pathAttribute}]]" => null]);
        $query->multiple = true;
        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPrev()
    {
        $tableName = $this->owner->tableName();
        $like = strtr($this->getParentPath() . $this->delimiter, ['%' => '\%', '_' => '\_', '\\' => '\\\\']);
        $query = $this->owner->find()
            ->andWhere(['like', "{$tableName}.[[{$this->pathAttribute}]]", $like . '%', false])
            ->andWhere(["{$tableName}.[[{$this->depthAttribute}]]" => $this->owner->getAttribute($this->depthAttribute)])
            ->andWhere(['<', "{$tableName}.[[{$this->sortAttribute}]]", $this->owner->getAttribute($this->sortAttribute)])
            ->andWhere($this->treeCondition())
            ->orderBy(["{$tableName}.[[{$this->sortAttribute}]]" => SORT_DESC])
            ->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNext()
    {
        $tableName = $this->owner->tableName();
        $like = strtr($this->getParentPath() . $this->delimiter, ['%' => '\%', '_' => '\_', '\\' => '\\\\']);
        $query = $this->owner->find()
            ->andWhere(['like', "{$tableName}.[[{$this->pathAttribute}]]", $like . '%', false])
            ->andWhere(["{$tableName}.[[{$this->depthAttribute}]]" => $this->owner->getAttribute($this->depthAttribute)])
            ->andWhere(['>', "{$tableName}.[[{$this->sortAttribute}]]", $this->owner->getAttribute($this->sortAttribute)])
            ->andWhere($this->treeCondition())
            ->orderBy(["{$tableName}.[[{$this->sortAttribute}]]" => SORT_ASC])
            ->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return count(explode($this->delimiter, $this->owner->getAttribute($this->pathAttribute))) === 1;
    }

    /**
     * @param ActiveRecord $node
     * @return bool
     */
    public function isChildOf($node)
    {
        $nodePath  = $node->getAttribute($this->pathAttribute) . $this->delimiter;
        $result = substr($this->owner->getAttribute($this->pathAttribute), 0, strlen($nodePath)) === $nodePath;

        if ($result && $this->treeAttribute !== null) {
            $result = $this->owner->getAttribute($this->treeAttribute) === $node->getAttribute($this->treeAttribute);
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isLeaf()
    {
        return count($this->owner->children) === 0;
    }

    /**
     * @return ActiveRecord
     */
    public function makeRoot()
    {
        $this->operation = self::OPERATION_MAKE_ROOT;
        return $this->owner;
    }

    /**
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function prependTo($node)
    {
        $this->operation = self::OPERATION_PREPEND_TO;
        $this->node = $node;
        return $this->owner;
    }

    /**
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function appendTo($node)
    {
        $this->operation = self::OPERATION_APPEND_TO;
        $this->node = $node;
        return $this->owner;
    }

    /**
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function insertBefore($node)
    {
        $this->operation = self::OPERATION_INSERT_BEFORE;
        $this->node = $node;
        return $this->owner;
    }

    /**
     * @param ActiveRecord $node
     * @return ActiveRecord
     */
    public function insertAfter($node)
    {
        $this->operation = self::OPERATION_INSERT_AFTER;
        $this->node = $node;
        return $this->owner;
    }

    /**
     * @return bool|int
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function deleteWithChildren()
    {
        $this->operation = self::OPERATION_DELETE_ALL;
        if (!$this->owner->isTransactional(ActiveRecord::OP_DELETE)) {
            $transaction = $this->owner->getDb()->beginTransaction();
            try {
                $result = $this->deleteWithChildrenInternal();
                if ($result === false) {
                    $transaction->rollBack();
                } else {
                    $transaction->commit();
                }
                return $result;
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } else {
            $result = $this->deleteWithChildrenInternal();
        }
        return $result;
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function beforeSave()
    {
        if ($this->node !== null && !$this->node->getIsNewRecord()) {
            $this->node->refresh();
        }

        switch ($this->operation) {
            case self::OPERATION_MAKE_ROOT:
                $this->makeRootInternal();

                break;
            case self::OPERATION_PREPEND_TO:
                $this->insertIntoInternal(false);

                break;
            case self::OPERATION_APPEND_TO:
                $this->insertIntoInternal(true);

                break;
            case self::OPERATION_INSERT_BEFORE:
                $this->insertNearInternal(false);

                break;

            case self::OPERATION_INSERT_AFTER:
                $this->insertNearInternal(true);

                break;

            default:
                if ($this->owner->getIsNewRecord()) {
                    throw new NotSupportedException('Method "' . $this->owner->className() . '::insert" is not supported for inserting new nodes.');
                }

                $item = $this->owner->getAttribute($this->itemAttribute);
                $path = $this->getParentPath($this->owner->getAttribute($this->pathAttribute));
                $this->owner->setAttribute($this->pathAttribute, $path . $this->delimiter . $item);
        }
    }

    /**
     * @throws Exception
     */
    public function afterInsert()
    {
        if ($this->operation === self::OPERATION_MAKE_ROOT && $this->treeAttribute !== null && $this->owner->getAttribute($this->treeAttribute) === null) {
            $id = $this->owner->getPrimaryKey();
            $this->owner->setAttribute($this->treeAttribute, $id);

            $primaryKey = $this->owner->primaryKey();
            if (!isset($primaryKey[0])) {
                throw new Exception('"' . $this->owner->className() . '" must have a primary key.');
            }

            $this->owner->updateAll([$this->treeAttribute => $id], [$primaryKey[0] => $id]);
        }
        if ($this->owner->getAttribute($this->pathAttribute) === null) {
            $primaryKey = $this->owner->primaryKey();
            if (!isset($primaryKey[0])) {
                throw new Exception('"' . $this->owner->className() . '" must have a primary key.');
            }
            $id = $this->owner->getPrimaryKey();
            if ($this->operation === self::OPERATION_MAKE_ROOT) {
                $path = $id;
            } else {
                $path = $this->node->getAttribute($this->pathAttribute);
                if ($this->operation === self::OPERATION_INSERT_BEFORE || $this->operation === self::OPERATION_INSERT_AFTER) {
                    $path = $this->getParentPath($path);
                }
                $path = $path . $this->delimiter . $id;
            }
            $this->owner->setAttribute($this->pathAttribute, $path);
            $this->owner->updateAll([$this->pathAttribute => $path], [$primaryKey[0] => $id]);
        }
        $this->operation = null;
        $this->node      = null;
    }

    /**
     * @param \yii\db\AfterSaveEvent $event
     */
    public function afterUpdate($event)
    {
        $this->moveNode($event->changedAttributes);
        $this->operation = null;
        $this->node      = null;
    }

    /**
     * @param \yii\base\ModelEvent $event
     * @throws Exception
     */
    public function beforeDelete($event)
    {
        if ($this->owner->getIsNewRecord()) {
            throw new Exception('Can not delete a node when it is new record.');
        }
        if ($this->isRoot() && $this->operation !== self::OPERATION_DELETE_ALL) {
            throw new Exception('Method "'. $this->owner->className() . '::delete" is not supported for deleting root nodes.');
        }
        $this->owner->refresh();
        if ($this->operation !== static::OPERATION_DELETE_ALL && !$this->primaryKeyMode) {
            /** @var self $parent */
            $parent =$this->getParent()->one();
            $slugs1 = $parent->getChildren()
                ->andWhere(['<>', $this->itemAttribute, $this->owner->getAttribute($this->itemAttribute)])
                ->select([$this->itemAttribute])
                ->column();
            $slugs2 = $this->getChildren()
                ->select([$this->itemAttribute])
                ->column();
            if (array_intersect($slugs1, $slugs2)) {
                $event->isValid = false;
            }
        }
    }

    /**
     *
     */
    public function afterDelete()
    {
        if ($this->operation !== static::OPERATION_DELETE_ALL) {
            foreach ($this->owner->children as $child) {
                /** @var self $child */
                if ($this->owner->next === null) {
                    $child->appendTo($this->owner->parent)->save();
                } else {
                    $child->insertBefore($this->owner->next)->save();
                }
            }
        }
        $this->operation = null;
        $this->node      = null;
    }

    /**
     * @param bool $forInsertNear
     * @throws Exception
     */
    protected function checkNode($forInsertNear = false)
    {
        if ($forInsertNear && $this->node->isRoot()) {
            throw new Exception('Can not move a node before/after root.');
        }
        if ($this->node->getIsNewRecord()) {
            throw new Exception('Can not move a node when the target node is new record.');
        }

        if ($this->owner->equals($this->node)) {
            throw new Exception('Can not move a node when the target node is same.');
        }

        if ($this->node->isChildOf($this->owner)) {
            throw new Exception('Can not move a node when the target node is child.');
        }
    }

    /**
     * @param int $to
     * @param bool $forward
     */
    protected function moveTo($to, $forward)
    {
        $this->owner->setAttribute($this->sortAttribute, $to + ($forward ? 1 : -1));

        $tableName = $this->owner->tableName();
        $path = $this->getParentPath($this->node->getAttribute($this->pathAttribute));
        $like = strtr($path . $this->delimiter, ['%' => '\%', '_' => '\_', '\\' => '\\\\']);

        $joinCondition = [
            'and',
            ['like', "n.[[{$this->pathAttribute}]]", $like . '%', false],
            [
                "n.[[{$this->depthAttribute}]]" => $this->node->getAttribute($this->depthAttribute),
                "n.[[{$this->sortAttribute}]]"  => new Expression("{$tableName}.[[{$this->sortAttribute}]] " . ($forward ? '+' : '-') . " 1"),
            ],
        ];
        if (!$this->owner->getIsNewRecord()) {
            $joinCondition[] = ['<>', "n.[[{$this->pathAttribute}]]", $this->owner->getAttribute($this->pathAttribute)];
        }
        if ($this->treeAttribute !== null) {
            $joinCondition[] = ["n.[[{$this->treeAttribute}]]" => new Expression("{$tableName}.[[{$this->treeAttribute}]]")];
        }

        $unallocated = (new Query())
            ->select("{$tableName}.[[{$this->sortAttribute}]]")
            ->from("{$tableName}")
            ->leftJoin("{$tableName} n", $joinCondition)
            ->where([
                'and',
                ['like', "{$tableName}.[[{$this->pathAttribute}]]", $like . '%', false],
                $this->treeCondition(),
                [$forward ? '>=' : '<=', "{$tableName}.[[{$this->sortAttribute}]]", $to],
                [
                    "{$tableName}.[[{$this->depthAttribute}]]" => $this->node->getAttribute($this->depthAttribute),
                    "n.[[{$this->sortAttribute}]]"             => null,
                ],
            ])
            ->orderBy(["{$tableName}.[[{$this->sortAttribute}]]" => $forward ? SORT_ASC : SORT_DESC])
            ->limit(1)
            ->scalar($this->owner->getDb());

        $this->owner->updateAll(
            [$this->sortAttribute => new Expression("[[{$this->sortAttribute}]] " . ($forward ? '+' : '-') . " 1")],
            [
                'and',
                ['like', "[[{$this->pathAttribute}]]", $like . '%', false],
                $this->treeCondition(),
                ["[[{$this->depthAttribute}]]" => $this->node->getAttribute($this->depthAttribute)],
                ['between', $this->sortAttribute, $forward ? $to + 1 : $unallocated, $forward ? $unallocated : $to - 1],
            ]
        );
    }

    /**
     * Make root operation internal handler
     */
    protected function makeRootInternal()
    {
        $item = $this->owner->getAttribute($this->itemAttribute);

        if ($item !== null) {
            $this->owner->setAttribute($this->pathAttribute, $item);
        }

        if ($this->sortAttribute !== null) {
            $this->owner->setAttribute($this->sortAttribute, 0);
        }

        if ($this->treeAttribute !== null) {
            if ($this->owner->getOldAttribute($this->treeAttribute) !== $this->owner->getAttribute($this->treeAttribute)) {
                $this->owner->setAttribute($this->treeAttribute, $this->owner->getAttribute($this->treeAttribute));
            } elseif (!$this->owner->getIsNewRecord()) {
                $this->owner->setAttribute($this->treeAttribute, $this->owner->getPrimaryKey());
            }
        }

        $this->owner->setAttribute($this->depthAttribute, 0);
    }

    /**
     * Append to operation internal handler
     * @param bool $append
     * @throws Exception
     */
    protected function insertIntoInternal($append)
    {
        $this->checkNode(false);
        $item = $this->owner->getAttribute($this->itemAttribute);

        if ($item !== null) {
            $path = $this->node->getAttribute($this->pathAttribute);
            $this->owner->setAttribute($this->pathAttribute, $path . $this->delimiter . $item);
        }

        $this->owner->setAttribute($this->depthAttribute, $this->node->getAttribute($this->depthAttribute) + 1);

        if ($this->treeAttribute !== null) {
            $this->owner->setAttribute($this->treeAttribute, $this->node->getAttribute($this->treeAttribute));
        }

        if ($this->sortAttribute !== null) {
            $to = $this->node->getChildren()->orderBy(null);
            $to = $append ? $to->max($this->sortAttribute) : $to->min($this->sortAttribute);
            if (
                !$this->owner->getIsNewRecord() && (int)$to === $this->owner->getAttribute($this->sortAttribute)
                && !$this->owner->getDirtyAttributes([$this->pathAttribute])
                && ($this->treeAttribute === null || !$this->owner->getDirtyAttributes([$this->treeAttribute]))
            ) {
            } elseif ($to !== null) {
                $to += $append ? $this->step : -$this->step;
            } else {
                $to = 0;
            }
            $this->owner->setAttribute($this->sortAttribute, $to);
        }
    }

    /**
     * Insert operation internal handler
     * @param bool $forward
     * @throws Exception
     */
    protected function insertNearInternal($forward)
    {
        $this->checkNode(true);
        $item = $this->owner->getAttribute($this->itemAttribute);

        if ($item !== null) {
            $path = $this->getParentPath($this->node->getAttribute($this->pathAttribute));
            $this->owner->setAttribute($this->pathAttribute, $path . $this->delimiter . $item);
        }

        $this->owner->setAttribute($this->depthAttribute, $this->node->getAttribute($this->depthAttribute));

        if ($this->treeAttribute !== null) {
            $this->owner->setAttribute($this->treeAttribute, $this->node->getAttribute($this->treeAttribute));
        }

        if ($this->sortAttribute !== null) {
            $this->moveTo($this->node->getAttribute($this->sortAttribute), $forward);
        }
    }

    /**
     * @return int
     */
    protected function deleteWithChildrenInternal()
    {
        if (!$this->owner->beforeDelete()) {
            return false;
        }
        $result = $this->owner->deleteAll($this->getDescendants(null, true)->where);
        $this->owner->setOldAttributes(null);
        $this->owner->afterDelete();
        return $result;
    }

    /**
     * @param array $changedAttributes
     * @throws Exception
     */
    protected function moveNode($changedAttributes)
    {
        $path = isset($changedAttributes[$this->pathAttribute]) ? $changedAttributes[$this->pathAttribute] : $this->owner->getAttribute($this->pathAttribute);
        $like = strtr($path . $this->delimiter, ['%' => '\%', '_' => '\_', '\\' => '\\\\']);
        $update = [];
        $condition = [
            'and',
            ['like', "[[{$this->pathAttribute}]]", $like . '%', false],
        ];
        if ($this->treeAttribute !== null) {
            $tree = isset($changedAttributes[$this->treeAttribute]) ? $changedAttributes[$this->treeAttribute] : $this->owner->getAttribute($this->treeAttribute);
            $condition[] = [$this->treeAttribute => $tree];
        }
        $params = [];

        if (isset($changedAttributes[$this->pathAttribute])) {
            $update['path']     = new Expression("CONCAT(:pathNew, SUBSTRING([[path]], LENGTH(:pathOld) + 1))");
            $params[':pathOld'] = $path;
            $params[':pathNew'] = $this->owner->getAttribute($this->pathAttribute);
        }

        if ($this->treeAttribute !== null && isset($changedAttributes[$this->treeAttribute])) {
            $update[$this->treeAttribute] = $this->owner->getAttribute($this->treeAttribute);
        }

        if ($this->depthAttribute !== null && isset($changedAttributes[$this->depthAttribute])) {
            $delta = $this->owner->getAttribute($this->depthAttribute) - $changedAttributes[$this->depthAttribute];
            $update[$this->depthAttribute] = new Expression("[[{$this->depthAttribute}]]" . sprintf('%+d', $delta));
        }

        if (!empty($update)) {
            $this->owner->updateAll($update, $condition, $params);
        }
    }

    /**
     * @param string|bool $path
     * @return null|string
     */
    protected function getParentPath($path = false)
    {
        if ($path === false) {
            $path = $this->owner->getAttribute($this->pathAttribute);
        }
        $path = explode($this->delimiter, $path);
        array_pop($path);
        return count($path) > 0 ? implode($this->delimiter, $path) : null;
    }

    /**
     * @return array
     */
    protected function treeCondition()
    {
        $tableName = $this->owner->tableName();
        if ($this->treeAttribute === null) {
            return [];
        } else {
            return ["{$tableName}.[[{$this->treeAttribute}]]" => $this->owner->getAttribute($this->treeAttribute)];
        }
    }
}
