<?php

namespace asb\yii2\common_2_170212\models;

use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

use ReflectionClass;

/**
 * Base data model.
 *
 * NB! Can't support now some functions for composite primary key.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class BaseDataModel extends ActiveRecord
{
    /** Module-container for this model */
    public $module;

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
    public function init()
    {//echo __METHOD__.'<br>';
        parent::init();

        if (empty($this->module)) {
            $rc = new ReflectionClass($this);
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

        if (empty($this->module)) {
            throw new \Exception("Model {$this::className()} must have 'module' attribute");
        }

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

    protected static $_tableName = [];
    /**
     * @inheritdoc
     * You can use class constant TABLE_NAME
     * for define base (without prefix) table name
     * instead of definition method tableName().
     */
    public static function tableName()
    {
        $class = get_called_class();//echo __METHOD__."@{$class}<br>";
        if (empty(static::$_tableName[$class])) {
            $moduleClass = static::moduleClass();
            $module = UniModule::getModuleByClassName($moduleClass);
            if (!empty($module)) {
                $params = $module->params;//echo $class.'::'.__FUNCTION__."()@module:{$module->uniqueId}";var_dump($params);
                $rc = new ReflectionClass($class);
                do {
                    $nextClass = $rc->getName();//var_dump($nextClass);
                    if (!empty($params[$nextClass]['tableName'])) {//echo"FOUND for $class: params[$nextClass]['tableName']={$params[$nextClass]['tableName']}<br>";
                        static::$_tableName[$class] = $params[$nextClass]['tableName']; // dont add '{{%}}' here - it may be alian table
                        break;
                    }
                    $rc = $rc->getParentClass();
                } while (!empty($rc));
            }
            if (empty(static::$_tableName[$class]) && @constant("{$class}::TABLE_NAME")) { // deprecated constant, leave for reverse compatibility
                static::$_tableName[$class] = '{{%' . static::TABLE_NAME . '}}';
            }
            if (empty(static::$_tableName[$class])) {
                static::$_tableName[$class] = parent::tableName();
            }
        }
        return static::$_tableName[$class];
    }

    /**
     * Returns actual name of table associated with this AR class (with prefix).
     * @return string
     */
    public static function rawTableName()
    {
        $tableName = static::tableName();
        $rawName = static::getDb()->schema->getRawTableName($tableName);
        return $rawName;
    }

    /**
     * Returns base (without prefix) name of table associated with this AR class.
     * @return string
     */
    public static function baseTableName()
    {
        $tableName = static::tableName();
        $cleanName = preg_replace('/{{%(.*?)}}/', '\1', $tableName);
        return $cleanName;
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

    /**
     * Convert query's orderBy in array format [field => direction]
     * to 'sort' parameter for URL in format '[-]fieldName',
     * Consider only first element in array.
     */
    public function orderByToSort()
    {//echo __METHOD__;var_dump($this->orderBy);
        $sort = '';
        if (empty($this->orderBy) || !is_array($this->orderBy)) {
            return $sort;
        }
        foreach ($this->orderBy as $field => $direction) {
            $sort = ($direction == SORT_DESC ? '-' : '') . $field;
            break;
        }//var_dump($sort);exit;
        return $sort;
    }

    /**
     * Search ID for record placed near record with $id for order by $orderField in $direction.
     * @param integer $id initial record id
     * @param string $direction is 'up' or 'down'
     * @param array $where additional query condition, for example ['parent_id' => NNN]
     * @param string $orderField means ordering only by this field
     * @return integer|false looked for record id
     */
    public function getNearId($id, $direction, $where = [], $orderField = 'prio')
    {//echo __METHOD__."($id,$direction)<br>";var_dump($where);
        if (!in_array($direction, ['up', 'down'])) return false;

        $item = self::findOne(['id' => $id]);
        if (!empty($item->prio)) {
            $prio = $item->prio;
        } else {
            return false;
        }
        if ($direction == 'down') {
            $andWhere = ['>', $orderField, $prio];
            $orderBy = [$orderField => SORT_ASC];
        } else {
            $andWhere = ['<', $orderField, $prio];
            $orderBy = [$orderField => SORT_DESC];
        }
        $swapItem = self::find()
            ->where($where)
            ->andWhere($andWhere)
            ->orderBy($orderBy)
            ->limit(1)
            ->one();
        $swapId = !empty($swapItem->id) ? $swapItem->id : false;//var_dump($swapId);exit;
        return $swapId;
    }

    /**
     * Swap order field values for 
     * @param integer $id1
     * @param integer $id2
     * @param string $orderField
     * @return boolean
     */
    public function swapPrio($id1, $id2, $orderField = 'prio')
    {//echo __METHOD__."($id1,$id2)<br>";
        $item1 = self::findOne(['id' => $id1]);
        if (!empty($item1->$orderField)) {
            $prio1 = $item1->$orderField;
        } else {
            return false;
        }
        $item2 = self::findOne(['id' => $id2]);
        if (!empty($item2->$orderField)) {
            $prio2 = $item2->$orderField;
        } else {
            return false;
        }//echo "$id1.$orderField=$prio1,$id2.$orderField=$prio2<br>";//exit;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $item1->$orderField = $prio2;
            $n1 = $item1->updateInternal();
            $item2->$orderField = $prio1;
            $n2 = $item2->updateInternal();
            if ($n1 && $n2) $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error($e);
            if (YII_DEBUG) throw $e;
        }
        return true;
    }

}
