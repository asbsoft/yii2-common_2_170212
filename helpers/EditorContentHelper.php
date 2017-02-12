<?php

namespace asb\yii2\common_2_170212\helpers;

use Yii;
use yii\base\Object;

/**
 * Content preprocessing for visual editor.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class EditorContentHelper extends Object
{
    /** Prepare text to save after visual editor */
    public static function beforeSaveBody($text)
    {
        $baseUrl = Yii::$app->urlManager->getBaseUrl();//var_dump($baseUrl);
        $tr_table = [
            "src=\"{$baseUrl}/uploads" => "src=\"@uploads",

//...todo add useful here...

        ];
        $text = strtr($text, $tr_table);//echo __METHOD__;var_dump($text);
        return $text;
    }

    /** Prepare text to show after get it from database */
    public static function afterSelectBody($text)
    {
        $baseUrl = Yii::$app->urlManager->getBaseUrl();//var_dump($baseUrl);
        $tr_table = [
            "src=\"@uploads" => "src=\"{$baseUrl}/uploads",

//...todo add useful here...

        ];
        $text = strtr($text, $tr_table);//echo __METHOD__;var_dump($text);
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
