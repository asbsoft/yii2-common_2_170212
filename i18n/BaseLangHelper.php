<?php

namespace asb\yii2\common_2_170212\i18n;

use yii\base\Object;

/**
 * Base lang helper.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class BaseLangHelper extends Object
{
    /**
     * Get basic active languages (as arrays) array according to sort criteria.
     * @return array of Lang objects
     */
    public static function activeLanguagesArray()
    {
        $langList = include dirname(__DIR__) . '/config/languages.php';
        return $langList;
    }
    
    /**
     * Get numder of active languages
     * @return integer
     */
    public static function countActiveLanguages()
    {
        $langList = static::activeLanguagesArray();
        return count($langList);
    }
    
    /**
     * Get first active language code
     * @return string 5-symbols language code
     */
    public static function getFirstActiveLanguageCode($default = 'en-US')
    {
        $langList = static::activeLanguagesArray();
        
        $first = array_shift($langList);
        return isset($first['code_full']) ? $first['code_full'] : $default;
    }
    
    /**
     * Check 2/3/5-symbols language code in set of active languages
     * @param string $langCode
     */
    public static function isValidLangCode($langCode)
    {
        $langList = static::activeLanguagesArray();
        foreach ($langList as $lang) {
            if ($lang['code2'] == $langCode) return true;
            if ($lang['code3'] == $langCode) return true;
            if ($lang['code_full'] == $langCode) return true;
        }
        return false;
    }
    
    /**
     * Convert 2/3-symbols language code or name into 5-symbols form
     * @param string $langCode 2/3-symbols language code
     * @return string 5-symbols language code or original $langCode if not found
     */
    public static function normalizeLangCode($langCode)
    {
        $langList = static::activeLanguagesArray();//var_dump($langList);exit;
        foreach ($langList as $lang) {
            if ($lang['code2'] == $langCode) return $lang['code_full'];
            if ($lang['code3'] == $langCode) return $lang['code_full'];
            if ($lang['code_full'] == $langCode) return $lang['code_full'];
            if (strtolower($lang['name_en']) == strtolower($langCode)) return $lang['code_full'];
            if (strtolower($lang['name_orig']) == strtolower($langCode)) return $lang['code_full'];
        }
        return $langCode; //?? or return false
    }

    /**
     * Convert 3/5-symbols language code or name into 2-symbols form
     * @param string $langCode 3/5-symbols language code
     * @return string 2-symbols language code or first 2 symbols of original $langCode if not found
     */
    public static function getLangCode2($langCode)
    {
        $langList = static::activeLanguagesArray();//var_dump($langList);exit;
        foreach ($langList as $lang) {
            if ($lang['code3'] == $langCode) return $lang['code2'];
            if ($lang['code_full'] == $langCode) return $lang['code2'];
            if (strtolower($lang['name_en']) == strtolower($langCode)) return $lang['code2'];
            if (strtolower($lang['name_orig']) == strtolower($langCode)) return $lang['code2'];
        }
        return substr($langCode, 0, 2); //?? or return false
    }

    /**
     * Find language by 2-symbols language code
     * @param string 2-symbols language code
     * @return Lang object
     */
    public static function findLanguageByCode2($langCode2)
    {
        $langList = static::activeLanguagesArray();
        foreach ($langList as $lang) {
            if ($lang['code2'] == $langCode2) return $lang;
        }
    }

}
