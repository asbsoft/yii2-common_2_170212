<?php

namespace asb\yii2\common_2_170212\base;

/**
 * Module implements this interface can't have additional (to own config) submodules
 * that will get by Modules manager.
 * ModulesManager module can't have such submodules to avoid infinitive loop.
 *
 * @author ASB <ab2014box@gmail.com>
 */
interface IWithoutUniSubmodules
{
}
