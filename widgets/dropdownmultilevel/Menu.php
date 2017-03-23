<?php

namespace asb\yii2\common_2_170212\widgets\dropdownmultilevel;

use yii\bootstrap\Nav;

/**
 * Renders a nav HTML component.
 * Support multilevel dropdowns.
 * For Yii2.0.10+ use in menu array 'dropDownOptions' => ['class' => 'dropdown-menu'] near 'items'.
 * @see yii\bootstrap\Nav
 */
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
