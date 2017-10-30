<?php

namespace asb\yii2\common_2_170212\i18n;

use yii\base\Object;

/**
 * Basic class to represents system language.
 */
abstract class LangConfigBasic extends Object
{
    /** Parameters with default values */
    public $params = [

        /** Cookie name for save language, set false to disable saving */
        'cookieDefaultLanguage' => 'def-lang',

        /** Cookie expired time */
        'langCookieExpiredSec' => 1, // 1sec - don't save lang in cookie

        /** Application type */
        'appTypePrefix' => false
    ];

    /**
     * Get languages infos.
     * @return array
     */
    abstract public function getLangsConfig();

}
