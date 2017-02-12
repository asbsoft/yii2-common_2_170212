<?php

namespace asb\yii2\common_2_170212\models;

use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

/**
 * Base data model.
 *
 * NB! Can't support now composite primary key.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class BaseDataModel extends ActiveRecord
{
    /** Module-container for this model */
    public $module;

    /** Module's config */
    //public $config = []; //deprecated

    /** Common translation category for all module */
    public $tcModule = '';
    /** Common translation category for all models in this module */
    public $tcModels = '';
    /** Translation category personal for this model */
    public $tc = '';

    /** @var array default order */
    public $orderBy = ['id' => SORT_ASC];

    /** Page for current record */
    public $page = 1;

    /** Default page size, items */
    public $pageSize = 10;

    /**
     * @inheritdoc
     */
/*
    public function __construct($config = [])
    {//var_dump($config);
        parent::__construct($config);
    }
*/

    /**
     * @inheritdoc
     */
    public function init()
    {//echo __METHOD__.'<br>';
        parent::init();

        if (empty($this->module)) {
            //throw new \Exception("Model {$this::className()} must have module attribute");//!!

            //$class = $this::className();
            //$modelNamespace = substr($class, 0, strrpos($class, '\\'));
            $rc = new \ReflectionClass($this);
            $modelNamespace = $rc->getNamespaceName();//var_dump($modelNamespace);
            $moduleNamespace = substr($modelNamespace, 0, strrpos($modelNamespace, '\\'));
            $moduleClassName = $moduleNamespace . '\Module';//var_dump($moduleClassName);exit;

            //?? problem: $moduleClassName may be a name of parent class but need latest module class in inheritance hierarchy
            $this->module = UniModule::getModuleByClassname($moduleClassName);//echo"found module uid={$this->module->uniqueId} noname:";var_dump($this->module->noname);
            if ($this->module instanceof UniModule && $this->module->noname) {
                $moduleClass = UniModule::findModuleByNamespace($moduleNamespace);//echo"UniModule::findModuleByNamespace($moduleNamespace)={$moduleClass}";
                if (!empty($moduleClass)) {
                    $module = UniModule::getModuleByClassname($moduleClass);
                    if (!$module instanceof UniModule || !$module->noname) {
                        $this->module = $module;
                    }
                }
            }
        }//var_dump($this->module);exit;

        $this->prepare();
    }

    /**
     * Part of init() can repeat after (re)set $this->module.
     */
    public function prepare()
    {    
        //var_dump($this->module->templateTransCat);
        if (!empty($this->module->templateTransCat)) {
            $this->tcModule = $this->module->tcModule;
            $this->tcModels = $this->module->tcModels;
            
            $id = Inflector::camel2id(basename($this->className()));
            $this->tc = str_replace('*', "/model-{$id}", $this->module->templateTransCat);//var_dump($this->tc);
        }
        //deprecated: if (isset($this->module->config)) $this->config = $this->module->config;//var_dump($this->config);
        if (!empty($this->module->params['pageSize'])) {
            $this->pageSize = $this->module->params['pageSize'];
        }
    }

    /**
     * @inheritdoc
     * Sometime need clear table name without enclosure '{{%...}}'.
     * Use class constant TABLE_NAME for clean table name.
     */
    public static function tableName()
    {
        $class = get_called_class();
        if (@constant("{$class}::TABLE_NAME")) {
            return '{{%' . static::TABLE_NAME . '}}';
        } else {
            return parent::tableName();
        }
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $this->page = $this->calcPage();//var_dump($this->id);var_dump($this->page);exit;
    }

    /**
     * Calculate page number for current record.
     */
    public function calcPage($currentQuery = null)
    {//echo __METHOD__;//var_dump($currentQuery);
        $page = 1;
        $num = $this->getOrderNumber($this->id, $currentQuery);
        if ($num > 0) $page = (int) ceil($num / $this->pageSize);//echo __METHOD__.":ceil($num/{$this->pageSize})={$page}<br>";exit;
        return $page;
    }

    /**
     * Get number of record with $id according to sort criteria $this->orderBy
     * NB!! Return illegal result if orderBy attributes not uniq
     */
    public function getOrderNumber($id, $currentQuery = null)
    {//echo __METHOD__;var_dump($id);
        $item = $this->findOne($id);//var_dump($item->attributes);
        if (!isset($item)) return 0;

        //var_dump($this->orderBy);exit;
        if (empty($currentQuery)) {
            $query = self::find()->orderBy($this->orderBy);
        } else {
            $query = clone $currentQuery; //!
        }//var_dump($this->orderBy);
        foreach ($this->orderBy as $prio_field => $direction) {
            $item_prio = $item[$prio_field];
            $where = [$direction == SORT_ASC ? '<=' : '>=', $prio_field, $item_prio];//var_dump($where);
            $query = $query->andWhere($where);
        }//var_dump($query);
        $num = $query->count();//var_dump($num);exit;
        return intval($num);
    }

    /**
     * Set $orderBy property.
     * Need for calculate number of record in list (and page number).
     */
    public function setOrder($sortParam)
    {//echo __METHOD__;var_dump($sortParam);var_dump($this->orderBy);

        if (!isset($sortParam) || !is_string($sortParam)) return;

        if (substr($sortParam, 0, 1) == '-') {
            $direction = SORT_DESC;
            $sortParam = substr($sortParam, 1);
        } else {
            $direction = SORT_ASC;
        }
        if (array_key_exists($sortParam, $this->attributes)) {
            $this->orderBy = [$sortParam => $direction];//var_dump($this->orderBy);
        }
    }

    /**
     * Try to get module class this model belong to.
     * Get latest module in inheritance chain.
     * @return string|false class name.
     */
    public static function moduleClass($moduleName = 'Module')
    {//echo __METHOD__;
        $result = false;

        $class = get_called_class();//var_dump($class);
        $ns = dirname($class);//var_dump($ns);

        $len = strlen($ns) - strlen(UniModule::$modelsSubdir);
        if (strrpos($ns, UniModule::$modelsSubdir) == $len) {
           $ns = substr($ns, 0, $len);//var_dump($ns);
           $result = $ns . $moduleName;
        }//var_dump($result);exit;    

        return $result;
    }

}
