<?php

namespace asb\yii2\common_2_170212\models;

use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

use Exception;
use ReflectionClass;

/**
 * Base data model.
 *
 * NB! Can't support some functions for composite primary key.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
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
    public $orderBy = []; // ['id' => SORT_ASC]

    /** Page for current record */
    public $page = 1;

    /** Default page size, items */
    public $pageSize = 10;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $primaryKeys = static::primaryKey();
        if (isset($primaryKeys[0])) {
            $primaryKey = $primaryKeys[0];
            $this->orderBy = [$primaryKey => SORT_ASC];
        }
        
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

    protected static $_tableName = [];
    /**
     * @inheritdoc
     * You can use class constant TABLE_NAME
     * for define base (without prefix) table name
     * instead of definition method tableName().
     */
    public static function tableName()
    {
        $class = get_called_class();
        if (empty(static::$_tableName[$class])) {
            $moduleClass = static::moduleClass();
            $module = UniModule::getModuleByClassName($moduleClass);
            if (!empty($module)) {
                $params = $module->params;
                $rc = new ReflectionClass($class);
                do {
                    $nextClass = $rc->getName();
                    if (!empty($params[$nextClass]['tableName'])) {
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

        $this->page = $this->calcPage();
    }

    /**
     * Calculate page number for current record.
     */
    public function calcPage($currentQuery = null)
    {
        $page = 1;
        $primaryKeys = static::primaryKey();
        if (isset($primaryKeys[0])) {
            $primaryKey = $primaryKeys[0];
            $num = $this->getOrderNumber($this->$primaryKey, $currentQuery);
            if ($num > 0) {
                $page = (int) ceil($num / $this->pageSize);
            }
        }
        return $page;
    }

    /**
     * Get number of record with $id according to sort criteria $this->orderBy
     * NB!! Return illegal result if orderBy attributes not uniq
     */
    public function getOrderNumber($id, $currentQuery = null)
    {
        $item = $this->findOne($id);
        if (!isset($item)) return 0;

        if (empty($currentQuery)) {
            $query = self::find()->orderBy($this->orderBy);
        } else {
            $query = clone $currentQuery; //!
        }
        foreach ($this->orderBy as $prio_field => $direction) {
            $item_prio = $item[$prio_field];
            $where = [$direction == SORT_ASC ? '<=' : '>=', $prio_field, $item_prio];
            $query = $query->andWhere($where);
        }
        $num = $query->count();
        return intval($num);
    }

    /**
     * Set $orderBy property.
     * Need for calculate number of record in list (and page number).
     */
    public function setOrder($sortParam)
    {

        if (!isset($sortParam) || !is_string($sortParam)) return;

        if (substr($sortParam, 0, 1) == '-') {
            $direction = SORT_DESC;
            $sortParam = substr($sortParam, 1);
        } else {
            $direction = SORT_ASC;
        }
        if (array_key_exists($sortParam, $this->attributes)) {
            $this->orderBy = [$sortParam => $direction];
        }
    }

    /**
     * Try to get module class this model belong to.
     * Get latest module in inheritance chain.
     * @return string|false class name.
     */
    public static function moduleClass($moduleName = 'Module')
    {
        $result = false;

        $className = get_called_class();
        $refClass = new ReflectionClass($className);
        $ns = $refClass->getNamespaceName();

        $len = strlen($ns) - strlen(UniModule::$modelsSubdir);
        if (strrpos($ns, UniModule::$modelsSubdir) == $len) {
           $ns = substr($ns, 0, $len);
           $result = $ns . $moduleName;
        }

        return $result;
    }

    /**
     * Convert query's orderBy in array format [field => direction]
     * to 'sort' parameter for URL in format '[-]fieldName',
     * Consider only first element in array.
     */
    public function orderByToSort()
    {
        $sort = '';
        if (empty($this->orderBy) || !is_array($this->orderBy)) {
            return $sort;
        }
        foreach ($this->orderBy as $field => $direction) {
            $sort = ($direction == SORT_DESC ? '-' : '') . $field;
            break;
        }
        return $sort;
    }

    /**
     * Search ID for record placed near record with $id for order by $orderField in $direction.
     * @param integer $id initial record id
     * @param string $direction is 'up' or 'down'
     * @param yii\db\ActiveQuery|array $query select items query or additional query condition, for example ['parent_id' => NNN]
     * @param string $orderField means ordering only by this field
     * @return integer|false looked for record id
     */
    public function getNearId($id, $direction, $query = [], $orderField = 'prio')
    {
        if (!in_array($direction, ['up', 'down'])) return false;

        $primaryKeys = static::primaryKey();
        if (!isset($primaryKeys[0])) {
            return false;
        } else {
            $primaryKey = $primaryKeys[0];
        }

        $item = self::findOne([$primaryKey => $id]);
        if (!empty($item->$orderField)) {
            $prio = $item->$orderField;
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

        if (is_array($query)) {
            $where = $query;
            $query = self::find()->where($where);
        }
        $query->andWhere($andWhere)
            ->orderBy($orderBy)
            ->limit(1);
        $swapItem = $query->one();
        $swapId = !empty($swapItem->$primaryKey) ? $swapItem->$primaryKey : false;
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
    {
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
        }

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
