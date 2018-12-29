<?php

namespace asb\yii2\common_2_170212\behaviors;

use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\base\InvalidParamException;
use yii\base\Exception;

/**
 * Set UniqID attribute for new item.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class UniqidBehavior extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive the uniqid value
     */
    public $uniqidAttribute = 'uniqid';
    /**
     * @var string query alias for unique attribute
     */
    public $queryAlias;
    /**
     * @var string
     */
    public $prefix = '';
    /**
     * @var int 
     */
    public $maxTry = 100;
    /**
     * @var int sleep interval between repeat generation, in mikroseconds
     */
    public $sleepInterval = 1000;
    /**
     * @var bool
     */
    public $moreEntropy = false;

    /**
     * @var string
     */
    public $modelClass;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->modelClass)) {
            throw new InvalidParamException("Not set 'modelClass'");
        }

        if (empty($this->attributes)) {
            $this->attributes = [
                ActiveRecord::EVENT_BEFORE_INSERT => $this->uniqidAttribute,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        if ($this->value === null) {
            $try = 0;
            while (true) {
                $value = $this->getUniqValue();
                $modelClass = $this->modelClass;
                $uniqidAttribute = empty($this->queryAlias) ? $this->uniqidAttribute : "`$this->queryAlias`.`{$this->uniqidAttribute}`";
                $exists = $modelClass::find()
                    ->where([$uniqidAttribute => $value])
                    ->exists();
                if (!$exists) {
                    $this->value = $value;
                    break;
                }
                if (++$try > $this->maxTry) {
                    throw new Exception("Can't generate unique ID");
                }
                usleep($this->sleepInterval);
            }
            return $this->value;
        }
        return parent::getValue($event);
    }

    protected function getUniqValue()
    {
        if (function_exists('com_create_guid')) {
            return $this->prefix . trim(com_create_guid(), '{}');
        } else {
            return $this->prefix . md5(uniqid('', $this->moreEntropy));
        }
    }

}
