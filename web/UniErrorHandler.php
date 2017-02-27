<?php

namespace asb\yii2\common_2_170212\web;

use Yii;
use yii\web\ErrorHandler;

class UniErrorHandler extends ErrorHandler
{
    /**
     * @var string the route to the action that will be used to display error at backend.
     * Need for united application (Yii2 basic template) for separate display error at frontend and backend layouts, etc.
     */
    public $errorActionBackend;

    /**
     * @inheritdoc
     */
    protected function renderException($exception)
    {//echo __METHOD__;//var_dump($exception);
        $adminPath = empty(Yii::$app->params['adminPath']) ? '' : Yii::$app->params['adminPath'];//var_dump($adminPath);

        if ($adminPath && !empty($this->errorActionBackend)) {
            $pathInfo = Yii::$app->request->pathInfo;//var_dump($pathInfo);
            if (strpos($pathInfo, $adminPath) === 0) {
                $this->errorAction = $this->errorActionBackend;
            }
        }
        return parent::renderException($exception);
    }
}
