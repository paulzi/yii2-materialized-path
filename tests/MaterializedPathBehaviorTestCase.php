<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedpath\tests;

use paulzi\materializedpath\tests\migrations\TestMigration;
use paulzi\materializedpath\tests\models\AttributeModeNode;
use paulzi\materializedpath\tests\models\MultipleTreeNode;
use paulzi\materializedpath\tests\models\Node;
use Yii;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
class MaterializedPathBehaviorTestCase extends BaseTestCase
{
    public function testGetParents()
    {
        $data = [1, 3];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(9)->parents));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::findOne(9)->parents));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::findOne(9)->parents));

        $data = [17];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(24)->getParents(1)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::findOne(24)->getParents(1)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::findOne(24)->getParents(1)->all()));
    }

    public function testGetParent()
    {
        $data = 15;
        $this->assertEquals($data, Node::findOne(18)->parent->id);
        $this->assertEquals($data, AttributeModeNode::findOne(18)->parent->id);
        $this->assertEquals($data, MultipleTreeNode::findOne(18)->parent->id);

        $data = null;
        $this->assertEquals($data, Node::findOne(1)->getParent()->one());
        $this->assertEquals($data, AttributeModeNode::findOne(1)->getParent()->one());
        $this->assertEquals($data, MultipleTreeNode::findOne(1)->getParent()->one());
    }

    public function testGetRoot()
    {
        $data = 1;
        $this->assertEquals($data, Node::findOne(12)->root->id);
        $this->assertEquals($data, AttributeModeNode::findOne(12)->root->id);
        $this->assertEquals($data, MultipleTreeNode::findOne(12)->root->id);

        $data = 1;
        $this->assertEquals($data, Node::findOne(1)->getRoot()->one()->id);
        $this->assertEquals($data, AttributeModeNode::findOne(1)->getRoot()->one()->id);
        $this->assertEquals($data, MultipleTreeNode::findOne(1)->getRoot()->one()->id);
    }

    public function testGetDescendants()
    {
        $data = [2, 3, 4, 5, 8, 11, 12, 6, 9, 10, 7, 13];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(1)->descendants));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::findOne(1)->descendants));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::findOne(1)->descendants));

        $data = [14, 15, 16, 17];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(14)->getDescendants(1, true)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::findOne(14)->getDescendants(1, true)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::findOne(14)->getDescendants(1, true)->all()));
    }

    public function testGetChildren()
    {
        $data = [24, 25, 26];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(17)->children));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::findOne(17)->children));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::findOne(17)->children));

        $data = [];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(13)->getChildren()->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::findOne(13)->getChildren()->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::findOne(13)->getChildren()->all()));
    }

    public function testGetLeaves()
    {
        $data = [18, 21, 24, 25, 19, 22, 23, 20, 26];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(14)->leaves));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::findOne(14)->leaves));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::findOne(14)->leaves));

        $data = [];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::findOne(14)->getLeaves(1)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::findOne(14)->getLeaves(1)->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::findOne(14)->getLeaves(1)->all()));
    }

    public function testGetPrev()
    {
        $data = 12;
        $this->assertEquals($data, Node::findOne(13)->prev->id);
        $this->assertEquals($data, AttributeModeNode::findOne(13)->prev->id);
        $this->assertEquals($data, MultipleTreeNode::findOne(13)->prev->id);

        $data = null;
        $this->assertEquals($data, Node::findOne(15)->getPrev()->one());
        $this->assertEquals($data, AttributeModeNode::findOne(15)->getPrev()->one());
        $this->assertEquals($data, MultipleTreeNode::findOne(15)->getPrev()->one());
    }

    public function testGetNext()
    {
        $data = 23;
        $this->assertEquals($data, Node::findOne(22)->next->id);
        $this->assertEquals($data, AttributeModeNode::findOne(22)->next->id);
        $this->assertEquals($data, MultipleTreeNode::findOne(22)->next->id);

        $data = null;
        $this->assertEquals($data, Node::findOne(4)->getNext()->one());
        $this->assertEquals($data, AttributeModeNode::findOne(4)->getNext()->one());
        $this->assertEquals($data, MultipleTreeNode::findOne(4)->getNext()->one());
    }

    public function testIsRoot()
    {
        $this->assertTrue(Node::findOne(1)->isRoot());
        $this->assertFalse(Node::findOne(3)->isRoot());

        $this->assertTrue(AttributeModeNode::findOne(1)->isRoot());
        $this->assertFalse(AttributeModeNode::findOne(3)->isRoot());

        $this->assertTrue(MultipleTreeNode::findOne(1)->isRoot());
        $this->assertFalse(MultipleTreeNode::findOne(3)->isRoot());
    }

    public function testIsChildOf()
    {
        $this->assertTrue(Node::findOne(10)->isChildOf(Node::findOne(1)));
        $this->assertTrue(Node::findOne(5)->isChildOf(Node::findOne(2)));
        $this->assertFalse(Node::findOne(9)->isChildOf(Node::findOne(12)));
        $this->assertFalse(Node::findOne(9)->isChildOf(Node::findOne(10)));
        $this->assertFalse(Node::findOne(9)->isChildOf(Node::findOne(9)));
        $this->assertFalse(Node::findOne(9)->isChildOf(Node::findOne(16)));

        $this->assertTrue(AttributeModeNode::findOne(10)->isChildOf(AttributeModeNode::findOne(1)));
        $this->assertTrue(AttributeModeNode::findOne(5)->isChildOf(AttributeModeNode::findOne(2)));
        $this->assertFalse(AttributeModeNode::findOne(9)->isChildOf(AttributeModeNode::findOne(12)));
        $this->assertFalse(AttributeModeNode::findOne(9)->isChildOf(AttributeModeNode::findOne(10)));
        $this->assertFalse(AttributeModeNode::findOne(9)->isChildOf(AttributeModeNode::findOne(9)));
        $this->assertFalse(AttributeModeNode::findOne(9)->isChildOf(AttributeModeNode::findOne(16)));

        $this->assertTrue(MultipleTreeNode::findOne(10)->isChildOf(MultipleTreeNode::findOne(1)));
        $this->assertTrue(MultipleTreeNode::findOne(5)->isChildOf(MultipleTreeNode::findOne(2)));
        $this->assertFalse(MultipleTreeNode::findOne(9)->isChildOf(MultipleTreeNode::findOne(12)));
        $this->assertFalse(MultipleTreeNode::findOne(9)->isChildOf(MultipleTreeNode::findOne(10)));
        $this->assertFalse(MultipleTreeNode::findOne(9)->isChildOf(MultipleTreeNode::findOne(9)));
        $this->assertFalse(MultipleTreeNode::findOne(9)->isChildOf(MultipleTreeNode::findOne(16)));
    }

    public function testIsLeaf()
    {
        $this->assertTrue(Node::findOne(5)->isLeaf());
        $this->assertFalse(Node::findOne(2)->isLeaf());

        $this->assertTrue(AttributeModeNode::findOne(5)->isLeaf());
        $this->assertFalse(AttributeModeNode::findOne(2)->isLeaf());

        $this->assertTrue(MultipleTreeNode::findOne(5)->isLeaf());
        $this->assertFalse(MultipleTreeNode::findOne(2)->isLeaf());
    }

    public function testMakeRootInsert()
    {
        (new TestMigration())->up();
        $dataSet = new ArrayDataSet(require(__DIR__ . '/data/empty.php'));
        $this->getDatabaseTester()->setDataSet($dataSet);
        $this->getDatabaseTester()->onSetUp();

        $node = new Node(['slug' => 'r']);
        $this->assertTrue($node->makeRoot()->save());

        $node = new AttributeModeNode(['slug' => 'r']);
        $this->assertTrue($node->makeRoot()->save());

        $node = new MultipleTreeNode(['slug' => 'r']);
        $this->assertTrue($node->makeRoot()->save());

        $node = new MultipleTreeNode([
            'slug' => 'r',
            'tree' => 100,
        ]);
        $this->assertTrue($node->makeRoot()->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-make-root-insert.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testMakeRootUpdate()
    {
        $node = Node::findOne(9);
        $this->assertTrue($node->makeRoot()->save());

        $node = AttributeModeNode::findOne(15);
        $this->assertTrue($node->makeRoot()->save());

        $node = MultipleTreeNode::findOne(17);
        $this->assertTrue($node->makeRoot()->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-make-root-update.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToInsertInNoEmpty()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->prependTo(Node::findOne(1))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->prependTo(AttributeModeNode::findOne(1))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->prependTo(MultipleTreeNode::findOne(1))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-insert-in-no-empty.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToInsertInEmpty()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->prependTo(Node::findOne(22))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->prependTo(AttributeModeNode::findOne(22))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->prependTo(MultipleTreeNode::findOne(22))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-insert-in-empty.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateSameNode()
    {
        $node = Node::findOne(4);
        $this->assertTrue($node->prependTo(Node::findOne(1))->save());

        $node = AttributeModeNode::findOne(4);
        $this->assertTrue($node->prependTo(AttributeModeNode::findOne(1))->save());

        $node = MultipleTreeNode::findOne(4);
        $this->assertTrue($node->prependTo(MultipleTreeNode::findOne(1))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-update-same-node.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateDeep()
    {
        $node = Node::findOne(16);
        $this->assertTrue($node->prependTo(Node::findOne(19))->save());

        $node = AttributeModeNode::findOne(16);
        $this->assertTrue($node->prependTo(AttributeModeNode::findOne(19))->save());

        $node = MultipleTreeNode::findOne(16);
        $this->assertTrue($node->prependTo(MultipleTreeNode::findOne(19))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-update-deep.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateOut()
    {
        $node = Node::findOne(12);
        $this->assertTrue($node->prependTo(Node::findOne(1))->save());

        $node = AttributeModeNode::findOne(12);
        $this->assertTrue($node->prependTo(AttributeModeNode::findOne(1))->save());

        $node = MultipleTreeNode::findOne(12);
        $this->assertTrue($node->prependTo(MultipleTreeNode::findOne(1))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-update-out.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateAnotherTree()
    {
        $node = Node::findOne(17);
        $this->assertTrue($node->prependTo(Node::findOne(2))->save());

        $node = AttributeModeNode::findOne(17);
        $this->assertTrue($node->prependTo(AttributeModeNode::findOne(2))->save());

        $node = MultipleTreeNode::findOne(17);
        $this->assertTrue($node->prependTo(MultipleTreeNode::findOne(2))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-prepend-to-update-another-tree.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testPrependToUpdateSelf()
    {
        $node = Node::findOne(2);
        $this->assertTrue($node->prependTo(Node::findOne(1))->save());

        $node = AttributeModeNode::findOne(2);
        $this->assertTrue($node->prependTo(AttributeModeNode::findOne(1))->save());

        $node = MultipleTreeNode::findOne(2);
        $this->assertTrue($node->prependTo(MultipleTreeNode::findOne(1))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/data.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testPrependToInsertExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->prependTo(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testPrependToUpdateExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = Node::findOne(2);
        $node->prependTo(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testPrependToUpdateExceptionIsRaisedWhenTargetIsSame()
    {
        $node = Node::findOne(3);
        $node->prependTo(Node::findOne(3))->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testPrependToUpdateExceptionIsRaisedWhenTargetIsChild()
    {
        $node = Node::findOne(17);
        $node->prependTo(Node::findOne(24))->save();
    }

    public function testAppendToInsertInNoEmpty()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->appendTo(Node::findOne(1))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->appendTo(AttributeModeNode::findOne(1))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->appendTo(MultipleTreeNode::findOne(1))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-insert-in-no-empty.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToInsertInEmpty()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->appendTo(Node::findOne(22))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->appendTo(AttributeModeNode::findOne(22))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->appendTo(MultipleTreeNode::findOne(22))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-insert-in-empty.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateSameNode()
    {
        $node = Node::findOne(2);
        $this->assertTrue($node->appendTo(Node::findOne(1))->save());

        $node = AttributeModeNode::findOne(2);
        $this->assertTrue($node->appendTo(AttributeModeNode::findOne(1))->save());

        $node = MultipleTreeNode::findOne(2);
        $this->assertTrue($node->appendTo(MultipleTreeNode::findOne(1))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-update-same-node.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateDeep()
    {
        $node = Node::findOne(16);
        $this->assertTrue($node->appendTo(Node::findOne(19))->save());

        $node = AttributeModeNode::findOne(16);
        $this->assertTrue($node->appendTo(AttributeModeNode::findOne(19))->save());

        $node = MultipleTreeNode::findOne(16);
        $this->assertTrue($node->appendTo(MultipleTreeNode::findOne(19))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-update-deep.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateOut()
    {
        $node = Node::findOne(12);
        $this->assertTrue($node->appendTo(Node::findOne(1))->save());

        $node = AttributeModeNode::findOne(12);
        $this->assertTrue($node->appendTo(AttributeModeNode::findOne(1))->save());

        $node = MultipleTreeNode::findOne(12);
        $this->assertTrue($node->appendTo(MultipleTreeNode::findOne(1))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-update-out.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateAnotherTree()
    {
        $node = Node::findOne(17);
        $this->assertTrue($node->appendTo(Node::findOne(2))->save());

        $node = AttributeModeNode::findOne(17);
        $this->assertTrue($node->appendTo(AttributeModeNode::findOne(2))->save());

        $node = MultipleTreeNode::findOne(17);
        $this->assertTrue($node->appendTo(MultipleTreeNode::findOne(2))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-append-to-update-another-tree.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAppendToUpdateSelf()
    {
        $node = Node::findOne(4);
        $this->assertTrue($node->appendTo(Node::findOne(1))->save());

        $node = AttributeModeNode::findOne(4);
        $this->assertTrue($node->appendTo(AttributeModeNode::findOne(1))->save());

        $node = MultipleTreeNode::findOne(4);
        $this->assertTrue($node->appendTo(MultipleTreeNode::findOne(1))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/data.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testAppendToInsertExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->appendTo(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testAppendToUpdateExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = Node::findOne(2);
        $node->appendTo(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testAppendToUpdateExceptionIsRaisedWhenTargetIsSame()
    {
        $node = Node::findOne(3);
        $node->appendTo(Node::findOne(3))->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testAppendToUpdateExceptionIsRaisedWhenTargetIsChild()
    {
        $node = Node::findOne(17);
        $node->appendTo(Node::findOne(24))->save();
    }

    public function testInsertBeforeInsertNoGap()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(Node::findOne(4))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(AttributeModeNode::findOne(4))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(MultipleTreeNode::findOne(4))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-insert-no-gap.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeInsertGap()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(Node::findOne(10))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(AttributeModeNode::findOne(10))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(MultipleTreeNode::findOne(10))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-insert-gap.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeInsertBegin()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(Node::findOne(24))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(AttributeModeNode::findOne(24))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->insertBefore(MultipleTreeNode::findOne(24))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-insert-begin.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }


    public function testInsertBeforeUpdateSameNode()
    {
        $node = Node::findOne(2);
        $this->assertTrue($node->insertBefore(Node::findOne(4))->save());

        $node = AttributeModeNode::findOne(2);
        $this->assertTrue($node->insertBefore(AttributeModeNode::findOne(4))->save());

        $node = MultipleTreeNode::findOne(2);
        $this->assertTrue($node->insertBefore(MultipleTreeNode::findOne(4))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-update-same-node.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeUpdateNext()
    {
        $node = Node::findOne(3);
        $this->assertTrue($node->insertBefore(Node::findOne(4))->save());

        $node = AttributeModeNode::findOne(3);
        $this->assertTrue($node->insertBefore(AttributeModeNode::findOne(4))->save());

        $node = MultipleTreeNode::findOne(3);
        $this->assertTrue($node->insertBefore(MultipleTreeNode::findOne(4))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/data.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertBeforeUpdateAnotherTree()
    {
        $node = Node::findOne(14);
        $this->assertTrue($node->insertBefore(Node::findOne(10))->save());

        $node = AttributeModeNode::findOne(14);
        $this->assertTrue($node->insertBefore(AttributeModeNode::findOne(10))->save());

        $node = MultipleTreeNode::findOne(14);
        $this->assertTrue($node->insertBefore(MultipleTreeNode::findOne(10))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-before-update-another-tree.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertBeforeInsertExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->insertBefore(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertBeforeInsertExceptionIsRaisedWhenTargetIsRoot()
    {
        $node = new Node(['name' => 'new']);
        $node->insertBefore(Node::findOne(1))->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertBeforeUpdateExceptionIsRaisedWhenTargetIsSame()
    {
        $node = Node::findOne(3);
        $node->insertBefore(Node::findOne(3))->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertBeforeUpdateExceptionIsRaisedWhenTargetIsChild()
    {
        $node = Node::findOne(17);
        $node->insertBefore(Node::findOne(24))->save();
    }

    public function testInsertAfterInsertNoGap()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(Node::findOne(2))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(AttributeModeNode::findOne(2))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(MultipleTreeNode::findOne(2))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-insert-no-gap.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterInsertGap()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(Node::findOne(11))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(AttributeModeNode::findOne(11))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(MultipleTreeNode::findOne(11))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-insert-gap.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterInsertEnd()
    {
        $node = new Node(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(Node::findOne(23))->save());

        $node = new AttributeModeNode(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(AttributeModeNode::findOne(23))->save());

        $node = new MultipleTreeNode(['slug' => 'new']);
        $this->assertTrue($node->insertAfter(MultipleTreeNode::findOne(23))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-insert-end.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterUpdateSameNode()
    {
        $node = Node::findOne(4);
        $this->assertTrue($node->insertAfter(Node::findOne(2))->save());

        $node = AttributeModeNode::findOne(4);
        $this->assertTrue($node->insertAfter(AttributeModeNode::findOne(2))->save());

        $node = MultipleTreeNode::findOne(4);
        $this->assertTrue($node->insertAfter(MultipleTreeNode::findOne(2))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-update-same-node.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterUpdatePrev()
    {
        $node = Node::findOne(3);
        $this->assertTrue($node->insertAfter(Node::findOne(2))->save());

        $node = AttributeModeNode::findOne(3);
        $this->assertTrue($node->insertAfter(AttributeModeNode::findOne(2))->save());

        $node = MultipleTreeNode::findOne(3);
        $this->assertTrue($node->insertAfter(MultipleTreeNode::findOne(2))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/data.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testInsertAfterUpdateAnotherTree()
    {
        $node = Node::findOne(14);
        $this->assertTrue($node->insertAfter(Node::findOne(11))->save());

        $node = AttributeModeNode::findOne(14);
        $this->assertTrue($node->insertAfter(AttributeModeNode::findOne(11))->save());

        $node = MultipleTreeNode::findOne(14);
        $this->assertTrue($node->insertAfter(MultipleTreeNode::findOne(11))->save());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-insert-after-update-another-tree.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertAfterInsertExceptionIsRaisedWhenTargetIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->insertAfter(new Node())->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertAfterInsertExceptionIsRaisedWhenTargetIsRoot()
    {
        $node = new Node(['slug' => 'new']);
        $node->insertAfter(Node::findOne(1))->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertAfterUpdateExceptionIsRaisedWhenTargetIsSame()
    {
        $node = Node::findOne(3);
        $node->insertAfter(Node::findOne(3))->save();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testInsertAfterUpdateExceptionIsRaisedWhenTargetIsChild()
    {
        $node = Node::findOne(17);
        $node->insertAfter(Node::findOne(24))->save();
    }

    public function testDelete()
    {
        $this->assertEquals(1, Node::findOne(3)->delete());

        $this->assertEquals(1, AttributeModeNode::findOne(3)->delete());

        $this->assertEquals(1, MultipleTreeNode::findOne(3)->delete());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-delete.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testDeleteRoot()
    {
        Node::findOne(1)->delete();
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testDeleteExceptionIsRaisedWhenNodeIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->delete();
    }

    public function testDeleteWithChildren()
    {
        $this->assertEquals(4, Node::findOne(3)->deleteWithChildren());

        $this->assertEquals(4, AttributeModeNode::findOne(3)->deleteWithChildren());

        $this->assertEquals(4, MultipleTreeNode::findOne(3)->deleteWithChildren());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-delete-with-children.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeleteWithChildrenRoot()
    {
        $this->assertEquals(13, Node::findOne(1)->deleteWithChildren());

        $this->assertEquals(13, AttributeModeNode::findOne(1)->deleteWithChildren());

        $this->assertEquals(13, MultipleTreeNode::findOne(1)->deleteWithChildren());

        $dataSet = $this->getConnection()->createDataSet(['tree', 'attribute_mode_tree', 'multiple_tree']);
        $expectedDataSet = new ArrayDataSet(require(__DIR__ . '/data/test-delete-with-children-root.php'));
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @expectedException \yii\base\Exception
     */
    public function testDeleteWithChildrenExceptionIsRaisedWhenNodeIsNewRecord()
    {
        $node = new Node(['slug' => 'new']);
        $node->deleteWithChildren();
    }

    /**
     * @expectedException \yii\base\NotSupportedException
     */
    public function testExceptionIsRaisedWhenInsertIsCalled()
    {
        $node = new Node(['slug' => 'new']);
        $node->insert();
    }

    public function testUpdate()
    {
        $node = Node::findOne(3);
        $node->slug = 'update';
        $this->assertEquals(1, $node->update());
    }

}