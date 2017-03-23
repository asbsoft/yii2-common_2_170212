<?php

namespace asb\yii2\common_2_170212\web;

use yii\imagine\Image;

use Yii;
use yii\base\Object;
use yii\helpers\FileHelper;

/**
 * Class provide synchronization with uploaded file and its mirror in web root.
 * Upload files area placed not in web root.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class BaseWebFile extends Object
{
    /** Path to image to show instead of bad image */
    public $badImage = '@webroot/img/bad-image.jpg'; // default, can redefine when create this Object

    public $allowedExtensions = ['gif', 'png', 'jpeg', 'jpg'];
    
    /** Root for upload files. Not in web root recommended!! */
    public $uploadsRootPath = '@uploadspath';
    /** Mirror of uploaded files in web root */
    public $webfileRootPath = '@webfilespath';
    /** Url to mirror of uploadede files in web root */
    public $webfileRootUrl  = '@webfilesurl';
    
    /** Use true to disable image files processing before copying */
    public $uploadsDirectCopy = false;

    /** Error message */
    public $errmsg = '';
    /** Messages translation category */
    public $tc = 'common';

    /** File path from $this->webfileRootUrl */
    protected $_fileSubpath;

    /** File extension */
    protected $_format;

    protected $_srcFilePath;
    public function getSrcFilePath() {return $this->_srcFilePath;}

    protected $_destFilePath;
    public function getDestFilePath() {return $this->_destFilePath;}

    /** 
     * Web file constructor.
     * @param string $fileUrl request pathInfo
     */
    public function __construct($fileUrl, $config = [])
    {//echo __METHOD__."($fileUrl)<br>";
        parent::__construct($config);

        $this->uploadsRootPath = Yii::getAlias($this->uploadsRootPath);
        $this->webfileRootPath = Yii::getAlias($this->webfileRootPath);
        $this->webfileRootUrl  = Yii::getAlias($this->webfileRootUrl);

        $filesBaseUrl = $this->webfileRootUrl;
        $filesBaseUrl = trim($filesBaseUrl, '/') . '/';
        if (strpos($fileUrl, $filesBaseUrl) === 0) {
            $this->_fileSubpath = substr($fileUrl, strlen($filesBaseUrl));
            $this->_format = pathinfo($this->_fileSubpath, PATHINFO_EXTENSION);
            $this->_srcFilePath  = $this->uploadsRootPath . '/' . $this->_fileSubpath;//echo'src:';var_dump($this->_srcFilePath);
            $this->_destFilePath = $this->webfileRootPath . '/' . $this->_fileSubpath;//echo'dest:';var_dump($destFilePath);
        } else {
            $this->errmsg = Yii::t($this->tc, "File '{file}' is not from upload mirror area", ['file' => $this->_destFilePath]);
            $this->_fileSubpath = false;
        }//echo __METHOD__;var_dump($this->_fileSubpath);
    }

    protected $_fileBody;
    /**
     * Get file body
     * @return string|false and get $this->errmsg
     */
    public function getFileBody()
    {
        if (!isset($this->_fileBody)) {
            if (is_file($this->_srcFilePath)) {
                $this->_fileBody = file_get_contents($this->_srcFilePath);
            } else {
                $this->errmsg = Yii::t($this->tc, "Source file '{file}' not found", ['file' => $this->_srcFilePath]);
                return false;
            }
        }
        return $this->_fileBody;
    }

    /** 
     * Synchronize file from uploads area to web root files area.
     * @return boolean return true if
     */
    public function synchronize()
    {//echo __METHOD__;
        if (empty($this->_fileSubpath)) {
            $this->errmsg = Yii::t($this->tc, "File '{file}' is not from upload mirror area", ['file' => $this->_destFilePath]);
            return false;
        }

        $ext = $this->isAllowedExtension();
        if ($ext !== true) {
            $this->errmsg = Yii::t($this->tc, "File has not allowed type '{ext}'", ['ext' => $ext]);
            return false;
        }

        if (!is_file($this->_srcFilePath)) {
            $this->errmsg = Yii::t($this->tc, "Source file '{file}' not found", ['file' => $this->_srcFilePath]);
            return false;
        }

        $needUpdate = true;
        if (is_file($this->_destFilePath)) {
            // compare existing file size and times with source
            $needUpdate = false;
            if (filesize($this->_srcFilePath) != filesize($this->_destFilePath)) {
                $needUpdate = true;
            }
            $srcTime  = filectime($this->_srcFilePath);
            $descTime = filectime($this->_destFilePath);//var_dump($srcTime, $descTime);
            if ($srcTime > $descTime) $needUpdate = true;
            if ($needUpdate && !@unlink($this->_destFilePath)) {
                $this->errmsg = Yii::t($this->tc, "Can't delete file '{file}'", ['file' => $this->_destFilePath]);
                return false;
            }
        }

        $result = $this->copyFile($this->_srcFilePath, $this->_destFilePath);
        if ($result === false) {
           $this->errmsg = $this->errmsg ?: Yii::t($this->tc, "Can't copy file");
           return false;
        }
        $this->_fileBody = file_get_contents($this->_destFilePath); // new content

        return true;
    }

    /**
     * Check file extension.
     * @return true|string illegal extension or true if allowed
     */
    protected function isAllowedExtension()
    {
        if (in_array(strtolower($this->_format), $this->allowedExtensions)) {
            return true;
        } else {
            return $this->_format;
        }
    }

    /**
     * Copy file.
     * @param string $srcFilePath
     * @param string $destFilePath
     * @return integer|false bytes count or false on error and set $this->errmsg
     */
    protected function copyFile($srcFilePath, $destFilePath)
    {
        $fileBody = file_get_contents($srcFilePath); // original

        if (empty($this->uploadsDirectCopy)) {
            // preparate image to remove injected code, etc
            $imagine = Image::getImagine();
            try {
                $image = $imagine->open($srcFilePath);
                $fileBody = $image->get($this->_format, []); // preprocessed image
            } catch (\Exception $ex) {
                //$this->errmsg = Yii::t($this->tc, "Uploaded file '{file}' not an image", ['file' => $srcFilePath]);
                $this->errmsg = $ex->getMessage();
                Yii::error($this->errmsg);
                //return false;

                // show instead of return error
                //$fileBody = @file_get_contents($this->badImage);
                $image = $imagine->open($this->badImage);
                $fileBody = $image->get($this->_format, []);
            }
        }

        $destDir = dirname($destFilePath);
        if (!is_dir($destDir) && !@FileHelper::createDirectory($destDir)) {
           $this->errmsg = Yii::t($this->tc, "Can't create destination folder '{dir}'", ['dir' => $destDir]);
           return false;
        }

//var_dump($fileBody);exit;
        $result = @file_put_contents($destFilePath, $fileBody, LOCK_EX);
        return $result;
    }

    
}
