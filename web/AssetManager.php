<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\helpers\FileHelper;

use yii\web\AssetManager as BaseAssetManager;
use Yii;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class AssetManager extends BaseAssetManager
{
    /**
     * @inheritdoc
     * Correct realpath() problem in Cygwin: it return false for exists path
     * and assets will copy to Cygwin root instead of @webroot.
     */
    public function init()
    {
        $this->basePath = Yii::getAlias($this->basePath);
        if (FileHelper::inCygwin()) {
            $this->basePath = FileHelper::cygpath($this->basePath);
        }
        parent::init();
    }

}
