<?php

namespace asb\yii2\common_2_170212\models;

use yii\db\Query as BaseQuery;

/**
 * Query represents a SELECT SQL statement.
 * @see yii\db\Query
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class Query extends BaseQuery
{
    use QueryTrait;
}
