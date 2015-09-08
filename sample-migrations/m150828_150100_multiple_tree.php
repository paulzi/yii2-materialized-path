<?php

use yii\db\Schema;
use yii\db\Migration;

class m150828_150100_multiple_tree extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%multiple_tree}}', [
            'id'    => Schema::TYPE_PK,
            'tree'  => Schema::TYPE_INTEGER . ' NULL',
            'path'  => Schema::TYPE_STRING . ' NULL',
            'depth' => Schema::TYPE_INTEGER . ' NOT NULL',
            'sort'  => Schema::TYPE_INTEGER . ' NOT NULL',
        ], $tableOptions);
        $this->createIndex('path', '{{%multiple_tree}}', ['tree', 'path']);
    }
}
