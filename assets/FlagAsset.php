<?php

namespace asb\yii2\common_2_170212\assets;

use yii\web\AssetBundle;

class FlagAsset extends AssetBundle
{
    public $sourcePath = '@vendor/lafeber/world-flags-sprite';
    public $css = [
        'stylesheets/flags16.css',
        'stylesheets/flags32.css',
    ];
}
