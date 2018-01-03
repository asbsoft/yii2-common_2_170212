<?php

namespace asb\yii2\common_2_170212\assets;

use yii\web\AssetBundle;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class CommonAsset extends AssetBundle
{
    public $css = ['common.css'];

    public function init()
    {
        parent::init();
        $this->sourcePath = __DIR__ . '/common';
    }

    public $depends = [
        'asb\yii2\common_2_170212\assets\BootstrapCssAsset', // need to move up 'bootstrap.css' in <head>s of render HTML-results
    ];
}
