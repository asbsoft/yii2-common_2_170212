<?php

namespace asb\yii2\common_2_170212\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\Inflector;
use yii\helpers\ArrayHelper;

/**
 * Base controller.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
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

    /** Data params array saving after run action. Can use in inherited controller. */
    public $renderData;

    /** Controller's type */
    protected $_type = 'frontend';

    /** Login url/route */
    protected static $_urlLogin;

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
            $name = substr($name, 0, strrpos($name, 'Controller'));
            $id = Inflector::camel2id($name);
            $this->tc = str_replace('*', "/controller-{$id}", $this->module->templateTransCat);
        }

        // create login link:
        $route = static::urlLogin();

        // relative URL will find only in current module, need absolute:
        $urlLogin = Yii::$app->urlManager->createAbsoluteUrl($route);
        Yii::$app->getUser()->loginUrl = $urlLogin;

        Yii::$app->getErrorHandler()->errorAction = static::$errorActionUniqueId;

        if (!empty(static::$layoutPath)) {
            Yii::$app->layoutPath = static::$layoutPath;
        }
    }

    /** Get controller's type */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Get login url/route.
     */
    public static function urlLogin()
    {
        if (!isset(static::$_urlLogin)) {
            static::$_urlLogin = static::$urlLoginDefault;
        }
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
    {
        // Get custom layout may sent from sitetree-module:
        $params = Yii::$app->request->getQueryParams();
        if (!empty($params['layout'])) {
            $layout = trim($params['layout']);
            $layoutFile = Yii::getAlias($layout);
            if (is_file($layoutFile)) $thisLayout = $layoutFile;
            else if (is_file($layoutFile . '.php')) $thisLayout = $layoutFile . '.php';
            else if (is_file($layoutFile . '.twig')) $thisLayout = $layoutFile . '.twig';
          //else Yii::warning("The view file does not exist: '{$layout}'");
        }
        if (empty($thisLayout)) {
            $module = $this->module;
            while (isset($module)) {
                if (isset($module->layouts) && isset($module->layouts[$this->type])) {
                    $parentLayout = $module->layouts[$this->type];
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
                }
                $layoutFile = $layoutPath . '/' . $parentLayout;

                if (is_file($layoutFile)) {
                    $thisLayout = $layoutFile;
                } else if (is_file($layoutFile . '.php')) {
                    $thisLayout = $layoutFile . '.php';
                } else if (is_file($layoutFile . '.twig')) {
                    $thisLayout = $layoutFile . '.twig';
                } else {
                    $msg = "The view file does not exist: '{$layoutFile}'";
                    Yii::warning($msg);
                    return parent::findLayoutFile($view);
                }
            }
        }
        return $thisLayout;
    }

    /**
     * Separate parameters from link and merge with GET-parameters.
     * Useful when all parameters in route parse as single <params:.*>
     * @var string $params parameters from URL separated by '/' in format 'name=value'
     * @return array of parameters ['name' => 'value']
     */
    public function prepareParams($params = '')
    {
        $parms = Yii::$app->request->get();
        $addParms = [];
        $list = explode('/', $params);
        foreach ($list as $next) {
            if ($next == '') continue;
            $tmp = explode('=', $next);
            if (count($tmp) > 0) $addParms[$tmp[0]] = isset($tmp[1]) ? $tmp[1] : '';
        }
        $parms = ArrayHelper::merge($parms, $addParms);
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
        {
            if (!is_dir($view_path)) {
                //$path = $this->module->parentPath . DIRECTORY_SEPARATOR . 'views';
                $module = $this->module;
                $path = $this->module->parentPath . DIRECTORY_SEPARATOR . $module::$viewsSubdir;
                $this->module->setViewPath($path);
            }
        }
        return parent::getViewPath();
    }

    /**
     * @inheritdoc
     * Save render data in $this->renderData
     */
    public function render($view, $params = [])
    {
        $this->renderData = $params;
        return parent::render($view, $params);
    }

    /**
     * @inheritdoc
     * Save render data in $this->renderData
     */
    public function renderPartial($view, $params = [])
    {
        $this->renderData = $params;
        return parent::renderPartial($view, $params);
    }

    /**
     * @inheritdoc
     * Save render data in $this->renderData
     */
    public function renderFile($file, $params = [])
    {
        $this->renderData = $params;
        return parent::renderFile($file, $params);
    }

    /**
     * @inheritdoc
     * Save render data in $this->renderData
     */
    public function renderAjax($view, $params = [])
    {
        $this->renderData = $params;
        return parent::renderAjax($view, $params);
    }

}
