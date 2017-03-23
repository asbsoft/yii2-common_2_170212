<?php

namespace asb\yii2\common_2_170212\web;

use Yii;
use yii\web\ErrorHandler;
use yii\helpers\FileHelper;

/**
 * Extension of standard error handler.
 * Add features:
 * - change standard layout at backend in basic/united Yii2-template by changind $this->errorAction
 * - for non-existent images files at web root: copy (with preprocessing) from hidden uploads area
 *
 * @author ASB <ab2014box@gmail.com>
 */
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
    {//echo __METHOD__;var_dump($exception);exit;

        // non-existent files processing
        if (isset($exception->statusCode) && $exception->statusCode == 404) {
            $fileUrl = Yii::$app->request->pathInfo;
            $homeUrl = trim(Yii::$app->homeUrl, '/');
            if (!empty($homeUrl)) $fileUrl = $homeUrl . '/' . $fileUrl;//var_dump($fileUrl);exit;

            $webFile = new WebFile($fileUrl, [
                'uploadsDirectCopy' => empty(Yii::$app->params['uploadsDirectCopy']) ? false : Yii::$app->params['uploadsDirectCopy'],
                'badImage' => dirname(__DIR__) . '/assets/common/img/bad-image.jpg',
            ]);
            $result = $webFile->synchronize();
            if ($result === true) { // file copied - send it
                //echo __METHOD__.": file copied OK: /$fileUrl";exit;
                //return Yii::$app->response->redirect('/' . $fileUrl); // one more chance - empty screan ??
                //Yii::$app->response->sendFile($webFile->srcFilePath, null, [`inline` => true]); //?? download dialog will pop up

                $response = Yii::$app->response;
                $response->stream = null;
                $response->content = null;
                $response->data = $webFile->fileBody;
                $mimeType = FileHelper::getMimeTypeByExtension($webFile->srcFilePath);
                $mimeType = isset($mimeType) ? $mimeType : 'application/octet-stream';
                $response->headers->set('Content-Type', $mimeType);
                $response->format = $response::FORMAT_RAW;
                $response->send();
                return;
            } else {
                $msg = $webFile->errmsg;
                Yii::error($msg);//echo __METHOD__;var_dump($msg);exit;
            }
        }
        
        // change errorAction for backend if need (in basic/united Yii2-template)
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
