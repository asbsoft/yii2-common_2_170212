<?php

namespace asb\yii2\common_2_170212\models;

use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;

use Exception;

/**
 * Base data model with multi-language support
 *
 * @author ASB <ab2014box@gmail.com>
 */
class DataModelMultilang extends BaseDataModel
{
    public static $i18n_join_model;
    public static $i18n_join_prim_key;

    public $languages;
    public $langCodeMain;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty(static::$i18n_join_model)) {
            throw new Exception("Model {$this::className()} must have static member 'i18n_join_model'");
        }
        if (empty(static::$i18n_join_prim_key)) {
            throw new Exception("Model {$this::className()} must have static member 'i18n_join_prim_key'");
        }

        $langHelper = empty($this->module->langHelper)
            ? LangHelper::className()
            : $this->module->langHelper;
        $editAllLanguages = empty($this->module->params['editAllLanguages']) ? false : $this->module->params['editAllLanguages'];
        $this->languages = $langHelper::activeLanguages($editAllLanguages);
        if (empty($this->langCodeMain) ) {
            $this->langCodeMain = $langHelper::normalizeLangCode(Yii::$app->language);
        }
    
    }

    /**
     * @var array of i18n-models in format [ langCode => i18n-model ]
     */
    protected $_i18n = [];

    /**
     * Get associated with this model array of i18n-models.
     * Use $this->i18n[$langCode] for modification in $this->load().
     * @return array of i18n-models in format langCode => i18nModel
     */
    public function getI18n()
    {
        if (empty($this->_i18n)) {
            $this->_i18n = $this->prepareI18nModels();
        }
        return $this->_i18n;
    }

    /**
     * Declares a `has-many` relation.
     * @return ActiveQueryInterface query object, not data
     */
    public function getJoined()
    {
        return $this->hasMany(
            $this->module->model(static::$i18n_join_model)->className(),
            [ static::$i18n_join_prim_key => 'id' ]
        );
    }

    /**
     * Prepare i18n-models array, create new if need.
     * No error if new language add or not found joined record - will create new i18n-model with default values.
     * @return array in format langCode => i18n-model's object
     */
    public function prepareI18nModels()
    {//echo __METHOD__;var_dump($this->attributes);
        $mI18n = $this->getJoined()->all();//var_dump($mI18n);exit;
        $modelsI18n = [];
        foreach ($mI18n as $modelI18n) {
            $modelsI18n[$modelI18n->lang_code] = $modelI18n;
        }
        foreach ($this->languages as $langCode => $lang) {
            if (empty($modelsI18n[$langCode])) {
                $newI18n = $this->module->model(static::$i18n_join_model);
                $modelsI18n[$langCode] = $newI18n->loadDefaultValues();
                $modelsI18n[$langCode]->lang_code = $langCode;
            }
        }//var_dump($modelsI18n);exit;
        return $modelsI18n;
    }

    /**
     * @var array validation errors [langcode][attribute name] => array of errors)
     */
    protected $_errorsI18n = [];
    /**
     * @return bool whether there is any error.
     */
    public function hasI18nErrors()
    {
        return !empty($this->_errorsI18n);
    }
    /**
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     */
    public function getI18nErrors($langCode = null, $attribute = null)
    {
        if ($langCode === null) {
            return empty($this->_errorsI18n) ? [] : $this->_errorsI18n;
        }
        if ($attribute === null) {
            return empty($this->_errorsI18n[$langCode]) ? [] : $this->_errorsI18n[$langCode];
        }
        return isset($this->_errorsI18n[$langCode][$attribute]) ? $this->_errorsI18n[$langCode][$attribute] : [];
    }

    /**
     * @inheritdoc
     * @return boolean whether `load()` found the expected form in `$data`.
     */
    public function load($data, $formName = null)
    {//echo __METHOD__;var_dump($data);
        $result = parent::load($data, $formName);//var_dump($result);var_dump($this->attributes);
        if ($result) {
            $i18nFormName = $this->module->model(static::$i18n_join_model)->formName();
            foreach ($this->languages as $langCode => $lang) {
                if (!empty($data[$i18nFormName][$langCode])) {
                    $i18nResult = $this->i18n[$langCode]->load($data[$i18nFormName][$langCode], '');//var_dump($i18nResult);var_dump($this->i18n[$langCode]->attributes);
                    if (!$i18nResult) {
                        $this->_errorsI18n[$langCode] = $this->i18n[$langCode]->errors;
                        $result = false;
                    }
                }
            }
        }//var_dump($result);var_dump($this->_errorsI18n);exit;
        return $result;
    }

    /**
     * Save multilang models.
     * Empty i18n-model will not save if it has method hasData() that return false
     * Existing i18n-model will delete if clean it's data.
     * Run it only after save (creat) main model $this - to set required $this->id.
     *
     * @param bool $runValidation whether to perform validation (calling [[\yii\base\Model::validate()|validate()]])
     * @param array $attributeNames list of attribute names that need to be saved. Defaults to `null`,
     * @return bool whether the saving succeeded (i.e. no validation errors occurred).
     */
    public function saveMultilang($runValidation = true, $attributeNames = null)
    {//echo __METHOD__;
        if (empty($this->id)) {
            throw new Exception("Model {$this::className()} can't save multilang data because not set ID in main model");
        }
        $result = true;
        foreach ($this->languages as $langCode => $lang) {
            $modelI18n = $this->i18n[$langCode];
            $joinKey = static::$i18n_join_prim_key;
            $modelI18n->$joinKey = $this->id;//var_dump($modelI18n->attributes);

            if ($runValidation) {
                $validResult = $modelI18n->validate($attributeNames);//echo"validate($langCode):";var_dump($validResult,$modelI18n->errors);
                $result = $result && $validResult;
            }
            if ($result) {
                if (!method_exists($modelI18n, 'hasData') || $modelI18n->hasData()) { // check model data not empty
                    $i18nResult = $modelI18n->save(false, $attributeNames);//echo $langCode;var_dump($i18nResult);var_dump($modelI18n->errors);exit;
                    if (!$i18nResult) {//echo'save err:';var_dump($modelI18n->errors);
                        $result = false;
                    }
                } elseif (!empty($modelI18n->id)) { // if empty i18n-data don't save it or delete it if exists
                    $modelI18n->delete();
                }
            }
            if (!$result) {
                $this->_errorsI18n[$langCode] = $modelI18n->errors;
            }
        }//var_dump($result,$this->_errorsI18n);exit;
        return $result;
    }

    /**
     * @inheritdoc
     * Delete also i18n-records from joined table
     *
     * @return integer|false the number of rows deleted, or `false` if the deletion is unsuccessful for some reason.
     * Note that it is possible that the number of rows deleted is 0, even though the deletion execution is successful.
     * @throws \Exception in case delete failed.
     */
    public function delete()
    {
        $id = $this->id;
        $result = false;
        $transaction = static::getDb()->beginTransaction();
        try {
            $numRows = 0;
            $result = true;
            $modelsI18n = $this->i18n;//var_dump($modelsI18n);exit;
            foreach ($modelsI18n as $modelI18n) {
                $result = $modelI18n->deleteInternal();
                if ($result === false) break;
                $numRows += $result;
            }
            if ($result !== false) {
                $result = $this->deleteInternal();
            }
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $numRows += $result;
                $result = $numRows;
                $transaction->commit();
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $result;
    }

}
