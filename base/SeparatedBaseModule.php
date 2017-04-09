<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\helpers\ConfigsBuilder;
use asb\yii2\common_2_170212\i18n\TranslationsBuilder;
use asb\yii2\common_2_170212\web\RoutesInfo;

use Yii;
use yii\base\BootstrapInterface;
use yii\helpers\ArrayHelper;

use Exception;

/**
 * Package module class.
 * Package contain backend and frontend in common folder at same level:
 * backend and frontend controllers together, etc.
 * Package can have two Yii2-modules extends this class for backend and frontend separate.
 *
 * Unfortunatly such modules can't support inheritance as a UniModule.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class SeparatedBaseModule extends BaseModule implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function __construct($id, $parent = null, $config = [])
    {
        $addConfig = ConfigsBuilder::getConfig($this);
        $config = ArrayHelper::merge($addConfig, $config);//echo"module($id) result config:";var_dump($config);
        parent::__construct($id, $parent, $config);
    }

    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->_type)) {
            throw new Exception("You must set type of module in constructor");
        }
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        TranslationsBuilder::initTranslations($this);//var_dump(Yii::$app->i18n->translations);exit;
        static::$tc = $this->tcModule;

        //echo'before:<br>'.RoutesInfo::showRoutes($this->uniqueId);
        $this->addRoutes();
        //echo'after:<br>'.RoutesInfo::showRoutes($this->uniqueId);exit;

/*
        foreach ($this->modules as $module) {
            $module = This->getModule($module);
            $module->bootstrap(Yii::$app);
        }
*/
    }

}
