<?php

namespace asb\yii2\common_2_170212\web;

use Yii;
use yii\web\GroupUrlRule;
use yii\web\UrlRule as WebUrlRule;
use yii\rest\UrlRule as RestUrlRule;

/**
 * Routes info.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class RoutesInfo
{
    /**
     * Show all application routes.
     * @param string $moduleUid module uniqueId
     * @param Application $app 
     * @param boolean $echo if true print resule otherwise return result in string
     * @return string|null
     */
    public static function showRoutes($moduleUid = '', $app = null, $echo = false)
    {
        if (empty($app)) {
            $app = Yii::$app;
        }
        $urlMan = $app->urlManager;
        $result = '';
        foreach ($urlMan->rules as $rule) {
            if (empty($moduleUid)) {
                $result .= static::showRoute($rule);
            } else {
                switch ($rule::className()) {
                    case GroupUrlRule::className():
                        foreach ($rule->rules as $singleRule) {
                            if (0 === strpos($singleRule->route, $moduleUid)) {
                                $result .= static::showRoute($rule); // will show all rules from group
                                break;
                            }
                        }
                        break;
                    case RestUrlRule::className():
                        if (is_array($rule->controller)) {
                            $urlPrefix = array_keys($rule->controller)[0];
                            $controller = $rule->controller[$urlPrefix];
                        } else {
                            $controller = $rule->controller;
                        }
                        if (0 === strpos($controller, $moduleUid)) {
                            $result .= static::showRoute($rule);
                        }
                        break;
                    default:
                        if (0 === strpos($rule->route, $moduleUid)) {
                            $result .= static::showRoute($rule);
                        }
                        break;
                }
           }
        }
        if ($echo) {
           echo $result;
        } else {
           return $result;
        }
    }

    public static $ruleShift = '   ';
    /**
     * Show route.
     * @param yii\web\UrlRule $rule
     * @param boolean $echo if true print resule otherwise return result in string
     * @param boolean $showPattern
     * @return string|null
     */
    public static function showRoute($rule, $echo = false, $showPattern = false)
    {
        $result = '';
        switch ($rule::className()) {
            case WebUrlRule::className():
                $result .= ''
                   . "'" . htmlspecialchars($rule->name) . "'"
                   . ($showPattern ? " (" . htmlspecialchars($rule->pattern) . ")" : '')
                   . " => '" . htmlspecialchars($rule->route) . "'"
                   . " ({$rule::className()})"
                   . "\n"
                   ;
                break;
            case GroupUrlRule::className():
                $result .= "'{$rule->prefix}/...' ({$rule::className()}): <br>";
                foreach ($rule->rules as $singleRule) {
                    $result .= static::$ruleShift
                       . "'" . htmlspecialchars($singleRule->name) . "'"
                       . ($showPattern ? " (" . htmlspecialchars($singleRule->pattern) . ")" : '')
                       . ' => '
                       . "'" . htmlspecialchars($singleRule->route) . "'"
                       . "\n"
                       ;
                }
                break;
            case RestUrlRule::className():
                if (is_array($rule->controller)) {
                    //foreach ($rule->controller as $urlPrefix => $controller) break;
                    $urlPrefix = array_keys($rule->controller)[0];
                    $controller = $rule->controller[$urlPrefix];
                } else {
                    $controller = $rule->controller;
                    $urlPrefix = '';
                }
                $result .= "'{$rule->prefix}/{$urlPrefix}/...' ({$rule::className()}): <br>";
                foreach ($rule->patterns as $template => $action) {
                    $result .= static::$ruleShift
                       . "'" . $rule->prefix
                       . '/' . $urlPrefix . '/'
                       . htmlspecialchars($template) . htmlspecialchars($rule->suffix) . "'"
                       . " => '" . $controller . '/' . htmlspecialchars($action) . "'"
                       . "\n"
                       ;
                }
                break;
            default:
                $result .= "{$rule::className()}:<br>";
                if (method_exists($rule, 'showRouteInfo')) {
                    $info = $rule->showRouteInfo($showPattern);
                    $strings = explode("\n", trim($info));
                    foreach ($strings as $str) $result .= static::$ruleShift . trim($str) . "\n";
                } else {
                    ob_start();
                    ob_implicit_flush(false);
                    var_dump($rule);
                    $result .= ob_get_clean();
                }
                break;
        }
        if ($echo) {
           echo $result;
        } else {
           return $result;
        }
    }

}
