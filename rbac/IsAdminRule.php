<?php

namespace asb\yii2\common_2_170212\rbac;

use asb\yii2\common_2_170212\web\UserIdentity;

use Yii;
use yii\rbac\Rule;

/**
 * Checks if user is admin
 */
class IsAdminRule extends Rule
{
    public $name = 'ruleIsAdmin';

    //protected $group = 'admins';

    public function execute($userId, $item, $params)
    {//echo __METHOD__;var_dump($userId);var_dump($item);var_dump($params);
        
        $identity = UserIdentity::findIdentity($userId);//var_dump($identity->attributes);
        if (empty($identity)) return false;

        //if (!$identity->hasRole('roleAdmin') && !$identity->hasRole('roleRoot')) return false;
        $hasRoleRoot = Yii::$app->authManager->getAssignment('roleRoot', $userId);
        $hasRoleAdmin = Yii::$app->authManager->getAssignment('roleAdmin', $userId);
        if (empty($hasRoleRoot) && empty($hasRoleAdmin)) return false;

        //$groups = $identity->getGroups();//var_dump($groups); //todo
        //if (!in_array($this->group, $groups)) return false;
        
        //echo 'IsAdminRule OK<br>';
        return true;
    }
}
