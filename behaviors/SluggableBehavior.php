<?php

namespace asb\yii2\common_2_170212\behaviors;

/**
 * SluggableBehavior automatically fills the specified attribute with a value that can be used a slug in a URL.
 *
 * When attribute 'ensureUnique' is true, parent SluggableBehavior will generate for empty attribute slugs such as '-2'.
 * To avoid this add attribute 'allowEmptySlug'.
 *
 * Usage:
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => SluggableBehavior::className(),
 *             'attribute' => '...',
 *             'ensureUnique' => true,
 *             'allowEmptySlug' => true,
 *         ],
 *     ];
 * }
 * ```
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class SluggableBehavior extends \yii\behaviors\SluggableBehavior
{
    /** Set true to allow empty slug when ensureUnique property is true */
    public $allowEmptySlug = false;

    /**
     * @inheritdoc
     */
    protected function makeUnique($slug)
    {
        if ($this->allowEmptySlug && empty($slug)) {
            return '';
        } else {
            return parent::makeUnique($slug);
        }
    }

}
