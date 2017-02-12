<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\web\CmsUrlRule;
use asb\yii2\common_2_170212\web\RedirectUrlRule;
use asb\yii2\common_2_170212\web\ContentUrlRule;

use Yii;
use yii\web\GroupUrlRule;
use yii\web\UrlRule as WebUrlRule;
use yii\rest\UrlRule as RestUrlRule;

/**
 * Routes info.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class RoutesInfo
{
    /**
     * Show all application routes.
     * @params $moduleUid module uniqueId
     * @param boolean $echo if true print resule otherwise return result in string
     * @return string|null
     */
    public static function showRoutes($moduleUid = '', $echo = false)
    {
        $urlMan = Yii::$app->urlManager;
        $result = '';
        foreach ($urlMan->rules as $rule) {//var_dump($rule);
            if (empty($moduleUid)) {
                $result .= static::showRoute($rule);
            } else {
                switch ($rule::className()) {
                    case WebUrlRule::className():
                    case CmsUrlRule::className()://var_dump($rule->route);
                        if (0 === strpos($rule->route, $moduleUid)) $result .= static::showRoute($rule);
                    break;
                    case GroupUrlRule::className():
                        foreach ($rule->rules as $singleRule) {//var_dump($singleRule->route);
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
                        }//var_dump($controller);
                        if (0 === strpos($controller, $moduleUid)) $result .= static::showRoute($rule);
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

    /**
     * Show route.
     * @param yii\web\UrlRule $rule
     * @param boolean $echo if true print resule otherwise return result in string
     * @return string|null
     */
    public static function showRoute($rule, $echo = false)
    {
        $result = '';
        switch ($rule::className()) {
            case WebUrlRule::className():
                $result .= "'" . htmlspecialchars($rule->pattern) . "'"
                   . " => '" . htmlspecialchars($rule->route) . "'"
                   . " ({$rule::className()})"
                   . '<br>'
                   ;
                break;
            case GroupUrlRule::className():
                $result .= "{$rule::className()}:<br>";
                foreach ($rule->rules as $singleRule) {
                    $result .= ' + '
                     //. "'" . htmlspecialchars($rule->prefix) . '/'
                       . "'" . htmlspecialchars($singleRule->pattern) . "'"
                       . ' => '
                     //. "'" . $rule->routePrefix . '/'
                       . "'" . htmlspecialchars($singleRule->route) . "'"
                       . '<br>'
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
                }//var_dump($urlPrefix);var_dump($controller);exit;
                $result .= "{$rule::className()}:<br>";
                foreach ($rule->patterns as $template => $action) {
                    $result .= ' + '
                       . "'" . htmlspecialchars($rule->prefix)
                       . '/' . htmlspecialchars($urlPrefix) . '/'
                       . htmlspecialchars($template) . htmlspecialchars($rule->suffix) . "'"
                       . " => '" . $controller . '/' . htmlspecialchars($action) . "'"
                       . '<br>'
                       ;
                }
                break;
            case ContentUrlRule::className()://var_dump($rule);exit;
                $result .= "{$rule::className()}:<br>";
                $result .= "\t pattern = '" . $rule->pattern . "'<br>";
                $result .= "\t content alias = '" . $rule->alias . "'<br>";
                break;
            case RedirectUrlRule::className():
                $result .= "{$rule::className()}:<br>";
                $result .= "\t pattern = '" . $rule->pattern . "'<br>";
                $result .= "\t link = '" . $rule->link . "'<br>";
                break;
            case CmsUrlRule::className():
                $result .= "{$rule::className()}:<br>";
                $result .= " '{$rule->pattern}' => '{$rule->route}'<br>";
                $result .= "  + layout = '" . $rule->layout . "'<br>";
                break;
            default:
                $result .= "{$rule::className()}:<br>";
                ob_start();
                ob_implicit_flush(false);
                var_dump($rule);//?!
                $result .= ob_get_clean();
                break;
        }
        if ($echo) {
           echo $result;
        } else {
           return $result;
        }
    }

}
