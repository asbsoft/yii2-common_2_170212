<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
            
/**
 * @author ASB <ab2014box@gmail.com>
 */
class UrlManagerMultilang extends UrlManagerBase
{
    public function init()
    {//echo __METHOD__;var_dump($this->rules);exit;
        parent::init();

        $this->enablePrettyUrl = true;
        //$this->enableStrictParsing = true;
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
     */
    public function parseRequest($request)
    {//echo __METHOD__;var_dump($request->url);
        //static::processLanguage($request); // already call in UrlManagerBase

        $route = parent::parseRequest($request);//var_dump($route);exit;
        return $route;
    }

}
