<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\base\UniModule;

use asb\yii2\common_2_170212\models\User;

use Yii;
use yii\base\Model;
use yii\web\IdentityInterface;
//use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;

/**
 * Class providing identity information.
 * If exists module with static::userModuleUniqueId load User Identity there.
 * Otherwise use config.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class UserIdentity extends Model implements IdentityInterface //, ActiveRecordInterface
{
    public $id;
    public $username;
    public $password;
    public $authKey;
    public $accessToken;

    /**
     * Some parameters with default values.
     * You can owerwrite their values by define such array
     * in Yii2-application config['params']['asb\yii2\common_2_170212\web\UserIdentity'].
     */
    protected static $_params = [
        'userModuleUniqueId' => 'users', //?!
        'userManagerAlias'   => 'UserIdentity', // see UniModule::model($alias)
      //'usersConfigFname'   =>  dirname(__DIR__) . '/config/users-default.php', // error
        'usersConfigFname'   => '@asb/yii2/common_2_170212/config/users-default.php',
        'rolesConfigFname'   => '@asb/yii2/common_2_170212/config/roles-default.php',
    ];

    /**
     * Get one of parameters merged with Yii::$app->params[self::className()].
     * @param string $alias name of parameter
     * @return string
     */
    protected static function parameter($alias)
    {//var_dump(static::$_params);
        if (isset(Yii::$app->params[self::className()]) && is_array(Yii::$app->params[self::ClassName()]) ) {
            //var_dump(Yii::$app->params[self::ClassName()]);
            static::$_params = ArrayHelper::merge(
                static::$_params
              , Yii::$app->params[self::className()]
            );
        }//var_dump(static::$_params);exit;
        return static::$_params[$alias];
    }
    
    protected static $_moduleUserIdentity;
    /**
     * Find Users module module from system loaded modules
     * @return yii\web\IdentityInterface|null
     */
    public static function moduleUserIdentity()
    {//echo __METHOD__;
        if (empty(static::$_moduleUserIdentity)) {
            $module = Yii::$app->getModule(static::parameter('userModuleUniqueId'));//var_dump($module);
            if (!empty($module) && $module instanceof UniModule) {
                $result = $module->getDataModel(static::parameter('userManagerAlias'));//var_dump($result);
                static::$_moduleUserIdentity = $result;
            }
        }//var_dump(static::$_moduleUserIdentity);exit;

        //return null; //!! use for debug
        return static::$_moduleUserIdentity;
    }

    protected static $_users;
    /**
     * Get users infos from file.
     * @return array
     */
    protected static function users()
    {//echo __METHOD__;
        if (empty(static::$_users)) {
            static::$_users = include(Yii::getAlias(static::parameter('usersConfigFname')));
        }//var_dump(static::$_users);exit;
        return static::$_users;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        $userIdentiry = static::moduleUserIdentity();//echo __METHOD__;var_dump($userIdentiry);
        if (!empty($userIdentiry)) return $userIdentiry::findIdentity($id);

        $users = static::users();//var_dump($users);exit;
        return isset($users[$id]) ? new static($users[$id]) : null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $userIdentiry = static::moduleUserIdentity();//echo __METHOD__;var_dump($userIdentiry);
        if (!empty($userIdentiry)) return $userIdentiry::findIdentityByAccessToken($token, $type);

        $users = static::users();
        foreach ($users as $user) {
            if (isset($user['accessToken'][$type]) && $user['accessToken'][$type] === $token) {
                return new static($user);
            }
        }
        return null;

    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        $userIdentiry = static::moduleUserIdentity();//echo __METHOD__;var_dump($userIdentiry);
        if (!empty($userIdentiry)) return $userIdentiry->getId();

        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        $userIdentiry = static::moduleUserIdentity();//echo __METHOD__;var_dump($userIdentiry);
        if (!empty($userIdentiry)) return $userIdentiry->getAuthKey();

        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        $userIdentiry = static::moduleUserIdentity();//echo __METHOD__;var_dump($userIdentiry);
        if (!empty($userIdentiry)) return $userIdentiry->validateAuthKey($authKey);

        return $this->getAuthKey() === $authKey;
    }

    /**
     * Need if try to use this as ActiveRecord object.
     */
    public function __call($name, $params)
    {//echo __METHOD__."($name)";var_dump($params);
        $userIdentiry = static::moduleUserIdentity();
        if (!empty($userIdentiry)) {
            if (method_exists($userIdentiry, $name)) {
                return call_user_func_array([$userIdentiry, $name], $params);
            } else {
                return parent::__call($name, $params);
            }
        }
    }
    /**
     * Need if try to use this as ActiveRecord object.
     */
    public static function __callStatic($name, $params)
    {//echo __METHOD__."($name)";var_dump($params);exit;
        $userIdentiry = static::moduleUserIdentity();
        if (!empty($userIdentiry)) {
            if (method_exists($userIdentiry, $name)) {
                return call_user_func($userIdentiry::className() . '::' . $name, $params);
            } else {
                return parent::__call($name, $params);
            }
        }
    }

    protected static $_usersList;
    /**
     * @return array of users info
     */
    public static function usersList()
    {
        if (empty(self::$_usersList)) {
            $userIdentiry = static::moduleUserIdentity();//echo __METHOD__;var_dump($userIdentiry);
            if (empty($userIdentiry)) {
                self::$_usersList = static::users();
            } else if (method_exists($userIdentiry, 'usersList')) {
                self::$_usersList = $userIdentiry->usersList();
            } else {
                throw new \Exception("Method 'usersList' expected in UserIdentity");
            }
        }//var_dump(self::$_usersList);exit;
        return self::$_usersList;
    }

    /**
     * @return array in format id => username
     */
    public static function usersNames()
    {
        $list = static::usersList();
        return ArrayHelper::map($list, 'id', 'username');
    }

}
