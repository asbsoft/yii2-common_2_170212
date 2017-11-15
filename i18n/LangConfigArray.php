<?php

namespace asb\yii2\common_2_170212\i18n;

/**
 * This class represents system languages.
 * Languages infos here saved in array in config-file.
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
