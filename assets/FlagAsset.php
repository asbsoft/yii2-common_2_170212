<?php

namespace asb\yii2\common_2_170212\assets;

use Yii;
use yii\web\AssetBundle;

class FlagAsset extends AssetBundle
{
    public $sourcePathPlaces = [ // possible assets places
        '@vendor/npm-asset/world-flags-sprite', // most probable - first
        '@vendor/npm/world-flags-sprite',
        '@vendor/bower-asset/world-flags-sprite',
        '@vendor/bower/world-flags-sprite',
        '@vendor/lafeber/world-flags-sprite',
    ];

    public function init()
    {
        parent::init();

        foreach ($this->sourcePathPlaces as $path) {
            $path = Yii::getAlias($path);
            if (is_dir($path)) {
                $this->sourcePath = $path;
                break;
            }
        }
    }

    public $css = [
        'stylesheets/flags16.css',
        'stylesheets/flags32.css',
    ];
}
