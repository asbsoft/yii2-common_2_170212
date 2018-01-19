<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\base\UniApplication;
use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\base\Module as YiiBaseModule;
use yii\helpers\ArrayHelper;

/**
 * Modules manager.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
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
    public static $tc = 'common';

    protected static $_submodsConfig;
    /**
     * Get submodules infos from file.
     * @return array
     */
    public static function submodsConfig()
    {
        if (empty(static::$_submodsConfig)) {
            static::$_submodsConfig = include(Yii::getAlias(static::$submodulesConfigFname));
        }
        return static::$_submodsConfig;
    }

    protected static $_modmgr;
    /**
     * Get modules manager instance
     */
    public static function instance()
    {
        if (empty(static::$_modmgr)) {
            $module = Yii::$app->getModule(static::$modulesManagerModuleUid);
            if (!empty($module) && $module instanceof UniModule) {
                static::$_modmgr = $module->getDataModel(static::$modulesManagerModelAlias);
            } else {
                static::$_modmgr = new static;
            }
        }
        return static::$_modmgr;
    }

    /** Modules already has installed additional submodules format array(moduleClassName => moduleUniqueId) */
    protected static $_modulesWithInstalledSubmodules = [];
    /**
     * Add new module to list modules with already installed submodules.
     * @param yii\base\Module $module
     * @param yii\base\Application $app
     */
    public static function setAlreadyAddSubmodulesFor($module, $app = null)
    {
        if (empty($app)) {
            $app = Yii::$app;
        }
        $appKey = UniApplication::appKey($app);
        if($module instanceof YiiBaseModule) {
            static::$_modulesWithInstalledSubmodules[$appKey][$module::className()] = $module->uniqueId; // uniqueId for Yii::$app = ''
        }
    }
    /**
     * Check if $module has additional dynamic submodules
     * @param yii\base\Module|string $module
     * @param yii\base\Application $app
     * @return boolean
     */
    public static function alreadyAddSubmodulesFor($module, $app = null)
    {
        if (empty($app)) {
            $app = Yii::$app;
        }
        $appKey = UniApplication::appKey($app);
        if($module instanceof YiiBaseModule) {
            //$module = $module->uniqueId;
            $module = $module::className();
        }
        if (is_string($module) && !empty(static::$_modulesWithInstalledSubmodules[$appKey])) {
            if (array_key_exists($module, static::$_modulesWithInstalledSubmodules[$appKey])) return true;
            if (in_array($module, static::$_modulesWithInstalledSubmodules[$appKey])) return true;
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
     * @param \yii\base\Application $app
     * @return array of submodules configs
     * see getSubmodules()
     */
    public static function submodules($module, $onlyActivated = true, $app = null)
    {
        if (empty($app)) {
            $app = Yii::$app;
        }
        $appKey = UniApplication::appKey($app);
        $modmgr = static::instance();
        if (!isset(static::$_additionalSubmodules[$appKey][$module->uniqueId])) {
            $result = $modmgr->getSubmodules($module, $onlyActivated);
            $_additionalSubmodules[$appKey][$module->uniqueId] = $result;
        }
        return $_additionalSubmodules[$appKey][$module->uniqueId];
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
            $module = Yii::$app;
        }

        $modmgr = static::instance();

        if (method_exists ($modmgr, 'registeredModuleName')) {
            $label = $modmgr::registeredModuleName($module->uniqueId);
        }
        if (empty($label) && $module instanceof UniModule) {
            $label = $module->inform('label');
        }
        if (empty($label)) {
                $label = Yii::t(self::$tc, 'Module') . ' ' . $module->uniqueId;
        }

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
        $dynSubmodules = $modmgr->getSubmodules($module, $onlyActivated);
        $module->modules = ArrayHelper::merge($dynSubmodules, $staticSubmodules);
        if (empty($module->modules)) {
            return $list;
        }

        // add submodules list
        foreach ($module->modules as $childId => $childModule) {
            if (!$onlyLoaded && is_array($childModule)) {
                $childModule = $module->getModule($childId);
                //$childModule = Yii::$app->getModule($module->uniqueId . '/' . $childId);
            }
            if ($childModule instanceof YiiBaseModule) {
                $list += static::modulesNamesList($childModule, $onlyActivated, $forModmgr, $onlyUniModule, $onlyLoaded, $indent);
            }
        }
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
        }
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
        $modmgr = static::instance();
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
    {
        $submodsConfig = static::submodsConfig();
        $result = [];
        foreach ($submodsConfig as $submodUniqueId => $config) {
            if (($pos = strrpos($submodUniqueId, '/')) !== false) {
                $parentUid = substr($submodUniqueId, 0, $pos);
                $moduleId = substr($submodUniqueId, $pos + 1);
            } else {
                $parentUid = ''; // Yii::$app
                $moduleId = $submodUniqueId;
            }
            if ($parentUid == $module->uniqueId) {
                $result[$moduleId] = $config;
            }
        }
        return $result;
    }

    /**
     * Recursively init all submodules of module.
     * @param \yii\base\Module $module
     */
    public static function initSubmodules($module)
    {
        $submodules = ArrayHelper::merge($module->modules, static::submodules($module));
        foreach ($submodules as $submoduleId => $submodule) {
            if (is_array($submodule)) $submodule = $module->getModule($submoduleId);
            if (empty($submodule)) continue;
            static::initSubmodules($submodule);
        }
    }

}
