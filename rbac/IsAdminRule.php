<?php

namespace asb\yii2\common_2_170212\rbac;

use Yii;
use yii\rbac\Rule;

/**
 * Checks if user is admin.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class IsAdminRule extends Rule
{
    public $name = 'ruleIsAdmin';

    public function execute($userId, $item, $params)
    {
        $identityClass = Yii::$app->user->identityClass;
        $identity = $identityClass::findIdentity($userId);
        if (empty($identity)) return false;

        //if (!$identity->hasRole('roleAdmin') && !$identity->hasRole('roleRoot')) return false;
        $hasRoleRoot = Yii::$app->authManager->getAssignment('roleRoot', $userId);
        $hasRoleAdmin = Yii::$app->authManager->getAssignment('roleAdmin', $userId);
        if (empty($hasRoleRoot) && empty($hasRoleAdmin)) return false;

        return true;
    }
}
