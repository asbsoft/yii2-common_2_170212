<?php

use asb\yii2\web\UserIdentity;

use yii\db\Migration;

//Yii::setAlias('@asb/yii2/common_2_170212', '@vendor/asbsoft/yii2-common_2_170212'); // uncomment if need

class m160524_093501_addrole_to_root extends Migration
{
    protected $rootUserId = 90; //!! tune
    protected $roleRoot = 'roleRoot';

    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $roleAdmin = $auth->getRole($this->roleRoot);
        $auth->assign($roleAdmin, $this->rootUserId);
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $roleAdmin = $auth->getRole($this->roleRoot);
        $auth->revoke($roleAdmin, $this->rootUserId);
    }

}
