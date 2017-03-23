<?php

namespace asb\yii2\common_2_170212\rbac;

use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\base\Object;
use yii\base\Controller as YiiBaseController;
use yii\helpers\Inflector;
use yii\filters\AccessControl;

use Exception;

/**
 * Authorization helper.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class AuthHelper extends Object
{
    /** Array in format moduleUniqueId => controllerId => controllerClassName */
    protected static $_modulesControllers = [];

    /** Array in format moduleUniqueId => controllerId => accessBehaviorsRules */
    protected static $_accessRules;

    /**
     * Get roles from behaviors()-methods from every controller of module.
     * @param yii\base\Module $module
     * @return array [controllerArias => accessRules]
     */
    public static function moduleAccessRules($module)
    {//echo __METHOD__."@{$module->className()}({$module->uniqueId})";
        if (empty(static::$_accessRules[$module->uniqueId])) {
            static::$_accessRules[$module->uniqueId] = [];
            if ($module instanceof UniModule) {
                $pathList = $module->getBasePathList();
            } else {
                $pathList = [$module->getBasePath()];
            }//var_dump($pathList);
            foreach ($pathList as $path) {
                $dir = $path . DIRECTORY_SEPARATOR . UniModule::$controllersSubdir;
                if (is_dir($dir) && $handle = opendir($dir)) {
                    while (false !== ($file = readdir($handle))) {//echo "{$dir}/{$file}<br>";
                        if (1 === preg_match(UniModule::$regControllerFileSuffix, $file, $matches)) {//var_dump($matches[1]);
                            $controllerId = Inflector::camel2id($matches[1]);//var_dump($controllerId);
                            $controller = $module->createControllerByID($controllerId);
                            if ($controller instanceof YiiBaseController) {
                                static::$_modulesControllers[$module->uniqueId][$controllerId] = $controller::className();
                                $behaviors = $controller->behaviors();//var_dump($behaviors);
                                if (empty($behaviors['access']['rules'])) {
                                    static::$_accessRules[$module->uniqueId][$controllerId] = [];
                                } else {
                                    static::$_accessRules[$module->uniqueId][$controllerId] = $behaviors['access']['rules'];
                                }
                                break;
                            }
                        } 
                    }
                    closedir($handle); 
                }
            }
        }//var_dump(static::$_accessRules[$module->uniqueId]);//var_dump(static::$_modulesControllers);
        return static::$_accessRules[$module->uniqueId];
    }

    /**
     * Check if user can run action.
     * @param string $actionUid action uniqueId
     * @param yii\web\User $user application user
     * @return boolean
     */
    public static function canUserRunAction($actionUid, $user = null)
    {//echo __METHOD__."($user->id, $actionUid)<br>";
        if (empty($user)) $user = Yii::$app->user;

        $parts = explode('/', $actionUid);//var_dump($parts);
        if ($parts === false || count($parts) < 3) {
            throw new Exception("Illegal action uniqueId format: '{$actionUid}'");
        } else {
            $actionId = array_pop($parts);
            $controllerId = array_pop($parts);
            $moduleUid = implode ('/', $parts);//var_dump($moduleUid);

            $module = Yii::$app->getModule($moduleUid);//var_dump($module::className());
            if (empty($module)) {
                //throw new Exception("Can't get module '{$moduleUid}'");
                return false;
            } else {
                $controller = $module->createControllerByID($controllerId);
                if ($controller instanceof YiiBaseController) {
                    //static::$_modulesControllers[$moduleUid][$controllerId] = $controller::className();
                    $behaviors = $controller->behaviors();
                    if (empty($behaviors['access']['rules'])) {
                        $rules = [];
                    } else {
                        $rules = $behaviors['access']['rules'];
                    }//var_dump($rules);
                    //static::$_accessRules[$moduleUid][$controllerId] = $rules;
                } else {//var_dump($controller);exit;
                    throw new Exception("Illegal controller '{$controllerId}' in module '{$moduleUid}'");
                }
            }

            if (empty($rules)) {
                $result = true; // no rules - action allow
            } else {
                $ac = Yii::createObject(['class' => AccessControl::className(), 'user' => $user, 'rules' => $rules]);
                $action = $controller->createAction($actionId);
                $request = Yii::$app->getRequest();
                $result = false; // if rule(s) exists - deny by default
                foreach ($ac->rules as $rule) {
                    if ($rule->allows($action, $user, $request)) {//echo'allow by rule:';var_dump($rule);
                        $result = true; // found allow rule
                        break;
                    }
                }
            }//var_dump($result);

            return $result;
        }
    }

}
