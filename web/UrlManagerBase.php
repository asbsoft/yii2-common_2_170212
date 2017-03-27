<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
use yii\web\UrlManager as YiiUrlManager;

class UrlManagerBase extends YiiUrlManager
{
    /**
     * Process language in request.
     * At first find it in _GET parameter lang=XX if exists.
     * Second find language in URL-prefix /LL/path/info/.
     * If found - save language in session/cookie and cut language from URL and pathInfo.
     * @param yii\web\Request $request
     */
    public static function processLanguage($request)
    {//echo __METHOD__;

        if (LangHelper::countActiveLanguages() > 1)
        {
            //$lang = LangHelper::getFirstActiveLanguageCode(); //!! no
            $lang = Yii::$app->language; // current language

            // Get language from _GET parameter lang=XX if exists.
            // This language has low priority than as URL part: BASE_URL/XX/...
            $get = $request->get();
            if (!empty($get['lang'])) {
                $getLang = $get['lang'];
                if (LangHelper::isValidLangCode($getLang)) {
                    $lang = $getLang;
                }
            }           

            // Get language from URL if exists
            // and correct $request->pathInfo by cutting language part
            $pathInfo0 = $request->getPathInfo(); // pathInfo has not leading '/'
            //echo "orig pathInfo:'$pathInfo'<br>";
            $parts = explode('/', $pathInfo0, 2);//var_dump($parts);
            if (!empty($parts[0])) {
                $langPart = $parts[0];//var_dump($langPart);
                if (LangHelper::isValidLangCode($langPart)) {
                    $pathInfo = substr($pathInfo0, strlen($langPart) + 1);
                    $lang = $langPart;
                    $request->setPathInfo($pathInfo);

                    $msg = "corrected pathInfo from '{$pathInfo0}' to '{$pathInfo}'<br>";//echo"$msg<br>";
                    Yii::trace($msg);
                }
            }

            // Save language
            $lang = LangHelper::normalizeLangCode($lang);//echo "new lang:'$lang'<br>";
            Yii::$app->language = $lang;
            LangHelper::setDefaultLanguage($lang);
            LangHelper::saveLanguageInSession($lang);

            // Correct $request->url by cutting language part if exists
            $urlManager = Yii::$app->urlManager;
            $baseUrl = $urlManager->showScriptName || !$urlManager->enablePrettyUrl ? $urlManager->getScriptUrl() : $urlManager->getBaseUrl();//echo "baseUrl=$baseUrl<br>";
            $url0 = $request->getUrl();//echo "orig url:'$url0'<br>";
            if ($baseUrl !== '' && strpos($url0, $baseUrl) === 0) { // $url0 = $request->url begin with '/'
                $link = substr($url0, strlen($baseUrl));
                if (isset($langPart) && LangHelper::isValidLangCode($langPart)) {
                    $langPart = '/' . $langPart;
                    if (strpos($link, $langPart) === 0) {
                        $link = substr($link, strlen($langPart));
                    }
                    $url = $baseUrl . $link;
                    $request->setUrl($url);
                    $msg = "correct request->url from '{$url0}' to '{$url}'";//echo"$msg<br>";
                    Yii::trace($msg);
                }
            }
        }    

    }

}
