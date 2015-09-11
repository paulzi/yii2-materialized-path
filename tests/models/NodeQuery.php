<?php

namespace tests\models;

use paulzi\materializedpath\MaterializedPathQueryTrait;

class NodeQuery extends \yii\db\ActiveQuery
{
    use MaterializedPathQueryTrait;
}