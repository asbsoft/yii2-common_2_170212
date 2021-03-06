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
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class RoutesBuilder extends BaseRoutesBuilder
{
    public static $defaultCacheDuration = 300; //sec

    public static $_routes = [];

    /**
     * @inheritdoc
     */
    public static function collectRoutes($routeConfig, $app = null)
    {
        $app = empty($app) ? Yii::$app : $app;
        $appKey = UniApplication::appKey($app);
        $moduleUid = $routeConfig['moduleUid'];
        $routesType = $routeConfig['routesType'];

        static::loadRoutes($app);

        if (!isset(static::$_routes[$appKey][$moduleUid][$routesType])) {
            $msg = "*** Load routes from file for app='$appKey', module='$moduleUid'<br>";
            Yii::trace($msg);
            static::$_routes[$appKey][$moduleUid][$routesType] = parent::collectRoutes($routeConfig, $app);
        }

        return static::$_routes[$appKey][$moduleUid][$routesType];
    }

    /**
     * Save all application's routes.
     * @param \yii\base\Application $app
     */
    public static function saveAppRoutes($app = null)
    {
        $app = empty($app) ? Yii::$app : $app;
        $appKey = UniApplication::appKey($app);

        if ($app->cache instanceof Cache && isset(static::$_routes[$appKey])) {
            $app->cache->set($appKey, static::$_routes[$appKey], static::$defaultCacheDuration);
        }
    }

    /**
     * Load (prepare) all application's routes.
     * @param \yii\base\Application $app
     */
    public static function loadRoutes($app = null)
    {
        $app = empty($app) ? Yii::$app : $app;
        $appKey = UniApplication::appKey($app);

        if (!isset(static::$_routes[$appKey])) {
            if ($app->cache instanceof Cache) {
                $data = $app->cache->get($appKey);
                if ($data !== false && is_array($data)) {
                    static::$_routes[$appKey] = $data;
                } else {
                    static::$_routes[$appKey] = [];
                }
            }
        }
    }

}
