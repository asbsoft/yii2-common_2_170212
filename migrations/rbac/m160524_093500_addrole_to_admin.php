<?php

use yii\db\Migration;

//Yii::setAlias('@asb/yii2/common_2_170212', '@vendor/asbsoft/yii2-common_2_170212'); // uncomment if need

class m160524_093500_addrole_to_admin extends Migration
{
    protected $adminUserId = 100; //!! tune
    protected $roleAdmin = 'roleAdmin';

    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $roleAdmin = $auth->getRole($this->roleAdmin);
        $auth->assign($roleAdmin, $this->adminUserId);
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $roleAdmin = $auth->getRole($this->roleAdmin);
        $auth->revoke($roleAdmin, $this->adminUserId);
    }

}
