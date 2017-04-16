<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\base\UniApplication;
use asb\yii2\common_2_170212\controllers\BaseAdminController;
use asb\yii2\common_2_170212\i18n\LangHelper;
use asb\yii2\common_2_170212\web\UrlManagerBase;

use Yii;
use yii\base\BootstrapInterface;
use yii\helpers\FileHelper;

/**
 * Common system bootstrap.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class CommonBootstrap implements BootstrapInterface
{
    /** Defaul web files path from web root, can redefine in $app->params */
    public $webfilesSubdir = 'files';
    
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        //Yii::setAlias('@uploads', '@webroot/uploads'); //!! deprecated: files should not be upload direct in web root

        // mirror of uploads files dir in web root
        if (!empty($app->params['webfilesSubdir'])) $this->webfilesSubdir = $app->params['webfilesSubdir'];
        Yii::setAlias('@webfilespath', rtrim(Yii::getAlias('@webroot/' . $this->webfilesSubdir), '/'));
        Yii::setAlias('@webfilesurl',  rtrim(Yii::getAlias('@web/' . $this->webfilesSubdir), '/'));//var_dump(Yii::$aliases);//exit;

        // uploads path is common for all Yii2-templates and not placed in web root
        if (empty($app->params['@uploadspath'])) {
            Yii::setAlias('@uploadspath',  dirname($app->vendorPath) . '/uploads'); // default
        } else {
            Yii::setAlias('@uploadspath',  $app->params['@uploadspath']); // default
        }//var_dump(Yii::$aliases);exit;

        // create common uploads dir and it's mirror in web root
        $dir = Yii::getAlias('@uploadspath');
        if (!is_dir($dir)) @FileHelper::createDirectory($dir);
        $dir = Yii::getAlias('@webfilespath');
        if (!is_dir($dir)) @FileHelper::createDirectory($dir);

        //LangHelper::appendDefLangToHomeUrl($app);

        $app->language = LangHelper::defaultLanguage($app);//echo __METHOD__;var_dump($app->language);

        UrlManagerBase::processLanguage($app->getRequest());

        LangHelper::saveCurrentLanguageInCookies($app);

        // correct BaseAdminController::$adminPath
        //echo"adminPath:'{$app->params['adminPath']}',type:'{$app->type}',request->baseUrl:'{$app->request->baseUrl}'<br>";
        if ($app instanceof UniApplication
            && isset($app->params['adminPath']) && $app->params['adminPath'] !== false // admin URL prefix, may be empty string ''
            && (strlen(trim($app->request->baseUrl, '/')) != 0 // webroot shift by .htaccess
                || $app->type == UniApplication::APP_TYPE_UNITED
                || $app->type == UniApplication::APP_TYPE_BACKEND
               )) {//echo"correct BaseAdminController::adminPath='".BaseAdminController::$adminPath."' to '{$app->params['adminPath']}'<br>";
            BaseAdminController::$adminPath = $app->params['adminPath'];
        }
    }

}
