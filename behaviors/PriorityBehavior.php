<?php

namespace asb\yii2\common_2_170212\behaviors;

use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\base\InvalidParamException;
use yii\base\InvalidValueException;
use Yii;

use Closure;

/**
 * Set priority attribute for new item according to owner attribute (by default - user id).
 *
 * Example of usage in model:
 * ```php
 *     public function behaviors()
 *     {
 *         return [
 *             'priority' => [
 *                 'class' => PriorityBehavior::className(),
 *                 'modelClass' => static::className(),
 *                 'priorityAttribute' => 'priority',
 *                 'ownerAttribute' => 'owner_id',
 *                 'funcGetOwnerAttributeValue' => function() {
 *                     return $this->owner_id;  // $this is object represent created record
 *                 }
 *             ],
 *         ];
 *     }
 * ```
 *
 * Not support now composite owner attribute. //todo
 *
 * @author ASB <ab2014box@gmail.com>
 */
class PriorityBehavior extends AttributeBehavior
{
    /** @var string */
    public $priorityAttribute = 'prio';

    /** @var string */
    public $ownerAttribute = 'user_id';

    /**
     * @var Closure|false|null
     * If false don't consider owner attribute.
     */
    public $funcGetOwnerAttributeValue;

    /** @var Model */
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
                ActiveRecord::EVENT_BEFORE_INSERT => $this->priorityAttribute,
                ActiveRecord::EVENT_BEFORE_UPDATE => $this->priorityAttribute,
            ];
        }
    }

    /**
     * Get owner attribute value. Logined user id by default.
     * @return mixed|null
     */
    public function getDefaultOwnerAttributeValue()
    {
        if (Yii::$app->has('user')) {
            $userId = Yii::$app->get('user')->id;
            if ($userId === null) { // something wrong
                throw new InvalidValueException('Empty owner ID');
            }
            return $userId;
        }
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        if ($this->value === null) {
            if ($event->sender->isNewRecord // set priority for new record
             || ($this->getOwnerValue() != $event->sender->oldAttributes[$this->ownerAttribute])) { // update priority if change owner
                return $this->getPriority();
            } else {
                $ownerAttribute = $this->ownerAttribute;
                return $event->sender->$ownerAttribute;
            }
        }
        return parent::getValue($event);
    }

    /**
     * @return int
     */
    protected function getPriority()
    {
        $priority = 1; // default if not found
        $modelClass = $this->modelClass;
        $query = $modelClass::find();
        if ($this->funcGetOwnerAttributeValue !== false) {
            $owherValue = $this->getOwnerValue();
            if (isset($owherValue)) {
                $query->where([$this->ownerAttribute => $owherValue]);
            }
        }
        $max = $query->max($this->priorityAttribute);
        if (!empty($max)) {
            $priority = $max + 1;
        }
        return $priority;
    }

    /**
     * @return mixed|null
     */
    protected function getOwnerValue()
    {
        if ($this->funcGetOwnerAttributeValue !== false) {
            if ($this->funcGetOwnerAttributeValue instanceof Closure) {
                $funcGetVal = $this->funcGetOwnerAttributeValue;
            } else {
                $funcGetVal = [$this, 'getDefaultOwnerAttributeValue'];
            }
            $owherValue = call_user_func($funcGetVal);
            return $owherValue;
        }
    }

}
