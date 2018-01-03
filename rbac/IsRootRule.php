<?php

namespace asb\yii2\common_2_170212\rbac;

use Yii;
use yii\rbac\Rule;

/**
 * Checks if user is Root
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class IsRootRule extends Rule
{
    public $name = 'ruleIsRoot';

    protected $role  = 'roleRoot';

    public function execute($userId, $item, $params)
    {
        $identityClass = Yii::$app->user->identityClass;
        $identity = $identityClass::findIdentity($userId);
        if (empty($identity)) return false;

        //$hasRole = $identity->hasRole($this->role);
        $hasRoleRoot = Yii::$app->authManager->getAssignment($this->role, $userId);
        if (empty($hasRoleRoot)) return false;

        return true;
    }
}
