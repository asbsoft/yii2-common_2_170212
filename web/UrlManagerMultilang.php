<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
            
/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class UrlManagerMultilang extends UrlManagerBase
{
    public function init()
    {
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
    {
        $url0 = parent::createUrl($params);

        if (LangHelper::countActiveLanguages() > 1)
        {
            $lang_code2 = LangHelper::getLangCode2(
                isset($params['lang'])
                    ? $params['lang']
                  //: Yii::$app->language
                    : LangHelper::defaultLanguage()
            );

            $baseUrl = $this->showScriptName || !$this->enablePrettyUrl ? $this->getScriptUrl() : $this->getBaseUrl();

            if ($baseUrl !== '' && strpos($url0, $baseUrl) === 0) $url0 = substr($url0, strlen($baseUrl));

            $url = "{$baseUrl}/{$lang_code2}{$url0}";
        } else {
            $url = $url0;
        }
        
        return $url;
    }

    /** 
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        //static::processLanguage($request); // already call in UrlManagerBase

        $route = parent::parseRequest($request);
        return $route;
    }

}
