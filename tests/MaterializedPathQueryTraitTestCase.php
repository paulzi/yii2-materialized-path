<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedPath\tests;

use paulzi\materializedPath\tests\models\Node;
use paulzi\materializedPath\tests\models\AttributeModeNode;
use paulzi\materializedPath\tests\models\MultipleTreeNode;
use Yii;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
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