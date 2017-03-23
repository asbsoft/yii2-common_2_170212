<?php

namespace asb\yii2\common_2_170212\models;

use yii\base\Model as BaseModel;

/**
 * Class represents language.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class Lang extends BaseModel
{
    public $id;
    public $prio;
    public $is_visible;
    public $code_full;
    public $code2;
    public $code3;
    public $name_en;
    public $name_orig;
    public $country_code;
}
