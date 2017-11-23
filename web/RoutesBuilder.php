<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\base\UniApplication;

use yii\caching\Cache;
use Yii;

use Exception;

/**
 * Module routes builder.
 * Caching part.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class RoutesBuilder extends BaseRoutesBuilder
{
    public static $defaultCacheDuration = 300; //sec

    public static $_routes = [];

    /**
     * @inheritdoc
     */
    public static function collectRoutes($routeConfig, $app = null)
    {//echo __METHOD__;var_dump($routeConfig);
        $app = empty($app) ? Yii::$app : $app;
        $appKey = static::getAppKey($app);//var_dump($appKey);exit;
        $moduleUid = $routeConfig['moduleUid'];

        static::loadRoutes($app);
        if (!isset(static::$_routes[$appKey][$moduleUid])) {
            $msg = "*** Load routes from file for app='$appKey', module='$moduleUid'<br>";
            Yii::trace($msg);//echo $msg;
            static::$_routes[$appKey][$moduleUid] = parent::collectRoutes($routeConfig, $app);
        }
        return static::$_routes[$appKey][$moduleUid];
    }

    /**
     * Save all application's routes.
     * @param \yii\base\Application $app
     */
    public static function saveAppRoutes($app = null)
    {//echo __METHOD__;
        $app = empty($app) ? Yii::$app : $app;
        $appKey = static::getAppKey($app);//var_dump($appKey);exit;

        if ($app->cache instanceof Cache && isset(static::$_routes[$appKey])) {//echo __METHOD__;var_dump(static::$_routes[$appKey]);
            $app->cache->set($appKey, static::$_routes[$appKey], static::$defaultCacheDuration);
        }
    }

    /**
     * Load (prepare) all application's routes.
     * @param \yii\base\Application $app
     */
    public static function loadRoutes($app = null)
    {//echo __METHOD__;
        $app = empty($app) ? Yii::$app : $app;
        $appKey = static::getAppKey($app);//var_dump($appKey);exit;

        if (!isset(static::$_routes[$appKey])) {
            if ($app->cache instanceof Cache) {
                $data = $app->cache->get($appKey);//var_dump($data);
                if ($data !== false && is_array($data)) {
                    static::$_routes[$appKey] = $data;
                } else {
                    static::$_routes[$appKey] = [];
                }
            }
        }//echo __METHOD__;var_dump(static::$_routes[$appKey]);
    }

    /**
     * Get application's type as unique key.
     * @param \yii\base\Application $app
     * @return string
     */
    protected static function getAppKey($app)
    {
        if ($app instanceof UniApplication) {
            $appKey = $app->appTemplate . '-' . $app->type;
        } else {
            throw new Exception("Can't get application type for non-UniApplication");
        }//var_dump($appKey);exit;
        return $appKey;
    }

}
