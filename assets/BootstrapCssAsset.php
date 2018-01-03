<?php

namespace asb\yii2\common_2_170212\assets;

use yii\web\AssetBundle;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class BootstrapCssAsset extends AssetBundle
{
    public $sourcePath = '@bower/bootstrap/dist';
    public $css = [
        'css/bootstrap.css',
    ];
}
