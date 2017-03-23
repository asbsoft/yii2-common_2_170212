<?php

namespace asb\yii2\common_2_170212\rbac;

use asb\yii2\common_2_170212\web\UserIdentity;

use Yii;
use yii\rbac\Rule;

/**
 * Checks if user is Root
 */
class IsRootRule extends Rule
{
    public $name = 'ruleIsRoot';

    protected $role  = 'roleRoot';

    //protected $group = 'roots';

    public function execute($userId, $item, $params)
    {//echo __METHOD__;var_dump($userId);var_dump($item);var_dump($params);

        $identity = UserIdentity::findIdentity($userId);//var_dump($identity->attributes);exit;
        if (empty($identity)) return false;

        //$hasRole = $identity->hasRole($this->role);//var_dump($hasRole);exit;
        $hasRoleRoot = Yii::$app->authManager->getAssignment($this->role, $userId);
        if (empty($hasRoleRoot)) return false;

        //$groups = $identity->getGroups();//var_dump($groups); //todo
        //if (!in_array($this->group, $groups)) return false;

        //echo 'IsRootRule OK<br>';
        return true;
    }
}
