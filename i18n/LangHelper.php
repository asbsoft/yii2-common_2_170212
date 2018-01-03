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
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
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
      //'langCookieExpiredSec'   => 2592000, // 30days, 1day = 86400sec
        'langCookieExpiredSec'   => 1, // 1sec - to disable saving
    ];

    /** Result (merged with $Yii::app) parameters */
    protected static $_params;

    /**
     * Get one of parameters merged with Yii::$app->params[self::ClassName()].
     * @param string $alias name of parameter
     * @return string
     */
    protected static function parameter($alias)
    {
        if (empty(static::$_params)) {
            static::$_params = static::$defaultParams;
            if (!empty(Yii::$app->langManager)) {
                if (isset(Yii::$app->langManager->params) && is_array(Yii::$app->langManager->params) ) {
                    static::$_params = ArrayHelper::merge(static::$defaultParams, Yii::$app->langManager->params);
                }
                if (!empty(Yii::$app->langManager->langsConfigFname)) {
                    static::$_params['langsConfigFname'] = Yii::$app->langManager->langsConfigFname;
                }
            }
        }
        return static::$_params[$alias];
    }

    /**
     * Get languages infos.
     * @return array
     */
    public static function getDefaultLanguagesConfig()
    {
        if (!empty(Yii::$app->langManager) && !empty(Yii::$app->langManager->langsConfig)) {
            return Yii::$app->langManager->langsConfig;
        } else {
            return include(Yii::getAlias(static::parameter('langsConfigFname')));
        }
    }
    
    /**
     * Search Language Module from loaded modules.
     * @param string $uniqueId full module's id
     * @return Module|null
     */
    public static function langModule($uniqueId = null)
    {
        if (empty($uniqueId)) $uniqueId = static::parameter('langModuleUniqueId');
        $module = Yii::$app->getModule($uniqueId);
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
    {
        if (empty(static::$languagesCache) || empty(static::$languagesAllCache)) {
            static::$languagesCache = [];
            $langModule = static::langModule();
            //$langModule = null; //!! use for debug

            if (!empty($langModule)) {
                $langModuleHelper = $langModule->getDataModel('LangHelper');
                static::$languagesCache = $langModuleHelper::activeLanguages(false);
                static::$languagesAllCache = $langModuleHelper::activeLanguages(true);
            } else {
                $languagesArray = static::getDefaultLanguagesConfig();
                $sortedArray = [];
                foreach ($languagesArray as $lang) {
                    $sortedArray[intval($lang['prio'])] = $lang;
                }
                $keys = array_keys($sortedArray);
                asort($keys);
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
        if ($all) {
            return static::$languagesAllCache;
        } else {
            return static::$languagesCache;
        }
    }

    /**
     * Get basic active languages (as arrays) array according to sort criteria.
     * @return array of arrays presents Lang-objects
     */
    public static function activeLanguagesArray()
    {
        $languages = static::activeLanguages();
        $langList = [];
        foreach ($languages as $key => $langobj) {
            $langList[$key] = $langobj->getAttributes() + get_object_vars($langobj);
        }
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
    {
        if (empty($app)) {
            $app = Yii::$app;
        }

        // get language from session
        if (empty(static::$_defLang)) {
            $name = static::parameter('appTypePrefix') . '-' . static::parameter('sessionDefaultLanguage');
            if ($name && !empty($app->session[$name])) {
                $lang = Yii::$app->session[$name];
                if (in_array($lang, array_keys(static::activeLanguages()))) {
                    $msg = "get '$lang' from session";
                    Yii::trace($msg);
                    static::$_defLang = $lang;
                }
            }
        }

        // get language from cookie
        if (empty(static::$_defLang)) {
            $name = static::parameter('appTypePrefix') . '-' . static::parameter('cookieDefaultLanguage');
            $lang = Yii::$app->getRequest()->getCookies()->getValue($name);
            if ($name && $lang) {
                if (in_array($lang, array_keys(static::activeLanguages()))) {
                    $msg = "get '$lang' from cookie";
                    Yii::trace($msg);
                    static::$_defLang = $lang;
                }
            }
        }

        if (empty(static::$_defLang)) {
            static::$_defLang = static::getFirstActiveLanguageCode();
            $msg = 'get first active: ' . static::$_defLang;
            Yii::trace($msg);
        }

        //$msg = "set system Yii::app->language from '{$app->language}' to '" . static::$_defLang . "'";
        //Yii::trace($msg);

        return static::$_defLang;
    }

    /**
     * Save default language in session and cookie.
     * @var string $lang language code
     */
    public static function saveLanguageInSession($lang)
    {
        $lang = static::normalizeLangCode($lang);
        $name = static::parameter('appTypePrefix') . '-' . static::parameter('sessionDefaultLanguage');
        if ($name && in_array($lang, array_keys(static::activeLanguages()))) {
            Yii::$app->session[$name] = $lang;
        }
    }

    /**
     * Save current language in cookies.
     * Will set before send responce.
     * Run in main bootstrap.
     */
    public static function saveCurrentLanguageInCookies($app = null)
    {
        if (empty($app)) $app = Yii::$app;

        $app->getResponse()->on(Response::EVENT_BEFORE_SEND, function($event) use($app) {
            $response = $event->sender;

            $name = static::parameter('appTypePrefix') . '-' . static::parameter('cookieDefaultLanguage');
            $lang = static::normalizeLangCode($app->language);

            $domain = '';
            
            $exists = (boolean)$response->cookies->getValue($name);
            
            if ($name && !$exists && in_array($lang, array_keys(static::activeLanguages()))) {
                $cookie = new Cookie([
                    'name' => $name,
                    'value' => $lang,
                    'domain' => $domain,
                    'expire' => time() + static::parameter('langCookieExpiredSec'),
                ]);
                $response->getCookies()->add($cookie);
                $msg = "add new cookie '$name' with value='$lang'";
                Yii::trace($msg);
            }
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

        $lang = self::findLanguageByCode2($langCode2);

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
            $url = $app->getHomeUrl();
            $lang = substr($app->language, 0, 2);
            $needAdd = strrpos($url, $lang) !== (count($url) - count($lang) + 1);
            if ($needAdd) $url .= $lang . '/';
            $app->setHomeUrl($url);
        });
    }

}
