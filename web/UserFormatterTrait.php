<?php

namespace asb\yii2\common_2_170212\web;

use yii\helpers\Html;
use Yii;

use Exception;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
trait UserFormatterTrait
{
    public static $unexistentUser = 'user#';

    protected static $_usersNames = [];

    public function asUsername($id)
    {
        $identityClass = Yii::$app->user->identityClass;

        if (empty(self::$_usersNames[$id])) {
            $user = $identityClass::findIdentity($id);
            if (!empty($user->username)) {
                self::$_usersNames[$id] = $user->username;
            }
        }
        $value = empty(self::$_usersNames[$id]) ? (static::$unexistentUser . $id) : self::$_usersNames[$id];
        return Html::encode($value);
    }

}
