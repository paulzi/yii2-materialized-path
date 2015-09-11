<?php

namespace tests;

use tests\models\Node;
use tests\models\AttributeModeNode;
use tests\models\MultipleTreeNode;
use Yii;

class MaterializedPathQueryTraitTestCase extends BaseTestCase
{
    public function testRoots()
    {
        $data = [1, 14];
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, Node::find()->roots()->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, AttributeModeNode::find()->roots()->all()));
        $this->assertEquals($data, array_map(function ($value) { return $value->id; }, MultipleTreeNode::find()->roots()->all()));
    }
}