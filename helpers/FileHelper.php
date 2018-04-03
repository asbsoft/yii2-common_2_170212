<?php
namespace asb\yii2\common_2_170212\helpers;

use yii\helpers\FileHelper as BaseFileHelper;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class FileHelper extends BaseFileHelper
{
    /**
     * Check if current environment is Cygwin.
     * @return boolean
     */
    public static function inCygwin()
    {
        if (preg_match('/CYGWIN/i', PHP_OS)) {
            return true;
        }
        return false;
    }

    /**
     * Convert path into Cygwin-like style.
     * @param string $path
     * @return string
     */
    public static function cygpath($path)
    {
        $path = parent::normalizePath($path, '/');
        $path = preg_replace('|^([a-z]):/|i', '/cygdrive/$1/', $path, 1);
        return $path;
    }

}
