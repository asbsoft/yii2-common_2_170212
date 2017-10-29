<?php

namespace asb\yii2\common_2_170212\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\Inflector;
use yii\helpers\ArrayHelper;

/**
 * Base controller.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class BaseController extends Controller
{
    /** Default login link route on frontend */
    public static $urlLoginDefault = '/login'; // $urlLogin = '/usår/login';

    /** Error page */
    public static $errorActionUniqueId = 'sys/main/error'; // default

    /** Preffered layout path */
    public static $layoutPath;

    /** Common translation category for all module */
    public $tcModule = '';
    /** Common translation category for all controllers in this module */
    public $tcControllers = '';
    /** Common translation category for all models in this module */
    public $tcModels = '';
    /** Translation category personal for this controller */
    public $tc = '';

    /** Data array recommended to use in actions in render calls. Can use in inherited controller. */
    public $renderData;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!empty(Yii::$app->errorHandler->errorAction)) {
            static::$errorActionUniqueId = Yii::$app->errorHandler->errorAction;
        }

        if (!empty($this->module->templateTransCat)) {
            $this->tcModule      = $this->module->tcModule;
            $this->tcModels      = $this->module->tcModels;
            $this->tcControllers = $this->module->tcControllers;

            $name = basename($this->className());
            $name = substr($name, 0, strrpos($name, 'Controller'));//var_dump($name);
            $id = Inflector::camel2id($name);
            $this->tc = str_replace('*', "/controller-{$id}", $this->module->templateTransCat);//var_dump($this->tc);
        }

        // create login link:
        $route = static::urlLogin();
        // $urlLogin = Yii::$app->urlManager->createUrl($route);
        // relative URL will find only in current module, need absolute:
        $urlLogin = Yii::$app->urlManager->createAbsoluteUrl($route);//var_dump($route);var_dump($urlLogin);exit;
        Yii::$app->getUser()->loginUrl = $urlLogin;

        Yii::$app->getErrorHandler()->errorAction = static::$errorActionUniqueId;

        if (!empty(static::$layoutPath)) {
            Yii::$app->layoutPath = static::$layoutPath;
        }
    }

    /** Controller's type */
    protected $_type = 'frontend';
    /** Get controller's type */
    public function getType()
    {
        return $this->_type;
    }

    protected static $_urlLogin;
    /**
     * Get login url/route.
     */
    public static function urlLogin()
    {
        if (!isset(static::$_urlLogin)) {
            static::$_urlLogin = static::$urlLoginDefault;
        }//echo __METHOD__;var_dump(static::$_urlLogin);//exit;
        return static::$_urlLogin;
    }
    /**
     * Set login url/route.
     * @param string|array|null $url link or route to set or null to get
     */
    public static function setUrlLogin($url)
    {
        static::$_urlLogin = $url;
    }

    /**
     * @inheritdoc
     * Find layout in containers('parent') modules properties $layouts according to $this->type
     */
    public function findLayoutFile($view)
    {//echo __METHOD__;var_dump($this->layout);//var_dump($this->module->layoutPath);
        //var_dump($this->id);var_dump($this->module->id);//var_dump($this->type);
        //var_dump(parent::findLayoutFile($view));

        // Get custom layout may sent from sitetree-module:
        $params = Yii::$app->request->getQueryParams();//var_dump($params);
        if (!empty($params['layout'])) {
            $layout = trim($params['layout']);
            $layoutFile = Yii::getAlias($layout);//var_dump($layoutFile);
            if (is_file($layoutFile)) $thisLayout = $layoutFile;
            else if (is_file($layoutFile . '.php')) $thisLayout = $layoutFile . '.php';
            else if (is_file($layoutFile . '.twig')) $thisLayout = $layoutFile . '.twig';
            //else Yii::warning("The view file does not exist: '{$layout}'");
            //var_dump($thisLayout);
        }
        if (empty($thisLayout)) {
            $module = $this->module;//var_dump($module->params);exit;
            while (isset($module)) {//echo"module:{$module->uniqueId}, type:{$this->type}";var_dump($module->layouts);
                if (isset($module->layouts) && isset($module->layouts[$this->type])) {
                    $parentLayout = $module->layouts[$this->type];//echo"found layout:'{$parentLayout}'<br>";
                    $parentLayoutPath = $module->layoutPath;
                    break;
                }
                $module = $module->module;
            }
            if (empty($parentLayout)) {
                return parent::findLayoutFile($view);
            } else {
                $layoutPath = $parentLayoutPath; // ? never empty
                if (!is_dir($layoutPath)) {
                    $layoutPath = empty($this->module->params['layoutPath'])
                        ? Yii::$app->layoutPath
                        : Yii::getAlias($this->module->params['layoutPath'])
                        ;
                }//echo"layoutPath=$layoutPath";
                $layoutFile = $layoutPath . '/' . $parentLayout;//var_dump($layoutFile);

                if (is_file($layoutFile)) {
                    $thisLayout = $layoutFile;
                } else if (is_file($layoutFile . '.php')) {
                    $thisLayout = $layoutFile . '.php';
                } else if (is_file($layoutFile . '.twig')) {
                    $thisLayout = $layoutFile . '.twig';
                } else {
                    $msg = "The view file does not exist: '{$layoutFile}'";//echo $msg;exit;
                    Yii::warning($msg);
                    return parent::findLayoutFile($view);
                }
            }
        }//echo"result layout:'{$thisLayout}'";exit;
        return $thisLayout;
    }

    /**
     * Separate parameters from link and merge with GET-parameters.
     * Useful when all parameters in route parse as single <params:.*>
     * @var string $params parameters from URL separated by '/' in format 'name=value'
     * @return array of parameters ['name' => 'value']
     */
    public function prepareParams($params = '')
    {//var_dump($params);
        $parms = Yii::$app->request->get();//var_dump($parms);
        $addParms = [];
        $list = explode('/', $params);//var_dump($list);
        foreach ($list as $next) {
            if ($next == '') continue;
            $tmp = explode('=', $next);
            if (count($tmp) > 0) $addParms[$tmp[0]] = isset($tmp[1]) ? $tmp[1] : '';
        }
        $parms = ArrayHelper::merge($parms, $addParms);//var_dump($parms);exit;
        return $parms;
    }

    /**
     * @inheritdoc
     * If child module does not have views folder - will use parent views folder
     */
    public function getViewPath()
    {
        $view_path = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
        if (!empty($this->module->parentPath)) // this module is child of another module
        {//var_dump($view_path);var_dump($this->module->parentPath);
            if (!is_dir($view_path)) {
                //$path = $this->module->parentPath . DIRECTORY_SEPARATOR . 'views';//var_dump($path);
                $module = $this->module;
                $path = $this->module->parentPath . DIRECTORY_SEPARATOR . $module::$viewsSubdir;//var_dump($path);
                $this->module->setViewPath($path);
            }
        }
        return parent::getViewPath();
    }

}
