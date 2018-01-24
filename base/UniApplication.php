<?php

namespace asb\yii2\common_2_170212\base;

use yii\web\Application;
use asb\yii2\common_2_170212\web\RoutesBuilder;
use asb\yii2\common_2_170212\web\RoutesInfo;
use asb\yii2\common_2_170212\web\WebFile;

use yii\base\InvalidRouteException;
use Yii;

class UniApplication extends Application
{
    const APP_TYPE_UNITED   = 'united';
    const APP_TYPE_BACKEND  = 'backend';
    const APP_TYPE_FRONTEND = 'frontend';
    const APP_TYPE_CONSOLE  = 'console';

    const APP_TEMPLATE_BASIC    = 'basic';
    const APP_TEMPLATE_ADVANCED = 'advanced';

    /** If true do not throw exception on illegal route in runAction() for renderPartial-(sub)pages */
  //public static $loyalModeRunAction = true;//todo
    public static $loyalModeRunAction = false;

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
     * Get application key describes kind of applicayion
     * @return string application composite type
     */
    public function getAppKey()
    {
        return $this->appTemplate . '-' . $this->type;
    }

    /**
     * Get application key describes kind of applicayion
     * @param yii\web\Application $app
     * @return string application composite type
     */
    public static function appKey($app)
    {
        return $appKey = $app instanceof self ? $app->getAppKey() : 'unknown';
    }

    /**
     * @inheritdoc
     * Loyal mode: message instead of exception.
     */
    public function runAction($route, $params = [])
    {
        try {
            $result = parent::runAction($route, $params);
        } catch (InvalidRouteException $e) {
            $msg = __FUNCTION__ . ': ' . $e->getMessage();
            Yii::error($msg);

            $ext = pathinfo($route, PATHINFO_EXTENSION);

            if (empty(static::$loyalModeRunAction)
              || in_array(strtolower($ext), WebFile::$allowedExtensions)  // if image file then throw exception to WebFile
              // todo: if not renderPartial page
            ) {
                throw new InvalidRouteException($msg, $e->getCode());
            }

// todo
            // echo without exception - only for renderPartial-parts of page, not for independent pages
            $result = '';
            if (YII_DEBUG) {
                $result = $msg;
            }
        }
        return $result;
    }

}
