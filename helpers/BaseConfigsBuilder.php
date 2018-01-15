<?php

namespace asb\yii2\common_2_170212\helpers;

use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\base\Module as YiiBaseModule;
use yii\base\Component;
use yii\helpers\ArrayHelper;

use ReflectionClass;

/**
 * Module configs builder.
 * Build initial module configs by getting them from module's config file and all it parents configs.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class BaseConfigsBuilder extends Component
{
    /** Included files cache */
    protected static $_configFiles = [];
    /**
     * Get, save in cache and return result of include file
     * @param string $filename
     * Note if config has some calculations caching may be not correct.
     */
    public static function includeConfigFile($filename)
    {
        if (!isset(self::$_configFiles[$filename])) {
            if (is_file($filename)) {
                self::$_configFiles[$filename] = include($filename);
            } else {
                throw new InvalidConfigException("Need config file '$filename'");
            }
        }
        return self::$_configFiles[$filename];
    }
    /** Clean included files cache */
    public static function cleanConfigFileCache()
    {
        static::$_configFiles = [];
    }

    /**
     * Recursively get configs for module and all it's PHP-parents.
     * DEPRECATED!!. Use static::getConfig().
     * @param string $modulePath
     * @param array $config initial config
     * @return array result config
     */
    public static function getAllConfigs($modulePath, $config = [])
    {
        $configFile = $modulePath . DIRECTORY_SEPARATOR . UniModule::$configsSubdir . DIRECTORY_SEPARATOR . UniModule::$configBasefilename . '.php';
        if (is_file($configFile)) {
            $addConfig = self::includeConfigFile($configFile);
            if (!empty($addConfig)) {
                $config = ArrayHelper::merge($addConfig, $config);
                if (isset($addConfig['parentPath'])) {
                    $parentPath = Yii::getAlias($addConfig['parentPath']);
                    if (is_dir($parentPath)) $config = self::getAllConfigs($parentPath, $config);
                }
            }
        }
        return $config;
    }

    /**
     * Get LATEST configs path for module. It's may not hav configs. Configs may be only in inherited-parents.
     * @return string
     */
/*
    public static function getConfigPath($module)
    {
        $class = new \ReflectionClass($module);
        $modulePath = dirname($class->getFileName());
        return $modulePath . DIRECTORY_SEPARATOR . UniModule::$configsSubdir;
    }
*/
    /**
     * Get selected config for module
     * and merged with all it's PHP-parents configs if $recursive = true
     * @param \yii\base\Module $module module class item
     * @param string $name basename of config file
     * @param boolean $recursive
     * @param string $subdir module subdir contains configs
     * @return array result config
     */
    public static function getConfig($module, $name = '', $recursive = true, $subdir = '')
    {
        if ($subdir === '') $subdir = UniModule::$configsSubdir;
        if ($name   === '') $name   = UniModule::$configBasefilename;
        $app = Yii::$app;

        $config = [];
        $class = new ReflectionClass($module);
        while (true) {
            $modulePath = dirname($class->getFileName());
            $configFile = $modulePath . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR . $name . '.php';
            if (is_file($configFile)) {
                $addConfig = self::includeConfigFile($configFile);
                if (!empty($addConfig)) {
                    $config = ArrayHelper::merge($addConfig, $config);
                }
            }
            if (!$recursive) break;

            $class = $class->getParentClass();

            if (empty($class)) break;

            if ($class->name == YiiBaseModule::className()) break;
            if ($class->name == UniModule::className()) break;
            if ($class->name == $app::className()) break;
            //if ($class->name == __CLASS__) break;
        }
        return $config;
    }

}
