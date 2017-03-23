<?php

namespace asb\yii2\common_2_170212\base;

/**
 * Module manager interface.
 *
 * @author ASB <ab2014box@gmail.com>
 */
interface ModulesManagerInterface
{

    /**
     * Get submodules configs for module from modules manager
     * addition to static submodules configs defined in module's $config['modules'].
     * @param \yii\base\Module $module
     * @return array of submodules configs
     */
    public function getSubmodules($module);

    /**
     * Get bootstrap list addition to Yii::$app->bootstrap if $moduleUid empty.
     * Otherwise get bootstrap list for submodules of $parentModuleUid.
     * @param string $parentModuleUid module need bootstrap submodules, empty value means all list.
     * @return array in format [bootstrap class or module's uniqueId => config]
     */
    public function getBootstrapList($parentModuleUid = '');

}
