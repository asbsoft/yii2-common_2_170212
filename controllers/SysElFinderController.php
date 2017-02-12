<?php

namespace asb\yii2\common_2_170212\controllers;

use zxbodya\yii2\elfinder\ConnectorAction;

use Yii;       
use yii\web\Controller;         
use yii\filters\AccessControl;
use yii\helpers\FileHelper;

class SysElFinderController extends BaseAdminController
{
    public $root;
    public $url;
    public $subdir = '/uploads/'; // from @webroot, finished by '/'

    public $dirMode = 0775;
    
    public function init()
    {
        parent::init();

        $this->url = Yii::getAlias('@web') . $this->subdir;
        $this->root = Yii::getAlias('@webroot') . $this->subdir;//var_dump($this->root);//exit;

        if (!is_dir($this->root)) {
            FileHelper::createDirectory($this->root, $this->dirMode, true);
        }
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['connector'],
                        'allow' => true,
                        'roles' => ['roleAdmin'],
                        //'roles' => ['roleAdmin', 'roleAuthor', 'roleModerator'],
                        //'roles' => ['role_admin','role_author'],
                        //'roles' => ['@'], //all logined
                    ],
                ],
            ],
        ];
    }

    public function actions() {
        return [
            'connector' => [
                'class' => ConnectorAction::className(),
                'settings' => [
                    'root' => $this->root,
                    'URL' => $this->url,
                    'rootAlias' => Yii::t($this->tc, 'Home'),
                    'mimeDetect' => 'none'
                ]
            ],
        ];                    
    }         

}
