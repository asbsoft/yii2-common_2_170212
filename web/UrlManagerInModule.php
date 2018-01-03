<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\base\UniModule;

use Yii;
//use yii\web\UrlManager as YiiUrlManager;

/**
 * Composite URL manager.
 * If exists module with uniqueId $this->sitetreeModuleUniqueId than load UrlManager from there.
 * Otherwise use standard Yii URL manager.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class UrlManagerInModule extends UrlManagerBase
{
    public $sitetreeModuleUniqueId = 'sys/sitetree';

    public $sitetreeManagerAlias = 'SitetreeManager';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->enablePrettyUrl = true;
        //$this->enableStrictParsing = true;
        $this->showScriptName = false;
    }
    
    protected static $_sitetreeManager;
    /** Find Sitetree module from system loaded modules */
    public function getSitetreeManager()
    {    
        if (empty(static::$_sitetreeManager)) {
            $module = Yii::$app->getModule($this->sitetreeModuleUniqueId);
            if (!empty($module) && $module instanceof UniModule) {
                $mgr = $module->getDataModel($this->sitetreeManagerAlias);
                if (empty($mgr->rules)) $mgr->rules = Yii::$app->urlManager->rules;
                static::$_sitetreeManager = $mgr;
            }
        }
        return static::$_sitetreeManager;
    }

    /** 
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        $mgr = $this->getSitetreeManager();
        if (empty($mgr)) {
            $result = parent::parseRequest($request);
        } else {
            $result = $mgr->parseRequest($request);
        }
        return $result;
    }

    /** 
     * @inheritdoc
     */
    public function createUrl($params)
    {
        $mgr = $this->getSitetreeManager();
        if (empty($mgr)) {
            $result = parent::createUrl($params);
        } else {
            $result = $mgr->createUrl($params);
        }
        return $result;
    }

}
