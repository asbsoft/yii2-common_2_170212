<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\base\UniApplication;
use asb\yii2\common_2_170212\web\RoutesBuilder;
use asb\yii2\common_2_170212\web\RoutesInfo;
use asb\yii2\common_2_170212\i18n\TranslationsBuilder;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Module;
use yii\web\GroupUrlRule;
use yii\rest\UrlRule as RestUrlRule;
use yii\helpers\Url;

use Exception;
use ReflectionClass;

/**
 * Common base module.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class BaseModule extends Module implements BootstrapInterface
{
    const MODULE_UNITED   = 'MODULE_UNITED';
    const MODULE_FRONTEND = 'MODULE_FRONTEND';
    const MODULE_BACKEND  = 'MODULE_BACKEND';

    /** Source language of module */
    public $sourceLanguage = 'en-US';

    /** Default config subpath from module root directory */
    public static $configsSubdir  = 'config';
    /** Default controllers subdir from root of module */
    public static $controllersSubdir = 'controllers';
    /** Default views subdir from root of module */
    public static $viewsSubdir = 'views';
    /** Default models subdir in module */
    public static $modelsSubdir = 'models';
    /** Default messages subdir in module */
    public static $messagesSubdir = 'messages';

    public static $regControllerFileSuffix = '|([a-z0-9]+)Controller\.php$|i';
    
    /** Default routes subdir in module */
    public static $patternRoutesFilename = 'routes-%s.php';

    /** Common module translation category, must be copy of $tcModule */
    public static $tc = 'app';
    /** Start (prefix) of translation category */
    public $baseTransCategory;
    /** Module translations category template, usually 'MODULE_ID*' */
    public $templateTransCat = '';
    /** Translation category common for all module's models */
    public $tcModels = '';
    /** Translation category common for all module's controllers */
    public $tcControllers = '';
    /** Translation category common for all module */
    public $tcModule = '';

    /**
     * Routes configurations according to applications types:
     * pairs $type => $prefix | array('urlPrefix' => $prefix, 'append' => true|false, ...)
     * Another routesConfig parameters:
     *    'startLink' => array('label' => 'Text...', 'link'|'action' => '...')
     *                   (action is 'controller/actionId' without moduleId)
     * or 'startLinkLabel' => 'Text...' (means 'link' => '' by default)
     *    - define start link with label
     * In array only key 'urlPrefix' is must.
     */
    public $routesConfig = [];

    /** 
     * @var array list of submodules that should be run during the bootstrapping process like in Application.
     * @see \yii\base\Application::$bootstrap
     */
    public $bootstrap = [];

    /** Module type */
    protected $_type;
    /**
     * Get module's type
     */
    public function getType()
    {
        return $this->_type;
    }

    protected $_pathList;
    protected $_namespaceList;
    /**
     * Get list of namespaces for all module parents (not container-"parents"!)
     */
    public function getNamespaceList()
    {
        if (!isset($this->_namespaceList)) $this->getBasePathList();
        static::$_modulesNs[$this::className()] = $this->_namespaceList;
        return $this->_namespaceList;
    }
    /**
     * Get list of base path for all module parents (not container-"parents"!).
     * Also getlist of namespaces for all module parents and update dependencies.
     * This basic version return list with only one path.
     * @see UniBaseModule - full version
     * @return array of string
     */
    public function getBasePathList()
    {
        if (!isset($this->_pathList)) {
            $this->_pathList = [];
            $this->_namespaceList = [];

            $class = new ReflectionClass($this);
            $dirname = dirname($class->getFileName());
                
            $this->_pathList[] = $dirname;
            $this->_namespaceList[] = $class->getNamespaceName();

        }
        return $this->_pathList;
    }

    /** Models path */
    protected $_modelsPath;
    /**
     * Will find first exists models directory from current and inheritance modules.
     * @return string directory of models files. Defaults to "[[basePath]]/models".
     */
    public function getModelsPathList()
    {
        if ($this->_modelsPath === null) {
            $this->_modelsPath = $this->getBasePath() . DIRECTORY_SEPARATOR . static::$modelsSubdir; // default
            $pathList = $this->getBasePathList();
            foreach ($pathList as $path) {
                $resultPath = $path . DIRECTORY_SEPARATOR . static::$modelsSubdir;
                if (is_dir($resultPath)) {
                    $this->_modelsPath = $resultPath;
                    break;
                }
            }
        }
        return $this->_modelsPath;
    }

    /**
     * Add module routes defined in config.
     */
    public function addRoutes()
    {
        list($rulesBefore, $rulesAfter) = $this->collectRoutes();
        Yii::$app->urlManager->addRules($rulesBefore, false);
        Yii::$app->urlManager->addRules($rulesAfter, true);
        //echo'<pre>'.RoutesInfo::showRoutes($this->uniqueId).'</pre>';exit;
    }

    /**
     * Collect module routes defined in config.
     * System will use only latest (in inheritance chain) module's routes, not mixed with ancestor's routes.
     * @return array
     */
    public function collectRoutes()
    {
        $rulesBefore = [];
        $rulesAfter = [];
        $isNoname = $this instanceof UniModule ? $this->noname : false;
        if (!$isNoname && !empty($this->routesConfig)) {
            //$routesSubdir = $this->getRoutesPath();
            foreach ($this->routesConfig as $type => $config) {
                if (is_string($config)) { // $config may be '' as urlPrefix
                    $routeConfig = ['urlPrefix' => $config];
                } elseif (is_array($config) && count($config) > 0) {
                    $routeConfig = $config;
                } else {
                    continue;
                }

                $file = static::getRoutesFilename($this, $type);
                if (is_file($file)) {
                    $routeConfig['fileRoutes'] = $file;
                    $routeConfig['routesType'] = $type;
                    $routeConfig['class']      = empty($config['class']) ? GroupUrlRule::className() : $config['class'];
                    $routeConfig['moduleUid']  = $this->uniqueId;
                    $routeConfig['urlPrefix']  = $this->collectUrlPrefix($routeConfig['urlPrefix'], $type);
                    if (!isset($routeConfig['append'])) {
                        $routeConfig['append'] = false;
                    }

                    //RoutesBuilder::buildRoutes($routeConfig); // deprecated, prepare to caching all module's routes together
                    $nextRules = RoutesBuilder::collectRoutes($routeConfig);
                    if ($routeConfig['append']) {
                        $rulesAfter = array_merge($rulesAfter, $nextRules);
                    } else {
                        $rulesBefore = array_merge($nextRules, $rulesBefore);
                    }
                    $this->setStartLink($routeConfig);
                } else {
                    throw new Exception("Routes list file '{$file}' not found!");
                }
            }
        }
        return [$rulesBefore, $rulesAfter];
    }

    /**
     * Create routes filename of routes $type for $module.
     * @param Module $module
     * @param string $type
     * @return string|false
     */
    public static function getRoutesFilename($module, $type)
    {
        //if (! $module instanceof self) return '';
        //$routesSubdir = $module->getRoutesPath(); // not correct

        // find routes from parents modules dirs chain
        $pathList = $module->getBasePathList();
        $baseFileName = sprintf(static::$patternRoutesFilename, $type);
        foreach ($pathList as $path) {
            $routesSubdir = $path . DIRECTORY_SEPARATOR . static::$configsSubdir;
            $file = $routesSubdir . '/' . $baseFileName;
            if (is_file($file)) {
                return $file;
            }
        }
        if ($module instanceof self) {
            $msg = "Routes list file '{$baseFileName}' not found in configs folder(s) for module " . $module->className();
            throw new Exception($msg);
        } else {
            return false;
        }
    }

    /**
     * Add prefixes such $type from parent module(s) to route's URL-prefix.
     * @param string $urlPrefix
     * @param string $type
     * @return string
     */
    protected function collectUrlPrefix($urlPrefix, $type)
    {
        $module = $this;
        while ($module = $module->module) {
            if (! $module instanceof self) break;
            if (empty($module->routesConfig[$type])) continue;

            $config = $module->routesConfig[$type];
            if (is_string($config)) {
                $parentPrefix = $config;
            } elseif (!empty($config['urlPrefix'])) {
                $parentPrefix = $config['urlPrefix'];
            }
            if (!empty($parentPrefix)) {
                // if $urlPrefix begin with '/' use it as absolute prefix
                if (substr($urlPrefix, 0, 1) == '/') {
                    $urlPrefix = substr($urlPrefix, 1);
                } else {
                    $urlPrefix = "{$parentPrefix}/{$urlPrefix}";
                }
            }
        }
        return $urlPrefix;
    }

    /**
     * Start links to module - different for every routesType.
     * array[module->uniqueId][routesType] => array[link, label]
     */
    protected static $_startLinks = [];
    /**
     * Set start link for module.
     */
    protected function setStartLink($routeConfig)
    {
        if (empty($routeConfig['startLink']) && !empty($routeConfig['startLinkLabel'])) {
            $routeConfig['startLink'] = [
                'label' => $routeConfig['startLinkLabel'],
                'link'  => '', // default
            ];
        }
        if (!empty($routeConfig['startLink'])) {
            if (!empty($routeConfig['startLink']['action'])) {
                $action = '/' . $routeConfig['moduleUid'] . '/' . $routeConfig['startLink']['action'];
                $route = [$action];
                $url = Url::toRoute($action);
            } elseif (isset($routeConfig['startLink']['link'])) {
                $link = $routeConfig['startLink']['link'];
                $link = '/' . $routeConfig['urlPrefix']
                       . ( ($link == '' || $link == '?') ? '' : ('/' . $link) )
                       ;
                //$route = ??;
                $url = Url::toRoute($link);
            } else {
                throw new Exception("Insufficient 'link' or 'action' in 'startLink' paremeter of routeConfig");
            }
            
            $tcCat = TranslationsBuilder::getBaseTransCategory($this);
            $label = $routeConfig['startLink']['label'];
            $linkData = [
                'label' => $label,
                'tcCat' => $tcCat,
                'link'  => $url,
            ];
            if (isset($route)) $linkData['route'] = $route;

            static::$_startLinks[$this->uniqueId][$routeConfig['routesType']] = $linkData;
        }
    }
    /**
     * Get start link for module.
     * @var string $moduleUid unique id of module
     * @var string $routesType type of routes collection
     */
    public static function startLink($moduleUid, $routesType)
    {
        if (!empty(static::$_startLinks[$moduleUid][$routesType])) {
            $linkData = static::$_startLinks[$moduleUid][$routesType];

            $tcCat = $linkData['tcCat'];
            $tc = "{$tcCat}/module";
            if (!empty(Yii::$app->i18n->translations["{$tcCat}*"])) {
                $label = $linkData['label'];
                $linkData['label'] = Yii::t($tc, $label);
            }
            return $linkData;
        }
        return false;
    }

    /** Array of already bootstrapped modules in format module's uniqueId => true */
    protected static $_bootstrappedModules = [];
    /**
     * @inheritdoc
     *
     * For bootstraping nested modules you can add to parent-container module's config short moduleId
     * 'bootstap' => [..., '(nextSubmoduleShortId)', ...]
     * It is similar to add Yii::$app->bootstrap[] = 'moduleUniqueId'
     * but you can't know full unique id for this submodule here.
     *
     * You can change inheritance of you submodule from yii\base\Module to asb\yii2\...\base\BaseModule.
     * For properly bootstrap of Bootstrap class in nested module you can use
     * ```php
     *   class Module extends BaseModule {
     *       public function bootstrap($app) {
     *           Yii::createObject(Bootstrap::className())->bootstrap($app);
     *           parent::bootstrap($app);
     *       }
     *   }
     * ```
     * without manual add this Bootstrap class to Yii::$app->bootstrap.
     *
     */
    public function bootstrap($app)
    {
        $appKey = UniApplication::appKey($app);

        if (empty(static::$_bootstrappedModules[$appKey][$this->uniqueId])) {
            static::$_bootstrappedModules[$appKey][$this->uniqueId] = true;

            TranslationsBuilder::initTranslations($this);
            static::$tc = $this->tcModule;

            $this->addRoutes();

            // bootstrap submodules such as in yii\base\Application
            foreach ($this->bootstrap as $class) {
                $component = null;
                if (is_string($class)) {
                    if ($this->has($class)) {
                        $component = $this->get($class);
                    } elseif ($this->hasModule($class)) {
                        $component = $this->getModule($class);
                    } elseif (strpos($class, '\\') === false) {
                        throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
                    }
                }
                if (!isset($component)) {
                    $component = Yii::createObject($class);
                }
/*
                if ($component instanceof BootstrapInterface) {
                    Yii::trace('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                    //$component->bootstrap($app); //!? twice bootstrap $component
                } else {
                    Yii::trace('Bootstrap with ' . get_class($component), __METHOD__);
                }
*/
            }
        }
    }

}
