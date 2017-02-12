<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\base\UniApplication;
use asb\yii2\common_2_170212\controllers\BaseAdminController;
use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
use yii\base\BootstrapInterface;

/**
 * Common system bootstrap.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class CommonBootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {//echo __METHOD__;

        Yii::setAlias('@uploads', '@webroot/uploads');

        //LangHelper::appendDefLangToHomeUrl($app);

        $app->language = LangHelper::defaultLanguage($app);//var_dump($app->language);

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
