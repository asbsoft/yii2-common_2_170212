<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
use yii\web\UrlManager as YiiWebUrlManager;
            
/**
 * @author ASB <ab2014box@gmail.com>
 */
class UrlManagerMultilang extends YiiWebUrlManager
{
    public function init()
    {//echo __METHOD__;var_dump($this->rules);exit;
        parent::init();

        $this->enablePrettyUrl = true;
        $this->showScriptName = false;
    }

    /** 
     * @inheritdoc
     * Additional add language info in returned relative URL
     */
    public function createUrl($params)
    {//echo __METHOD__;var_dump($params);
        $url0 = parent::createUrl($params);//var_dump($url0);

        if (LangHelper::countActiveLanguages() > 1)
        {
            $lang_code2 = LangHelper::getLangCode2(
                isset($params['lang'])
                    ? $params['lang']
                  //: Yii::$app->language
                    : LangHelper::defaultLanguage()
            );//var_dump($lang_code2);

            $baseUrl = $this->showScriptName || !$this->enablePrettyUrl ? $this->getScriptUrl() : $this->getBaseUrl();

            if ($baseUrl !== '' && strpos($url0, $baseUrl) === 0) $url0 = substr($url0, strlen($baseUrl));

            $url = "{$baseUrl}/{$lang_code2}{$url0}";
        } else {
            $url = $url0;
        }//var_dump($url);
        
        return $url;
    }

    /** 
     * @inheritdoc
     * Process language prefix in request.
     */
    public function parseRequest($request)
    {//var_dump($request);
        if (LangHelper::countActiveLanguages() > 1)
        {
            //$lang = LangHelper::getFirstActiveLanguageCode(); //!! no
            $lang = Yii::$app->language; // current language

            // Get language from _GET parameter lang=XX if exists.
            // This language has low priority than as URL part: BASE_URL/XX/...
            $get = Yii::$app->request->get();
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
            $baseUrl = $this->showScriptName || !$this->enablePrettyUrl ? $this->getScriptUrl() : $this->getBaseUrl();//echo "baseUrl=$baseUrl<br>";
            $url0 = $request->getUrl(); // $request->url begin with '/'
            //echo "orig url:'$url0'<br>";
            if ($baseUrl !== '' && strpos($url0, $baseUrl) === 0) {
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

        $route = parent::parseRequest($request);//var_dump($route);exit;
        return $route;
    }

}
