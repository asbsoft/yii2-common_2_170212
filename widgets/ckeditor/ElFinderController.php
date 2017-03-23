<?php

namespace asb\yii2\common_2_170212\widgets\ckeditor;

//use mihaildev\elfinder\PathController as BaseController; // select for use only one (separate) files root for news
use mihaildev\elfinder\Controller as BaseController; // use for additional common files root

use Yii;

class ElFinderController extends BaseController
{
    /** Subfolder in root of uploads folder */
    public $subfolder = ''; //!! need to redefine in child
    
    /** Default role(s) for all actions. Use instead of behaviors()['access'] */
    public $access = ['roleAdmin']; // default role for backend

    /**
     * Display files filter parameter for elFinder.
     * @example
     *   ['image'] - display all images
     *   ['image/png', 'application/x-shockwave-flash'] - display png and flash
     */
    //public $onlyMimes = ['image']; // default in elFinder
    //public $onlyMimes = ['all']; // show all

    /** Allow to upload files mime types */
    public $uploadAllow = ['image'];

    /** Default translation category */
    public $tc = 'common';

    public $uploadsNewsDir;
    public $uploadsNewsUrl;

    /** Additional options for files roots */
    protected $_addOptions = [];

    /**
     * @inheritdoc
     * In child of this controller recommended to redefine $this->roots['path']
     * to work with own subdirectory in uploads root directory.
     */
    public function init()
    {
        parent::init();

        if (empty($this->uploadsNewsDir)) $this->uploadsNewsDir = Yii::getAlias('@uploadspath');
        if (empty($this->uploadsNewsUrl)) $this->uploadsNewsUrl = Yii::getAlias('@webfilesurl');

        // enable to upload only $this->uploadAllow mime types
        if (isset($this->uploadAllow) && is_array($this->uploadAllow)) {
            $this->_addOptions['uploadOrder'] = ['deny', 'allow'];
            $this->_addOptions['uploadDeny']  = ['all'];
            $this->_addOptions['uploadAllow'] = $this->uploadAllow;
        }//var_dump($this->_addOptions);exit;
        
        $this->roots = [ // roots of all uploads by default
            [
                'name' => Yii::t($this->tc, 'Uploads files folder'),
                'basePath' => $this->uploadsNewsDir,
                'baseUrl'  => $this->uploadsNewsUrl,
                'path'     => $this->subfolder,

                'options'  => $this->_addOptions,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getManagerOptions()
    {
        $options = parent::getManagerOptions();

        if (isset($this->onlyMimes) && is_array($this->onlyMimes)) {
            $options['onlyMimes']   = $this->onlyMimes;
        }
/* ?? not work here:
        $options['uploadOrder'] = ['deny', 'allow'];
        $options['uploadDeny']  = ['all'];
        if (isset($this->uploadAllow) && is_array($this->uploadAllow)) {
            $options['uploadAllow'] = $this->uploadAllow;
        }//var_dump($options);exit;
*/
        return $options;
    }

}
