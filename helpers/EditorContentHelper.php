<?php

namespace asb\yii2\common_2_170212\helpers;

use Yii;
use yii\base\Object;

/**
 * Content preprocessing for visual editor:
 * - correct real images links to aliases and back (need when system move, f.e. to subdir)
 * - ...
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
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

        $baseUrl = Yii::$app->urlManager->getBaseUrl();
        $trTable = [
            "src=\"{$baseUrl}/{$webfilesSubdirOld}" => "src=\"@{$webfilesSubdir}", //!! old -> new
            "src=\"{$baseUrl}/{$webfilesSubdir}" => "src=\"@{$webfilesSubdir}",

//...todo add useful here...

        ];
        $text = strtr($text, $trTable);
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

        $baseUrl = Yii::$app->urlManager->getBaseUrl();
        $trTable = [
            "src=\"@{$webfilesSubdirOld}" => "src=\"{$baseUrl}/{$webfilesSubdir}", //!! old -> new
            "src=\"@{$webfilesSubdir}" => "src=\"{$baseUrl}/{$webfilesSubdir}",

//...todo add useful here...

        ];
        $text = strtr($text, $trTable);
        return $text;
    }

    /**
     * Convert upload path to web URL.
     * Work only if alias @uploads is subdir of alias @webroot.
     * @param string $path
     * @param string $uploadsAlias alias for uploads path in filesystem (non-standard for Yii2)
     * @param string $webfilesurlAlias alias/subdir - path from webroot to uploads directory
     * @return string
     */
    public static function uploadUrl($path = '', $uploadsAlias = '@uploads', $webfilesurlAlias = 'uploads')
    {
        $path = str_replace('\\', '/', $path);
        $webroot = str_replace('\\', '/', Yii::getAlias('@webroot'));
        $uploads = str_replace('\\', '/', Yii::getAlias($uploadsAlias));

        //$subdir = str_replace($webroot, '', $uploads); // work correct only if uploads path is insite web root
        $subdir = str_replace($uploads, '', $path);

        $web = Yii::getAlias('@web'); 
        $files = Yii::getAlias($webfilesurlAlias);
        $result = $web . (empty($web) ? '' : '/' ) . $files . $subdir;

        return $result;
    }

}
