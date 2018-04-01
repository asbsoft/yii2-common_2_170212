<?php

use yii\db\Migration;

class m170112_201001_insert_user_root extends Migration
{
    const NAME = 'root';
    const PSW  = 'toor4321';
    const ID = 90;

    const TABLE_BASENAME = 'user';

    protected $tableName;

    public function init()
    {
        parent::init();

        $this->tableName = $this->db->tablePrefix . self::TABLE_BASENAME;
    }
    
    public function safeUp()
    {
        $now = time();

        $this->insert($this->tableName, [
            'id' => self::ID,
            'username' => self::NAME,
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash(self::PSW),
            'password_reset_token' => null,
            'email' => self::NAME . '@example.com',
            'status' => 10, //STATUS_ACTIVE
            'created_at' => $now,
            'updated_at' => $now,
        ]);

    }

    public function safeDown()
    {
        $this->delete($this->tableName, [
            'id' => self::ID,
        ]);
    }

}
