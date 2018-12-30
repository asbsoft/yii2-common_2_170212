<?php

namespace asb\yii2\common_2_170212\models;

use yii\db\ActiveQuery as BaseActiveQuery;

/**
 * ActiveQuery represents a DB query associated with an Active Record class.
 * @see yii\db\ActiveQuery
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ActiveQuery extends BaseActiveQuery
{
    use QueryTrait;
}
