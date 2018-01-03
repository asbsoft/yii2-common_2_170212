<?php

namespace asb\yii2\common_2_170212\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

/**
 * Base admin controller.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class BaseAdminController extends BaseController
{
    /** Default prefix of admin path */
    public static $adminPath = 'admin';

    /** Part of default login link route on backend */
    public static $urlLoginDefaultAdminPart = '/user/login';

    /** Default admin roles */
    public static $adminRoles = ['roleRoot', 'roleAdmin'];

    /** Error page */
    public static $errorActionUniqueId = 'sys/admin/error';

    /** Preffered layout path */
    public static $layoutPathBackend;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->_type = 'backend';

        if (!empty(Yii::$app->errorHandler->errorAction)) static::$errorActionUniqueId = Yii::$app->errorHandler->errorAction;

        if (!empty(Yii::$app->modules['sys']->params['adminAuthTimeout'])) {
            Yii::$app->user->authTimeout = Yii::$app->modules['sys']->params['adminAuthTimeout'];
        }

        if (!empty(static::$layoutPathBackend)) {
            Yii::$app->layoutPath = static::$layoutPathBackend;
        }

        if (isset(Yii::$app->params['adminPath'])) {
            static::$adminPath = Yii::$app->params['adminPath'];
        }

    }

    protected static $_urlLoginAdmin;
    /**
     * @inheritdoc
     */
    public static function urlLogin()
    {
        if (isset(Yii::$app->params['adminPath'])) {
            static::$adminPath = Yii::$app->params['adminPath'];
        }
        if (!isset(static::$_urlLoginAdmin)) {
            static::$_urlLoginAdmin = static::$adminPath . static::$urlLoginDefaultAdminPart;
        }
        return static::$_urlLoginAdmin;
    }
    /**
     * @inheritdoc
     */
    public static function setUrlLogin($url)
    {
        static::$_urlLoginAdmin = $url;
    }

    /**
     * @inheritdoc
     * Default for every admin controller.
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => [],
                        'allow' => true,
                        'roles' => static::$adminRoles,
                    ],
                ],
            ],
        ];
    }

}
