<?php

namespace asb\yii2\common_2_170212\rbac;

use Yii;
use yii\rbac\Rule;

/**
 * Checks if user is Root
 */
class IsRootRule extends Rule
{
    public $name = 'ruleIsRoot';

    protected $role  = 'roleRoot';

    public function execute($userId, $item, $params)
    {//echo __METHOD__;var_dump($userId);var_dump($item);var_dump($params);

        $identityClass = Yii::$app->user->identityClass;
        $identity = $identityClass::findIdentity($userId);//var_dump($identity);exit;
        if (empty($identity)) return false;

        //$hasRole = $identity->hasRole($this->role);//var_dump($hasRole);exit;
        $hasRoleRoot = Yii::$app->authManager->getAssignment($this->role, $userId);
        if (empty($hasRoleRoot)) return false;

        //echo 'IsRootRule OK<br>';exit;
        return true;
    }
}
