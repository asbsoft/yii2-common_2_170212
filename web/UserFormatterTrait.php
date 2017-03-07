<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\web\UserIdentity;

use yii\helpers\Html;

use Exception;

/**
 * @author ASB <ab2014box@gmail.com>
 */
trait UserFormatterTrait
{
    public static $unexistentUser = '???';

    protected static $_usersList;

    public function asUsername($id)
    {
        if (empty(self::$_usersList)) {
            self::$_usersList = UserIdentity::usersNames();
        }
        $value = empty(self::$_usersList[$id]) ? static::$unexistentUser : self::$_usersList[$id];
        return Html::encode($value);
    }
}
