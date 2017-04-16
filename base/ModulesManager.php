<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\base\Module as YiiBaseModule;
use yii\helpers\ArrayHelper;

/**
 * Modules manager.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class ModulesManager implements ModulesManagerInterface
{
    /** UniqueId of manager's module */
    //public static $modulesManagerModuleUid = 'sys/modmgr'; //?? infinite loop
    public static $modulesManagerModuleUid = 'modmgr';

    /** Alias of model in manager's module */
    public static $modulesManagerModelAlias = 'ModulesManager';

    /** Submodules config */
    public static $submodulesConfigFname = '@asb/yii2/common_2_170212/config/submodules-default.php';

    /** Translation category */
    public static $tc = 'app/sys/module';

    protected static $_submodsConfig;
    /**
     * Get submodules infos from file.
     * @return array
     */
    public static function submodsConfig()
    {//echo __METHOD__;
        if (empty(static::$_submodsConfig)) {
            static::$_submodsConfig = include(Yii::getAlias(static::$submodulesConfigFname));
        }//var_dump(static::$_submodsConfig);exit;
        return static::$_submodsConfig;
    }

    protected static $_modmgr;
    /** Get modules manager instance */
    public static function instance()
    {//echo __METHOD__.'<br>';
        if (empty(static::$_modmgr)) {
            $module = Yii::$app->getModule(static::$modulesManagerModuleUid);
            //$module = UniModule::getModuleByUniqueId(static::$modulesManagerModuleUid); //?? infinite loop
            //$module = false; //!! for debug
            //var_dump($module);exit;
            if (!empty($module) && $module instanceof UniModule) {
                static::$_modmgr = $module->getDataModel(static::$modulesManagerModelAlias);
            } else {
                static::$_modmgr = new static;
            }
        }//var_dump(static::$_modmgr);//exit;
        return static::$_modmgr;
    }

    /** Modules already has installed additional submodules format array(moduleClassName => moduleUniqueId) */
    protected static $_modulesWithInstalledSubmodules = [];
    /**
     * Add new module to list modules with already installed submodules.
     * @param yii\base\Module $module
     */
    public static function setAlreadyAddSubmodules($module)
    {
        if($module instanceof YiiBaseModule) {
            static::$_modulesWithInstalledSubmodules[$module::className()] = $module->uniqueId; // uniqueId for Yii::$app = ''
        }
    }
    /**
     * Check if $module has additional dynamic submodules
     * @param yii\base\Module|string $module
     * @return boolean
     */
    public static function alreadyAddSubmodules($module)
    {
        if($module instanceof YiiBaseModule) {
            //$module = $module->uniqueId;
            $module = $module::className();
        }//echo __METHOD__."($module)";var_dump(static::$_modulesWithInstalledSubmodules);
        if (is_string($module)) {
            if (array_key_exists($module, static::$_modulesWithInstalledSubmodules)) return true;
            if (in_array($module, static::$_modulesWithInstalledSubmodules)) return true;
        }
        return false;
    }

    /** Additional (to own) submodules of module in format array(moduleUniqueId => array(submodules)) */
    protected static $_additionalSubmodules = [];
    /**
     * Get submodules configs for module from modules manager
     * addition to static submodules defined in module's $config['modules'].
     * @param \yii\base\Module $module
     * @param boolean $onlyActivated if true show only activated in Modules manager
     * @return array of submodules configs
     * see getSubmodules()
     */
    public static function submodules($module, $onlyActivated = true)
    {
        $modmgr = static::instance();//var_dump($modmgr::className());
        if (!isset(static::$_additionalSubmodules[$module->uniqueId])) {
            $result = $modmgr->getSubmodules($module, $onlyActivated);//echo"for:'{$module->uniqueId}':";var_dump(array_keys($result));
            $_additionalSubmodules[$module->uniqueId] = $result;
        }
        return $_additionalSubmodules[$module->uniqueId];
    }

    /**
     * Get modules list in format uniqueId => label.
     * Use for modules dropdown list.
     * @param yii\base\Module|empty $module "parent"(container) module
     * @param boolean $onlyActivated if true show only activated in Modules manager
     * @param boolean $forModmgr if true not expand to string moduleId-number from Modules manager
     * @param boolean $onlyUniModule if false show only UniModule modules
     * @param boolean $onlyLoaded if false show only loaded modules
     * @param string $indent
     * @return array of module's uniqueId => module's label
     */
    public static function modulesNamesList($module = null, $onlyActivated = true, $forModmgr = false
      , $onlyUniModule = false, $onlyLoaded = false, $indent = '. ')
    {
        $list = [];
        if (empty($module)) {
            $module = Yii::$app;//var_dump(array_keys(Yii::$app->modules));
        }//echo __METHOD__."({$module->uniqueId})<br>";//var_dump(array_keys($module->modules));

        $modmgr = static::instance();//var_dump($modmgr::className());exit;

        if (method_exists ($modmgr, 'registeredModuleName')) {
            $label = $modmgr::registeredModuleName($module->uniqueId);
        }
        if (empty($label) && $module instanceof UniModule) $label = $module->inform('label');
        if (empty($label)) $label = Yii::t(self::$tc, 'Module') . ' ' . $module->uniqueId;

        if ($module != Yii::$app) { // skip application itself
            if (!$onlyUniModule || $module instanceof UniModule) {
                $prefix = str_repeat($indent, count(explode('/', $module->uniqueId)));
                $muid = $module->uniqueId;
                if ($forModmgr) {
                    $muid = $modmgr::tonumberModuleUniqueId($muid);
                }
                $list[$muid] = $prefix . $label;
            }
        }

        $staticSubmodules = $module->modules;
        $dynSubmodules = $modmgr->getSubmodules($module, $onlyActivated);//echo $module->uniqueId;var_dump(array_keys($dynSubmodules));
        $module->modules = ArrayHelper::merge($dynSubmodules, $staticSubmodules);//echo"+ submodules @ '{$module->uniqueId}':";var_dump(array_keys($module->modules));
        if (empty($module->modules)) {//var_dump($list);
            return $list;
        }

        // add submodules list
        foreach ($module->modules as $childId => $childModule) {//echo"{$childId} @ {$module->uniqueId}<br>";
            if (!$onlyLoaded && is_array($childModule)) {//var_dump($childModule);
                $childModule = $module->getModule($childId);//if(empty($childModule))echo"??empty($childId)<br>";else var_dump($childModule->uniqueId);
                //$childModule = Yii::$app->getModule($module->uniqueId . '/' . $childId);//var_dump($childModule->uniqueId);
            }
            if ($childModule instanceof YiiBaseModule) {
                $list += static::modulesNamesList($childModule, $onlyActivated, $forModmgr, $onlyUniModule, $onlyLoaded, $indent);
            }
        }//var_dump($list);
        return $list;
    }

    /**
     * Get all application's submodules uniqueIds and classNames.
     * @return array in format [module uniqueId => module className]
     */
    public static function appModulesList()
    {
        if (empty(static::$_submodules)) {
            static::collectSubmoduleClasses(Yii::$app);
            //ksort(static::$_submodules);
        }//var_dump(static::$_submodules);exit;
        return static::$_submodules;
    }
    /** Array [module uniqueId => module className] */
    protected static $_submodules = [];
    protected static function collectSubmoduleClasses($module = null)
    {
        if ($module != Yii::$app) { // skip application itself
            static::$_submodules[$module->uniqueId] = $module::className();
        }
        foreach ($module->modules as $childId => $childModule) {
            if (is_array($childModule)) $childModule = $module->getModule($childId);
            if ($childModule instanceof YiiBaseModule) {
                static::collectSubmoduleClasses($childModule);
            }
        }
    }

    /**
     * Get bootstrap list addition to Yii::$app->bootstrap.
     * @return array
     */
    public static function bootstrapList($parentModuleUid = '')
    {
        $modmgr = static::instance();//var_dump($modmgr::className());
        return $modmgr->getBootstrapList($parentModuleUid);
    }

    /**
     * @inheritdoc
     */
    public function getBootstrapList($parentModuleUid = '')
    {
        return []; //todo: get from config-file
    }

    /**
     * Get submodules configs for module from modules manager
     * addition to static submodules configs defined in module's $config['modules'].
     * @param \yii\base\Module $module
     * @return array of submodules configs
     */
    public function getSubmodules($module)
    {//echo __METHOD__."({$module->uniqueId})<br>";
        $submodsConfig = static::submodsConfig();//var_dump($submodsConfig);exit;
        $result = [];
        foreach ($submodsConfig as $submodUniqueId => $config) {
            if (($pos = strrpos($submodUniqueId, '/')) !== false) {
                $parentUid = substr($submodUniqueId, 0, $pos);
                $moduleId = substr($submodUniqueId, $pos + 1);
            } else {
                $parentUid = ''; // Yii::$app
                $moduleId = $submodUniqueId;
            }
            if ($parentUid == $module->uniqueId) {//echo "+parentUid='$parentUid',moduleId='$moduleId'<br>";
                $result[$moduleId] = $config;
            }
        }//var_dump($result);
        return $result;
    }

}
