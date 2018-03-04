<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\behaviors\ParamsAccessBehaviour;

use yii\web\Application;
use asb\yii2\common_2_170212\web\RoutesBuilder;
use asb\yii2\common_2_170212\web\RoutesInfo;
use asb\yii2\common_2_170212\web\WebFile;

use yii\helpers\ArrayHelper;
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
     * Add to modules' behaviors from its' $params['behaviors']
     * and default 'params-access' behavior.
     * @return array the behavior configurations.
     */
    public function behaviors()
    {
        $behaviors = ArrayHelper::merge(parent::behaviors(), [
            'params-access' => [
                'class' => ParamsAccessBehaviour::className(),
                'defaultRole' => 'roleRoot',
                'readonlyParams' => [
                    'behaviors', // parameters which nobody can edit, will overwrite another rules
                ],
            ],
        ]);
        if (isset($this->params['behaviors'])) {
            $behaviors = ArrayHelper::merge($behaviors, $this->params['behaviors']);
        }
        return $behaviors;
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
     * Loyal version of runAction() for partial rendering action inside another template: message instead of exception.
     * @param string $route the route that specifies the action.
     * @param array $params the parameters to be passed to the action
     * @return mixed the result of the action.
     */
    public function renderAction($route, $params = [])
    {
        try {
            $result = parent::runAction($route, $params);
        } catch (InvalidRouteException $e) {
            $msg = __FUNCTION__ . ': ' . $e->getMessage();
            Yii::error($msg);

            $result = '';
            if (YII_DEBUG) {
                $result = $msg;
            }
        }
        return $result;
    }

}
