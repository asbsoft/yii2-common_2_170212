<?php

namespace asb\yii2\common_2_170212\i18n;

use asb\yii2\common_2_170212\models\Lang;

use Yii;
use yii\web\Application;
use yii\helpers\ArrayHelper;
use yii\web\Cookie;
use yii\web\Response;

define('LANGS_CONFIG_FNAME', dirname(__DIR__) . '/config/langs-default.php');

/**
 * Lang helper.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class LangHelper extends BaseLangHelper
{
    /**
     * Some parameters with default values.
     * You can overwrite their values by define array with such structure
     * in Yii2-application config['params']['asb\yii2\common_2_170212\i18n\LangHelper'].
     */
    public static $defaultParams = [
        /**
         * Application can get languages from this module.
         * This module must be first level module!!
         * for example 'langModuleUniqueId' => 'sys/lang' made init translation problems,
         * because this involve to bootstrap first module 'sys' with all it's submodules to get lang module.
         * But lang module can call in base\CommonBootstrap to setup init language
         * before any other modules bootstrap.
         */
        'langModuleUniqueId' => 'lang',

        /** Application can get languages from this file */
        'langsConfigFname' => LANGS_CONFIG_FNAME,
      //'langsConfigFname' => '@asb/yii2/common_2_170212/config/langs-default.php', // OK but alias can change
      //'langsConfigFname' => dirname(__DIR__) . '/config/langs-default.php', // syntax error in PHP 5.4

        // Language session and cookie parameters
        'appTypePrefix'          => 'basic',
        'sessionDefaultLanguage' => 'session-default-language',
        'cookieDefaultLanguage'  => 'default-language', // if define default language will get from cookie
        'langCookieExpiredSec'   => 2592000, // 1day = 86400sec
      //'langCookieExpiredSec'   => 1, // 1sec - to disable saving
    ];

    /** Result (merged with $Yii::app) parameters */
    protected static $_params;

    /**
     * Get one of parameters merged with Yii::$app->params[self::ClassName()].
     * @param string $alias name of parameter
     * @return string
     */
    protected static function parameter($alias)
    {//var_dump(static::$_params);
        if (empty(static::$_params)) {
            if (isset(Yii::$app->params[self::className()]) && is_array(Yii::$app->params[self::className()]) ) {
                //var_dump(Yii::$app->params[self::className()]);
                static::$_params = ArrayHelper::merge(
                    static::$defaultParams
                  , Yii::$app->params[self::className()]
                );
            }
        }//var_dump(static::$_params);exit;
        return static::$_params[$alias];
    }

    /**
     * Get languages infos from file.
     * @return array
     */
    public static function getDefaultLanguagesConfig()
    {
/*
        if (!empty(Yii::$app->params[static::$paramDefaultLangsConfig])) {
            return include(Yii::getAlias(Yii::$app->params[static::$paramDefaultLangsConfig]));
        } else {
            return include(Yii::getAlias(static::$langsConfigFname));
        }
*/
        return include(Yii::getAlias(static::parameter('langsConfigFname')));
    }
    
    /**
     * Search Language Module from loaded modules.
     * @param string $uniqueId full module's id
     * @return Module|null
     */
    public static function langModule($uniqueId = null)
    {
        if (empty($uniqueId)) $uniqueId = static::parameter('langModuleUniqueId');
/*
        $modules = Yii::$app->loadedModules;//var_dump(array_keys($modules));
        foreach ($modules as $module) {
            if ($module->uniqueId == $uniqueId) return $module;
        }
        return null;
*/
        $module = Yii::$app->getModule($uniqueId);//var_dump($module);
        return $module;
    }    
    
    /** Visible languages cache */
    protected static $languagesCache = [];
    /** Languages cache */
    protected static $languagesAllCache = [];
    /** Clean languages cache */
    public static function cleanLangCache()
    {
        static::$languagesCache = [];
        static::$languagesAllCache = [];
    }

    /**
     * Get active languages (as Lang-objects) array according to sort criteria.
     * @param boolean $all indicate to show non-visible languages (for admin purposes for example)
     * @return array of Lang objects
     */
    public static function activeLanguages($all = false)
    {//echo __METHOD__.'<br>';//var_dump(parent::activeLanguages());var_dump(Lang::activeLanguages());exit;
        if (empty(static::$languagesCache) || empty(static::$languagesAllCache)) {
            static::$languagesCache = [];
            $langModule = static::langModule();//var_dump($langModule);exit;
            //$langModule = null; //!! use for debug

            if (!empty($langModule)) {
                $langModuleHelper = $langModule->getDataModel('LangHelper');//var_dump($langModuleHelper);exit;
                static::$languagesCache = $langModuleHelper::activeLanguages(false);
                static::$languagesAllCache = $langModuleHelper::activeLanguages(true);
            } else {
                $languagesArray = static::getDefaultLanguagesConfig();//var_dump($languagesArray);exit;
                $sortedArray = [];
                foreach ($languagesArray as $lang) {
                    $sortedArray[intval($lang['prio'])] = $lang;
                }
                $keys = array_keys($sortedArray);
                asort($keys);//var_dump($keys);
                foreach ($keys as $key) {
                    $lang = $sortedArray[$key];
                    $lang['class'] = Lang::className();
                    static::$languagesAllCache[$lang['code_full']] = Yii::createObject($lang);
                    if ($lang['is_visible']) {
                        static::$languagesCache[$lang['code_full']] = Yii::createObject($lang);
                    }
                }
            }
        }
        //echo count(static::$languagesAllCache);var_dump(static::$languagesCache);var_dump(static::$languagesAllCache);exit;
        if ($all) return static::$languagesAllCache;
        else return static::$languagesCache;
    }

    /**
     * Get basic active languages (as arrays) array according to sort criteria.
     * @return array of arrays presents Lang-objects
     */
    public static function activeLanguagesArray()
    {//echo __METHOD__;
        $languages = static::activeLanguages();//var_dump($languages);
        $langList = [];
        foreach ($languages as $key => $langobj) {
            $langList[$key] = $langobj->getAttributes() + get_object_vars($langobj);
        }//var_dump($langList);exit;
        return $langList;
    }

    protected static $_defLang;
    public static function setDefaultLanguage($lang)
    {
        static::$_defLang = $lang;
    }
    /**
     * Get default language saved in session or cookie.
     * Run in main bootstrap.
     */
    public static function defaultLanguage($app = null)
    {//echo __METHOD__;var_dump($app->language);var_dump($GLOBALS['_COOKIE']);
        if (empty($app)) {
            $app = Yii::$app;
        }

        // get language from session
        if (empty(static::$_defLang)) {
            $name = static::parameter('appTypePrefix') . '-' . static::parameter('sessionDefaultLanguage');
            if ($name && !empty($app->session[$name])) {//var_dump($_SESSION);
                $lang = Yii::$app->session[$name];
                if (in_array($lang, array_keys(static::activeLanguages()))) {
                    $msg = "get '$lang' from session";//echo"$msg<br>";
                    Yii::trace($msg);
                    static::$_defLang = $lang;
                }
            }
        }

        // get language from cookie
        if (empty(static::$_defLang)) {
            $name = static::parameter('appTypePrefix') . '-' . static::parameter('cookieDefaultLanguage');
            //var_dump($name);var_dump($app->request->cookies);var_dump($GLOBALS['_COOKIE']);
            $lang = Yii::$app->getRequest()->getCookies()->getValue($name);
            if ($name && $lang) {
                if (in_array($lang, array_keys(static::activeLanguages()))) {
                    $msg = "get '$lang' from cookie";//echo"$msg<br>";
                    Yii::trace($msg);
                    static::$_defLang = $lang;
                }
            }
        }

        if (empty(static::$_defLang)) {
            static::$_defLang = static::getFirstActiveLanguageCode();
            $msg = 'get first active: ' . static::$_defLang;//echo"$msg<br>";
            Yii::trace($msg);
        }

        //$app->language = static::$_defLang; //!! not here
        //$msg = "set system Yii::app->language from '{$app->language}' to '" . static::$_defLang . "'";//echo"$msg<br>";
        //Yii::trace($msg);

        return static::$_defLang;
    }

    /**
     * Save default language in session and cookie.
     * @var string $lang language code
     */
    public static function saveLanguageInSession($lang)
    {//echo __METHOD__."($lang)";
        $lang = static::normalizeLangCode($lang);
        $name = static::parameter('appTypePrefix') . '-' . static::parameter('sessionDefaultLanguage');
        if ($name && in_array($lang, array_keys(static::activeLanguages()))) {//var_dump($name);
            Yii::$app->session[$name] = $lang;
        }//var_dump($_SESSION);
    }

    /**
     * Save current language in cookies.
     * Will set before send responce.
     * Run in main bootstrap.
     */
    public static function saveCurrentLanguageInCookies($app = null)
    {//echo __METHOD__;
        if (empty($app)) $app = Yii::$app;

        $app->getResponse()->on(Response::EVENT_BEFORE_SEND, function($event) use($app) {
            //var_dump($event->sender);
            //$response0 = $app->getResponse();//var_dump($response0->cookies);
            $response = $event->sender;//var_dump($response->cookies);

            $name = static::parameter('appTypePrefix') . '-' . static::parameter('cookieDefaultLanguage');
            $lang = static::normalizeLangCode($app->language);//echo __METHOD__;var_dump($name);var_dump($lang);

            //$domain = $app->request->hostName; //?? not need
            $domain = '';
            
            $exists = (boolean)$response->cookies->getValue($name);//echo"?exists:";var_dump($exists);
            
            if ($name && !$exists && in_array($lang, array_keys(static::activeLanguages()))) {
                $cookie = new Cookie([
                    'name' => $name,
                    'value' => $lang,
                    'domain' => $domain,
                    'expire' => time() + static::parameter('langCookieExpiredSec'),
                ]);//var_dump($cookie);
                //$response0->getCookies()->add($cookie);
                $response->getCookies()->add($cookie);
                $msg = "add new cookie '$name' with value='$lang'";//echo"$msg<br>";
                Yii::trace($msg);
            }//var_dump($event->sender->cookies);var_dump($app->response->cookies);var_dump(Yii::$app->response->cookies);
        });
    }

    /**
     * Prepare for MityMCE parameter 'spellchecker_languages' in format: "+Русский=ru"
     * @param string $langCode
     */
    public static function getSpellcheckerLanguage($langCode = '')
    {
        if ('' == $langCode) $langCode = Yii::$app->language;
        $langCode2 = substr($langCode, 0, 2);

        $lang = self::findLanguageByCode2($langCode2);//var_dump($lang);exit;

        if (isset($lang['name_orig'])) {
            return "+{$lang['name_orig']}={$langCode2}";
        } else {
            return '';
        }
    }

    /**
     * Append default language to home URL.
     * Run in main bootstrap.
     */
    public static function appendDefLangToHomeUrl($app)
    {
        if (empty($app)) $app = Yii::$app;
        $app->on(Application::EVENT_BEFORE_REQUEST, function($event) use($app) {
            // \yii\web\Application->_homeUrl end with '/'
            $url = $app->getHomeUrl();//echo'before:';var_dump($url);
            $lang = substr($app->language, 0, 2);//echo (strrpos($url, $lang)) . '<=>' . (count($url) - count($lang) + 1);
            $needAdd = strrpos($url, $lang) !== (count($url) - count($lang) + 1);
            if ($needAdd) $url .= $lang . '/';
            $app->setHomeUrl($url);//echo'after:';var_dump($url);
        });
    }

}
