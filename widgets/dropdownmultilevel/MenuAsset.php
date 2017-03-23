<?php

namespace asb\yii2\common_2_170212\widgets\dropdownmultilevel;

use yii\web\AssetBundle;
use yii\web\View;

class MenuAsset extends AssetBundle
{
    public $css = [
        'dropdown-submenu.css',
    ];

    //public $js = [];
    //public $jsOptions = ['position' => View::POS_BEGIN];

    public $depends = [
        'asb\yii2\common_2_170212\assets\BootstrapCssAsset', // add only CSS - need to move up 'bootstrap.css' in <head>s of render HTML-results
    ];

    public function init() {
        parent::init();
        $this->sourcePath = __DIR__ . '/assets';
    }
}
