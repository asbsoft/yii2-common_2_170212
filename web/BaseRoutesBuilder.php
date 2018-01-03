<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\base\UniModule;
use asb\yii2\common_2_170212\base\BaseModule;

use Yii;
use yii\base\Component;
use yii\web\GroupUrlRule;
use yii\rest\UrlRule as RestUrlRule;
use yii\web\UrlRule as WebUrlRule;

use Exception;
use ReflectionClass;

/**
 * Module routes builder
 * Without caching.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class BaseRoutesBuilder extends Component
{
    /** Default config subpath from module root directory */
    public static $configsSubdir  = 'config';

    /**
     * Build routes by routes config.
     * @param array $routeConfig
     * @param yii\base\Application $app
     */
    public static function buildRoutes($routeConfig, $app = null)
    {
        $app = empty($app) ? Yii::$app : $app;
        $rules = static::collectRoutes($routeConfig, $app);
        $app->urlManager->addRules($rules, $routeConfig['append']);
    }

    /**
     * Collect routes by routes config.
     * @param array $routeConfig
     * @param yii\base\Application $app
     * @return yii\web\UrlRuleInterface[] rules for UrlManager
     */
    public static function collectRoutes($routeConfig, $app = null)
    {
        $app = empty($app) ? Yii::$app : $app;
        $rules = [];
        if (isset($routeConfig['urlPrefix']) && $routeConfig['urlPrefix'] !== false && is_file($routeConfig['fileRoutes'])) {
            //!! config file use var $routeConfig:
            $routes = include($routeConfig['fileRoutes']);
            if (empty($routes)) return [];

            switch ($routeConfig['class']) {
                case GroupUrlRule::className(): // only for this class config-routes-files very simple
                    $configGroupRules = [
                        'rules'  => $routes,
                        'prefix' => $routeConfig['urlPrefix'],
                        'routePrefix' => $routeConfig['moduleUid'],
                    ];
                    $rules = [new GroupUrlRule($configGroupRules)];
                    break;
                default: // universal
                    if (isset($routes['enablePrettyUrl'])) {
                        $app->urlManager->enablePrettyUrl = $routes['enablePrettyUrl'];
                        unset($routes['enablePrettyUrl']);
                    }
                    $rules = $routes;
                    break;
            }
        }
        return $rules;
    }

    /**
     * Collect full module's URL-prefix from URL-prefixes of container("parent") modules.
     * @param string $urlPrefix (initial) prefix
     * @param yii\base\Module $module
     * @param string $type
     * @return string new URL-prefix
     */
    public static function correctUrlPrefix($urlPrefix, $module, $type)
    {
        // if $urlPrefix begin with '/' use it as absolute prefix
        if (substr($urlPrefix, 0, 1) == '/') {
            return substr($urlPrefix, 1);
        }
        
        if (empty($module->routesConfig[$type])) {
            return $urlPrefix;
        }

        $conf = $module->routesConfig[$type];
        if (is_string($conf) && !empty($conf)) {
            $addPrefix = $conf;
        } elseif (!empty($conf['urlPrefix'])) {
            $addPrefix = $conf['urlPrefix'];
        }
        $urlPrefix = $addPrefix . (empty($urlPrefix) ? '' : ('/' . $urlPrefix));

        $module = $module->module;
        if (!empty($module) && $module instanceof UniModule && !empty($module->routesConfig[$type])) {
            return static::correctUrlPrefix($urlPrefix, $module, $type);
        }
        return $urlPrefix;
    }

    /**
     * Create routes filename of routes $type for $module.
     * @param yii\base\Module $module
     * @param yii\base\Module $module
     * @param string $type
     * @see BaseModule::getRoutesFilename()
     * This method will use for standard Yii2-modules (non-BaseModule).
     */
    public static function getRoutesFilename($module, $type)
    {
        $class = new ReflectionClass($module);
        $dirname = dirname($class->getFileName());
        $baseFileName = sprintf(BaseModule::$patternRoutesFilename, $type);
        $routesSubdir = $dirname . DIRECTORY_SEPARATOR . static::$configsSubdir;
        $file = $routesSubdir . '/' . $baseFileName;
        if (is_file($file)) {
            return $file;
        }
        if ($module instanceof self) {
            throw new Exception("Routes list file '{$baseFileName}' not found in config(s) folder(s) for module " . $module->className());
        } else {
            return false;
        }
    }

    /**
     * Check if link will recognize by route's rule.
     * @return boolean true if found $rule correspond to $link
     */
    public static function properRule($rule, $link)
    {
        if (isset($rule->pattern)) {
            if (preg_match($rule->pattern, $link) > 0) return true;
        }
        if ($rule instanceof GroupUrlRule) {
            foreach ($rule->rules as $nextRule) {
                if (preg_match($nextRule->pattern, $link) > 0) return true;
            }
        }
        return false;
    }

}
