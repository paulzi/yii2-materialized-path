<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedPath;

use paulzi\sortable\SortableBehavior;
use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\db\Expression;

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
    public $itemAttribute;

    /**
     * @var string|null
     */
    public $treeAttribute;

    /**
     * @var array|false SortableBehavior config
     */
    public $sortable = [];

    /**
     * @var string
     */
    public $delimiter = '/';

    /**
     * @var int Value of $depthAttribute for root node.
     */
    public $rootDepthValue = 0;
    
    /**
     * @var int|null
     */
    protected $operation;

    /**
     * @var ActiveRecord|self|null
     */
    protected $node;

    /**
     * @var SortableBehavior
     */
    protected $behavior;

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
        parent::attach($owner);
        if ($this->itemAttribute === null) {
            $primaryKey = $owner->primaryKey();
            if (!isset($primaryKey[0])) {
                throw new Exception('"' . $owner->className() . '" must have a primary key.');
            }
            $this->itemAttribute = $primaryKey[0];
            $this->primaryKeyMode = true;
        }
        if ($this->sortable !== false) {
            $this->behavior = Yii::createObject(array_merge(
                [
                    'class' => SortableBehavior::className(),
                    'query' => function () {
                        return $this->getSortableQuery();
                    },
                ],
                $this->sortable
            ));
            $owner->attachBehavior('materialized-path-sortable', $this->behavior);
        }
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
        $query = $this->owner->find()
            ->andWhere(['like', "{$tableName}.[[{$this->pathAttribute}]]", $this->getLike($path), false]);

        if ($andSelf) {
            $query->orWhere(["{$tableName}.[[{$this->pathAttribute}]]" => $path]);
        }

        if ($depth !== null) {
            $query->andWhere(['<=', "{$tableName}.[[{$this->depthAttribute}]]", $this->owner->getAttribute($this->depthAttribute) + $depth]);
        }

        $orderBy = [];
        $orderBy["{$tableName}.[[{$this->depthAttribute}]]"] = SORT_ASC;
        if ($this->sortable !== false) {
            $orderBy["{$tableName}.[[{$this->behavior->sortAttribute}]]"] = SORT_ASC;
        }
        $orderBy["{$tableName}.[[{$this->itemAttribute}]]"]  = SORT_ASC;

        $query
            ->andWhere($this->treeCondition())
            ->addOrderBy($orderBy);
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
            ['like', "leaves.[[{$this->pathAttribute}]]",  new Expression($this->concatExpression(["{$tableName}.[[{$this->pathAttribute}]]", ':delimiter']), [':delimiter' => $this->delimiter . '%'])],
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
     * @throws NotSupportedException
     */
    public function getPrev()
    {
        if ($this->sortable === false) {
            throw new NotSupportedException('prev() not allow if not set sortable');
        }
        $tableName = $this->owner->tableName();
        $query = $this->owner->find()
            ->andWhere(['like', "{$tableName}.[[{$this->pathAttribute}]]", $this->getLike($this->getParentPath()), false])
            ->andWhere(["{$tableName}.[[{$this->depthAttribute}]]" => $this->owner->getAttribute($this->depthAttribute)])
            ->andWhere(['<', "{$tableName}.[[{$this->behavior->sortAttribute}]]", $this->owner->getSortablePosition()])
            ->andWhere($this->treeCondition())
            ->orderBy(["{$tableName}.[[{$this->behavior->sortAttribute}]]" => SORT_DESC])
            ->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws NotSupportedException
     */
    public function getNext()
    {
        if ($this->sortable === false) {
            throw new NotSupportedException('prev() not allow if not set sortable');
        }
        $tableName = $this->owner->tableName();
        $query = $this->owner->find()
            ->andWhere(['like', "{$tableName}.[[{$this->pathAttribute}]]", $this->getLike($this->getParentPath()), false])
            ->andWhere(["{$tableName}.[[{$this->depthAttribute}]]" => $this->owner->getAttribute($this->depthAttribute)])
            ->andWhere(['>', "{$tableName}.[[{$this->behavior->sortAttribute}]]", $this->owner->getSortablePosition()])
            ->andWhere($this->treeCondition())
            ->orderBy(["{$tableName}.[[{$this->behavior->sortAttribute}]]" => SORT_ASC])
            ->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * Returns all sibilings of node.
     * 
     * @param bool $andSelf = false Include self node into result.
     * @return \yii\db\ActiveQuery
     */
    public function getSiblings($andSelf = false)
    {
        $tableName = $this->owner->tableName();
        $path = $this->getParentPath();
        $like = strtr($path . $this->delimiter, ['%' => '\%', '_' => '\_', '\\' => '\\\\']);

        $query = $this->owner->find()
            ->andWhere(['like', "{$tableName}.[[{$this->pathAttribute}]]", $like . '%', false])
            ->andWhere(['<=', "{$tableName}.[[{$this->depthAttribute}]]", $this->owner->{$this->depthAttribute}]);

        if (!$andSelf) {
            $query->andWhere(["!=", "{$tableName}.[[{$this->itemAttribute}]]", $this->owner->{$this->itemAttribute}]);
        }

        $orderBy = [];
        $orderBy["{$tableName}.[[{$this->depthAttribute}]]"] = SORT_ASC;
        if ($this->sortable !== false) {
            $orderBy["{$tableName}.[[{$this->behavior->sortAttribute}]]"] = SORT_ASC;
        }
        $orderBy["{$tableName}.[[{$this->itemAttribute}]]"] = SORT_ASC;

        $query
            ->andWhere($this->treeCondition())
            ->addOrderBy($orderBy);
        $query->multiple = true;
        return $query;
    }

    /**
     * @param bool $asArray = false
     * @return null|string|array
     */
    public function getParentPath($asArray = false)
    {
        return static::getParentPathInternal($this->owner->getAttribute($this->pathAttribute), $this->delimiter, $asArray);
    }

    /**
     * Populate children relations for self and all descendants
     *
     * @param int $depth = null
     * @param string|array $with = null
     * @return static
     */
    public function populateTree($depth = null, $with = null)
    {
        /** @var ActiveRecord[]|static[] $nodes */
        $query = $this->getDescendants($depth);
        if ($with) {
            $query->with($with);
        }
        $nodes = $query->all();

        $relates = [];
        foreach ($nodes as $node) {
            $path = $node->getParentPath(true);
            $key = array_pop($path);
            if (!isset($relates[$key])) {
                $relates[$key] = [];
            }
            $relates[$key][] = $node;
        }

        $ownerDepth = $this->owner->getAttribute($this->depthAttribute);
        $nodes[] = $this->owner;
        foreach ($nodes as $node) {
            $key = $node->getAttribute($this->itemAttribute);
            if (isset($relates[$key])) {
                $node->populateRelation('children', $relates[$key]);
            } elseif ($depth === null || $ownerDepth + $depth > $node->getAttribute($this->depthAttribute)) {
                $node->populateRelation('children', []);
            }
        }

        return $this->owner;
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
        if ($node->getIsNewRecord()) {
            return false;
        }
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
     * Need for paulzi/auto-tree
     */
    public function preDeleteWithChildren()
    {
        $this->operation = self::OPERATION_DELETE_ALL;
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
     * @param bool $middle
     * @return int
     */
    public function reorderChildren($middle = true)
    {
        /** @var ActiveRecord|SortableBehavior $item */
        $item = count($this->owner->children) > 0 ? $this->owner->children[0] : null;
        if ($item) {
            return $item->reorder($middle);
        } else {
            return 0;
        }
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
                $path = $this->getParentPath();
                $this->owner->setAttribute($this->pathAttribute, ($path !== null ? $path . $this->delimiter : null) . $item);
        }
    }

    /**
     * @throws Exception
     */
    public function afterInsert()
    {
        if ($this->operation === self::OPERATION_MAKE_ROOT && $this->treeAttribute !== null && $this->owner->getAttribute($this->treeAttribute) === null) {
            $id = $this->getPrimaryKeyValue();
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
            $id = $this->getPrimaryKeyValue();
            if ($this->operation === self::OPERATION_MAKE_ROOT) {
                $path = $id;
            } else {
                if ($this->operation === self::OPERATION_INSERT_BEFORE || $this->operation === self::OPERATION_INSERT_AFTER) {
                    $path = $this->node->getParentPath();
                } else {
                    $path = $this->node->getAttribute($this->pathAttribute);
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
     * @return string
     */
    protected function getPrimaryKeyValue()
    {
        $result = $this->owner->getPrimaryKey(true);
        return reset($result);
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
     * Make root operation internal handler
     */
    protected function makeRootInternal()
    {
        $item = $this->owner->getAttribute($this->itemAttribute);

        if ($item !== null) {
            $this->owner->setAttribute($this->pathAttribute, $item);
        }

        if ($this->sortable !== false) {
            $this->owner->setAttribute($this->behavior->sortAttribute, 0);
        }

        if ($this->treeAttribute !== null && !$this->owner->getDirtyAttributes([$this->treeAttribute]) && !$this->owner->getIsNewRecord()) {
            $this->owner->setAttribute($this->treeAttribute, $this->getPrimaryKeyValue());
        }

        $this->owner->setAttribute($this->depthAttribute, $this->rootDepthValue);
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

        if ($this->sortable !== false) {
            if ($append) {
                $this->behavior->moveLast();
            } else {
                $this->behavior->moveFirst();
            }
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
            $path = $this->node->getParentPath();
            $this->owner->setAttribute($this->pathAttribute, $path . $this->delimiter . $item);
        }

        $this->owner->setAttribute($this->depthAttribute, $this->node->getAttribute($this->depthAttribute));

        if ($this->treeAttribute !== null) {
            $this->owner->setAttribute($this->treeAttribute, $this->node->getAttribute($this->treeAttribute));
        }

        if ($this->sortable !== false) {
            if ($forward) {
                $this->behavior->moveAfter($this->node);
            } else {
                $this->behavior->moveBefore($this->node);
            }
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
        $update = [];
        $condition = [
            'and',
            ['like', "[[{$this->pathAttribute}]]", $this->getLike($path), false],
        ];
        if ($this->treeAttribute !== null) {
            $tree = isset($changedAttributes[$this->treeAttribute]) ? $changedAttributes[$this->treeAttribute] : $this->owner->getAttribute($this->treeAttribute);
            $condition[] = [$this->treeAttribute => $tree];
        }
        $params = [];

        if (isset($changedAttributes[$this->pathAttribute])) {
            $substringExpr = $this->substringExpression(
                "[[{$this->pathAttribute}]]",
                'LENGTH(:pathOld) + 1',
                "LENGTH([[{$this->pathAttribute}]]) - LENGTH(:pathOld)"
            );
            $update[$this->pathAttribute] = new Expression($this->concatExpression([':pathNew', $substringExpr]));
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
     * @param string $path
     * @param string $delimiter
     * @param bool $asArray = false
     * @return null|string|array
     */
    protected static function getParentPathInternal($path, $delimiter, $asArray = false)
    {
        $path = explode($delimiter, $path);
        array_pop($path);
        if ($asArray) {
            return $path;
        }
        return count($path) > 0 ? implode($delimiter, $path) : null;
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

    /**
     * @return \yii\db\ActiveQuery
     */
    protected function getSortableQuery()
    {
        switch ($this->operation) {
            case self::OPERATION_PREPEND_TO:
            case self::OPERATION_APPEND_TO:
                $path  = $this->node->getAttribute($this->pathAttribute);
                $depth = $this->node->getAttribute($this->depthAttribute) + 1;
                break;

            case self::OPERATION_INSERT_BEFORE:
            case self::OPERATION_INSERT_AFTER:
                $path  = $this->node->getParentPath();
                $depth = $this->node->getAttribute($this->depthAttribute);
                break;

            default:
                $path  = $this->getParentPath();
                $depth = $this->owner->getAttribute($this->depthAttribute);
        }
        $tableName = $this->owner->tableName();

        return $this->owner->find()
            ->andWhere($this->treeCondition())
            ->andWhere($path !== null ? ['like', "{$tableName}.[[{$this->pathAttribute}]]", $this->getLike($path), false] : '1=0')
            ->andWhere(["{$tableName}.[[{$this->depthAttribute}]]" => $depth]);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function getLike($path)
    {
        return strtr($path . $this->delimiter, ['%' => '\%', '_' => '\_', '\\' => '\\\\']) . '%';
    }

    /**
     * @param array $items
     * @return string
     */
    protected function concatExpression($items)
    {
        if ($this->owner->getDb()->driverName === 'sqlite' || $this->owner->getDb()->driverName === 'pgsql') {
            return implode(' || ', $items);
        }
        return 'CONCAT(' . implode(',', $items) . ')';
    }

    protected function substringExpression($string, $from, $length)
    {
        if ($this->owner->getDb()->driverName === 'sqlite') {
            return "SUBSTR({$string}, {$from}, {$length})";
        }
        return "SUBSTRING({$string}, {$from}, {$length})";
    }
}
