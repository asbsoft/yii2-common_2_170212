<?php

namespace asb\yii2\common_2_170212\widgets\dropdownmultilevel;

use yii\bootstrap\Nav;

class Menu extends Nav
{
    /**
     * @inheritdoc
     */
    public function run()
    {
        MenuAsset::register($this->getView());
        return parent::run();
    }
}
