<?php

namespace asb\yii2\common_2_170212\base;

use yii\web\Application;

class UniApplication extends Application
{
    const APP_TYPE_UNITED   = 'united';
    const APP_TYPE_BACKEND  = 'backend';
    const APP_TYPE_FRONTEND = 'frontend';
    const APP_TYPE_CONSOLE  = 'console';

    const APP_TEMPLATE_BASIC    = 'basic';
    const APP_TEMPLATE_ADVANCED = 'advanced';

    /** Application template */
    public $appTemplate = self::APP_TEMPLATE_BASIC;

    /** Application type */
    public $type = self::APP_TYPE_UNITED;

}
