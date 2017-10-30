<?php

namespace asb\yii2\common_2_170212\i18n;

/**
 * Tis class represents system language.
 * Languages infos save static in config-array.
 */
class LangConfigArray extends LangConfigBasic
{
    /** Languages definition file name */
    public $langsConfigFname; // 

    /**
     * @inheritdoc
     */
    public function getLangsConfig()
    {
        return include($this->langsConfigFname);
    }

}
