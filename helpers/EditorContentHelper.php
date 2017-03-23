<?php

namespace asb\yii2\common_2_170212\helpers;

use Yii;
use yii\base\Object;

/**
 * Content preprocessing for visual editor:
 * - correct real images links to aliases and back (need when system move, f.e. to subdir)
 * - ...
 *
 * @author ASB <ab2014box@gmail.com>
 */
class EditorContentHelper extends Object
{
    /** Subdirectory in web root of uploaded files or theirs mirrors */
    public static $webfilesSubdir = 'files';

    public static $webfilesSubdirOld = 'uploads'; // deprecated but already use in old versions
    
    /** Correct parameter(s) */
    public static function correctParams()
    {
        if (!empty(Yii::$app->params['webfilesSubdir'])) {
            static::$webfilesSubdir = Yii::$app->params['webfilesSubdir'];
        }
    }
    
    /**
     * Prepare text to save after visual editor.
     * @param string $text
     * @return string 
     */
    public static function beforeSaveBody($text)
    {
        static::correctParams();
        $webfilesSubdir = static::$webfilesSubdir;
        $webfilesSubdirOld = static::$webfilesSubdirOld;

        $baseUrl = Yii::$app->urlManager->getBaseUrl();//var_dump($baseUrl);
        $trTable = [
            "src=\"{$baseUrl}/{$webfilesSubdirOld}" => "src=\"@{$webfilesSubdir}", //!! old -> new
            "src=\"{$baseUrl}/{$webfilesSubdir}" => "src=\"@{$webfilesSubdir}",

//...todo add useful here...

        ];//var_dump($trTable);exit;
        $text = strtr($text, $trTable);//echo __METHOD__;var_dump($text);
        return $text;
    }

    /**
     * Prepare text to show in editor after get it from database.
     * @param string $text
     * @return string 
     */
    public static function afterSelectBody($text)
    {
        static::correctParams();
        $webfilesSubdir = static::$webfilesSubdir;
        $webfilesSubdirOld = static::$webfilesSubdirOld;

        $baseUrl = Yii::$app->urlManager->getBaseUrl();//var_dump($baseUrl);
        $trTable = [
            "src=\"@{$webfilesSubdirOld}" => "src=\"{$baseUrl}/{$webfilesSubdir}", //!! old -> new
            "src=\"@{$webfilesSubdir}" => "src=\"{$baseUrl}/{$webfilesSubdir}",

//...todo add useful here...

        ];//var_dump($trTable);
        $text = strtr($text, $trTable);//echo __METHOD__;var_dump($text);
        return $text;
    }

    /**
     * Convert upload path to web URL.
     * Work only if alias @uploads is subdir of alias @webroot.
     * @param string $path
     * @return string
     */
    static function uploadUrl($path = '')
    {//echo __METHOD__."('$path')";
        $path = str_replace('\\', '/', $path);
        $webroot = str_replace('\\', '/', Yii::getAlias('@webroot'));
        $uploads = str_replace('\\', '/', Yii::getAlias('@uploads'));
        $subdir = str_replace($webroot, '', $uploads);
        $web = Yii::getAlias('@web');
        $result = $web . $subdir . (empty($path) ? '' : '/' . $path);//echo'result:';var_dump($result);
        return $result;
    }

}
