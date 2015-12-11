<?php
/**
 * @link https://github.com/paulzi/yii2-materialized-path
 * @copyright Copyright (c) 2015 PaulZi <pavel.zimakoff@gmail.com>
 * @license MIT (https://github.com/paulzi/yii2-materialized-path/blob/master/LICENSE)
 */

namespace paulzi\materializedPath\tests\migrations;

use yii\db\Schema;
use yii\db\Migration;

/**
 * @author PaulZi <pavel.zimakoff@gmail.com>
 */
class TestMigration extends Migration
{
    public function up()
    {
        ob_start();
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // tree
        if ($this->db->getTableSchema('{{%tree}}', true) !== null) {
            $this->dropTable('{{%tree}}');
        }
        $this->createTable('{{%tree}}', [
            'id'    => Schema::TYPE_PK,
            'path'  => Schema::TYPE_STRING . ' NULL',
            'depth' => Schema::TYPE_INTEGER . ' NOT NULL',
            'sort'  => Schema::TYPE_INTEGER . ' NOT NULL',
            'slug'  => Schema::TYPE_STRING . ' NOT NULL',
        ], $tableOptions);
        $this->createIndex('path', '{{%tree}}', ['path']);

        // attribute mode tree
        if ($this->db->getTableSchema('{{%attribute_mode_tree}}', true) !== null) {
            $this->dropTable('{{%attribute_mode_tree}}');
        }
        $this->createTable('{{%attribute_mode_tree}}', [
            'id'    => Schema::TYPE_PK,
            'path'  => Schema::TYPE_STRING . ' NULL',
            'depth' => Schema::TYPE_INTEGER . ' NOT NULL',
            'sort'  => Schema::TYPE_INTEGER . ' NOT NULL',
            'slug'  => Schema::TYPE_STRING . ' NOT NULL',
        ], $tableOptions);
        $this->createIndex('path2', '{{%attribute_mode_tree}}', ['path']);

        // multiple tree
        if ($this->db->getTableSchema('{{%multiple_tree}}', true) !== null) {
            $this->dropTable('{{%multiple_tree}}');
        }
        $this->createTable('{{%multiple_tree}}', [
            'id'    => Schema::TYPE_PK,
            'tree'  => Schema::TYPE_INTEGER . ' NULL',
            'path'  => Schema::TYPE_STRING . ' NULL',
            'depth' => Schema::TYPE_INTEGER . ' NOT NULL',
            'sort'  => Schema::TYPE_INTEGER . ' NOT NULL',
            'slug'  => Schema::TYPE_STRING . ' NOT NULL',
        ], $tableOptions);
        $this->createIndex('path3', '{{%multiple_tree}}', ['tree', 'path']);

        // update cache (sqlite bug)
        $this->db->getSchema()->getTableSchema('{{%tree}}', true);
        $this->db->getSchema()->getTableSchema('{{%attribute_mode_tree}}', true);
        $this->db->getSchema()->getTableSchema('{{%multiple_tree}}', true);
        ob_end_clean();
    }
}
