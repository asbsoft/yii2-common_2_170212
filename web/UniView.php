<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\controllers\BaseController;
use asb\yii2\common_2_170212\base\UniModule;

use yii\web\View;

/**
 * Common basic view.
 * Help to find view-templates from parent module(s).
 *
 * ?? Problem: assets assigned in UniView will lost in final result
 *    generated by Yii::$app->view if Yii::$app->view is yii\web\View not UniView
 *
 * @author ASB <ab2014box@gmail.com>
 */
class UniView extends View
{
    /**
     * @inheritdoc
     * Additional find views in parents views subdirs
     */
    protected function findViewFile($view, $context = null)
    {//echo __METHOD__."($view)";if(!empty($context))var_dump($context::className());else var_dump($context);
        if (empty($context->module) || ! $context->module instanceof UniModule) {
            return parent::findViewFile($view, $context);
        }
        if ($context instanceof BaseController) {
            $module = $context->module;
            $pathList = $module->getBasePathList();//var_dump($pathList);
            foreach ($pathList as $path) {
                //$viewPath = $path . DIRECTORY_SEPARATOR . $context->module->viewsSubdir . DIRECTORY_SEPARATOR . $context->id;//error
                $viewPath = $path . DIRECTORY_SEPARATOR . $module::$viewsSubdir . DIRECTORY_SEPARATOR . $context->id;
                $file = $viewPath . DIRECTORY_SEPARATOR . ltrim($view, '/');//var_dump($file);
                $path = $file . '.' . $this->defaultExtension;
                if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
                    $result = $file;
                } else if ($this->defaultExtension !== 'php' && !is_file($path)) $path = $file . '.php'; {
                    $result = $path;
                }
                if (!empty($result) && is_file($result)) {//echo'resultFile:';var_dump($result);exit;
                    return $result;
                }
            }
        }
        return parent::findViewFile($view, $context);
    }

}
