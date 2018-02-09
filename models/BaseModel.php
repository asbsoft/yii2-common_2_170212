<?php

namespace asb\yii2\common_2_170212\models;

use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\base\Model;
use yii\helpers\Inflector;

use Exception;
use ReflectionClass;

/**
 * Base model.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class BaseModel extends Model
{
    /** Module-container for this model */
    public $module;

    /** Common translation category for all module */
    public $tcModule = '';
    /** Common translation category for all models in this module */
    public $tcModels = '';
    /** Translation category personal for this model */
    public $tc = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->module)) {
            $rc = new ReflectionClass($this);
            $modelNamespace = $rc->getNamespaceName();
            $moduleNamespace = substr($modelNamespace, 0, strrpos($modelNamespace, '\\'));
            $moduleClassName = $moduleNamespace . '\Module';

            //?? problem: $moduleClassName may be a name of parent class but need latest module class in inheritance hierarchy
            $this->module = UniModule::getModuleByClassname($moduleClassName);
            if ($this->module instanceof UniModule && $this->module->noname) {
                $moduleClass = UniModule::findModuleByNamespace($moduleNamespace);
                if (!empty($moduleClass)) {
                    $module = UniModule::getModuleByClassname($moduleClass);
                    if (!$module instanceof UniModule || !$module->noname) {
                        $this->module = $module;
                    }
                }
            }
        }

        if (empty($this->module)) {
            throw new Exception("Model {$this::className()} must have 'module' attribute");
        }

        $this->prepare();
    }

    /**
     * Part of init() can repeat after (re)set $this->module.
     */
    public function prepare()
    {    
        if (!empty($this->module->templateTransCat)) {
            $this->tcModule = $this->module->tcModule;
            $this->tcModels = $this->module->tcModels;
            
            $id = Inflector::camel2id(basename($this->className()));
            $this->tc = str_replace('*', "/model-{$id}", $this->module->templateTransCat);
        }
        if (!empty($this->module->params['pageSize'])) {
            $this->pageSize = $this->module->params['pageSize'];
        }
    }

}
