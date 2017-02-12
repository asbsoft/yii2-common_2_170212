<?php

use asb\yii2\common_2_170212\web\UserIdentity;

use yii\db\Migration;

class m160524_093500_addrole_to_admin extends Migration
{
    protected $adminUserId = 100; //!! tune
    protected $roleAdmin = 'roleAdmin';

    public function init()
    {
        parent::init();

        //Yii::setAlias('@asb/yii2/common_2_170212', '@vendor/asbsoft/yii2-common_2_170212');
        Yii::setAlias('@asb/yii2/cms_3_170211', '@vendor/asbsoft/yii2-cms_3_170211');
        Yii::setAlias('@asb/yii2/modules', '@vendor/asbsoft/yii2module');
    }

    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $roleAdmin = $auth->getRole($this->roleAdmin);
        //$user = UserIdentity::findIdentity($this->adminUserId);
        //$auth->assign($roleAdmin, $user->getId());
        $auth->assign($roleAdmin, $this->adminUserId);
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $roleAdmin = $auth->getRole($this->roleAdmin);
        $auth->revoke($roleAdmin, $this->adminUserId);
    }

}
