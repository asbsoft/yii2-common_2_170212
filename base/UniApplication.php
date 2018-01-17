<?php

namespace asb\yii2\common_2_170212\base;

use yii\web\Application;
use asb\yii2\common_2_170212\web\RoutesBuilder;
use asb\yii2\common_2_170212\web\RoutesInfo;

use Yii;

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

    /** Alternate bower alias */
    public $altBowerAlias = '@vendor/bower-asset';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        RoutesBuilder::saveAppRoutes($this);
    }

    /**
     * @inheritdoc
     */
    public function setVendorPath($path)
    {
        parent::setVendorPath($path);

        $bowerDir = Yii::getAlias('@bower');
        if (!is_dir($bowerDir)) {
            $altDir = Yii::getAlias($this->altBowerAlias);
            if (is_dir($altDir)) {
                Yii::setAlias('@bower', $this->altBowerAlias);
            }
        }
    }

    /**
     * @return string application composite type
     */
    public function getAppKey()
    {
        return $this->appTemplate . '-' . $this->type;
    }

}
