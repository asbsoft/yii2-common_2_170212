<?php

use yii\db\Migration;

class m170112_200501_create_user_table extends Migration
{
    const TABLE_BASENAME = 'user';
    
    protected $tableName;
    protected $idxNamePrefix;

    public function init()
    {
        parent::init();

        $this->tableName = $this->db->tablePrefix . self::TABLE_BASENAME;

        $this->idxNamePrefix = 'idx_' . $this->tableName;
    }

    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($this->tableName, [
            'id'       => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'password_reset_token' => $this->string()->unique(),
            'email'      => $this->string()->notNull()->unique(),
            'status'     => $this->smallInteger()->notNull()->defaultValue(0), //STATUS_DELETED
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex("{$this->idxNamePrefix}-username",  $this->tableName, 'username');
        $this->createIndex("{$this->idxNamePrefix}-status",    $this->tableName, 'status');
        $this->createIndex("{$this->idxNamePrefix}-auth-key",  $this->tableName, 'auth_key');
    }

    public function safeDown()
    {
        $this->dropTable($this->tableName);
    }
}
