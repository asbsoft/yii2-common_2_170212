<?php

namespace asb\yii2\common_2_170212\validators;

use yii\validators\Validator;

use Yii;

/**
 * Either validator.
 *
 * Usage in model example:
 * ```php
 * public function rules() {
 *     return [
 *         [['attribute1', 'attribute2', 'attribute3', ...], EitherValidator::className()],
 *     ];
 * }
 * ```
 * @see http://stackoverflow.com/questions/7081066/yii-form-model-validation-either-one-is-required
 */
class EitherValidator extends Validator
{
    public $tc = 'common';

    /**
     * @inheritdoc
     */
    public function validateAttributes($model, $attributes = null)
    {
        $labels = [];
        $values = [];
        $attributes = $this->attributes;
        foreach($attributes as $attribute) {
            $labels[] = $model->getAttributeLabel($attribute);
            if(!empty($model->$attribute)) {
                $values[] = $model->$attribute;
            }
        }

        if (empty($values)) {
            $labels = implode(Yii::t($this->tc, '» or «'), $labels);
            foreach($attributes as $attribute) {
                $this->addError($model, $attribute, Yii::t($this->tc, 'Fill «{labels}».', ['labels' => $labels]));
            }
            return false;
        }
        return true;
    }
}
