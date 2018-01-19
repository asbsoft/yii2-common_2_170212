<?php

namespace asb\yii2\common_2_170212\helpers;

use asb\yii2\common_2_170212\base\UniApplication;

use Yii;
use yii\caching\Cache;

/**
 * Module configs builder.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ConfigsBuilder extends BaseConfigsBuilder
{
    public static $defaultCacheDuration = 300; //sec

    /** Cache key for saving configs */
    public static $confFileCacheKey = 'confFileCacheKey';

    /**
     * @inheritdoc
     * Use cache to save all configs together
     */
    public static function includeConfigFile($filename, $application = null)
    {
        if (empty($application)) {
            $application = Yii::$app;
        }
        $appKey = UniApplication::appKey($application);
        $cacheKey = static::$confFileCacheKey . '/' . $appKey;

        if (empty(static::$_configFiles) && $application->cache->exists(static::$confFileCacheKey)) {
            static::$_configFiles = $application->cache->get($cacheKey);
        }

        return parent::includeConfigFile($filename, $application);
    }

    /**
     * Save all configs together in cache
     */
    public static function cacheAllConfigsFile($application = null)
    {
        if (empty($application)) {
            $application = Yii::$app;
        }
        $appKey = UniApplication::appKey($application);
        $cacheKey = static::$confFileCacheKey . '/' . $appKey;

        if ($application->cache instanceof Cache && !empty(static::$_configFiles)) {
            $application->cache->set($cacheKey, static::$_configFiles, static::$defaultCacheDuration);
        }
    }

    /**
     * @inheritdoc
     */
    public static function cleanConfigFileCache($application = null)
    {
        if (empty($application)) {
            $application = Yii::$app;
        }
        $appKey = UniApplication::appKey($application);
        $cacheKey = static::$confFileCacheKey . '/' . $appKey;

        $application->cache->delete($cacheKey);

        parent::cleanConfigFileCache($application);
    }

}
