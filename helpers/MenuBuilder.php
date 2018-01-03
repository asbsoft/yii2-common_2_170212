<?php

namespace asb\yii2\common_2_170212\helpers;

use asb\yii2\common_2_170212\base\BaseModule;
use asb\yii2\common_2_170212\base\ModulesManager;
use asb\yii2\common_2_170212\rbac\AuthHelper;
use asb\yii2\common_2_170212\i18n\TranslationsBuilder;
use Yii;

class MenuBuilder
{
    /**
     * Recursively collect menu items array with submodules start links for $module.
     * @param string $routesType
     * @param Module $module
     * @return array
     */
    public static function modulesMenuitems($routesType = 'main', $module = null)
    {
        if (empty($module)) $module = Yii::$app;

        //$submodules = $module->modules; // without ModuleManager
        //$submodules = ModulesManager::submodules($module); // only dynamicly added modules
        $submodules = ArrayHelper::merge($module->modules, ModulesManager::submodules($module));
        
        $itemsModules = [];
        foreach ($submodules as $submoduleId => $submodule) {
            if (is_array($submodule)) $submodule = $module->getModule($submoduleId);
            if (empty($submodule)) continue;

            $nextItem = false;
            $startLinkInfo = BaseModule::startLink($submodule->uniqueId, $routesType);
            if (!empty($startLinkInfo)) {
                $nextItem = [
                    'label' => $startLinkInfo['label'],
                    'url' => isset($startLinkInfo['route']) ? $startLinkInfo['route'] : $startLinkInfo['link']
                ];
            
                if (!empty($startLinkInfo['route'][0])) {
                    $actionUid = trim($startLinkInfo['route'][0], '/');
                    $can = AuthHelper::canUserRunAction($actionUid, Yii::$app->user);
                    if (!$can) $nextItem = false;
                }
            }

            $itemsSubmodules = static::modulesMenuitems($routesType, $submodule);
            if (empty($itemsSubmodules)) {
                if (!empty($nextItem)) $itemsModules[] = $nextItem;
            } else {
                if (empty($nextItem)) {
                    //$tc = TranslationsBuilder::getBaseTransCategory($submodule) . '/module';
                    $label = empty($submodule->params['label'])
                           ? Yii::t('common', "Submenu for '{uid}'", ['uid' => $submodule->uniqueId])
                           //: Yii::t($tc, $submodule->params['label']);
                           : $submodule->params['label']; // must be already translated
                } else {
                    $label = $nextItem['label'];
                    $itemsSubmodules = ArrayHelper::merge([$nextItem], $itemsSubmodules);
                }
                // make submenu
                $itemsModules[] = [
                    'label' => $label,
                    'items' => $itemsSubmodules,
                ];
            }
        }
        return $itemsModules;
    }

}
