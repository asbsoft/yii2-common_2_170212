<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\web\RoutesBuilder;
use asb\yii2\common_2_170212\web\RoutesInfo;
use asb\yii2\common_2_170212\i18n\TranslationsBuilder;

use Yii;
use yii\base\Module;
use yii\web\GroupUrlRule;
use yii\rest\UrlRule as RestUrlRule;
use yii\helpers\Url;

/**
 * Common base module.
 * @author ASB <ab2014box@gmail.com>
 */
class BaseModule extends Module
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

    /** Start of translation category */
    public $baseTransCategory;
    /** Module translations category template, usually 'MODULE_ID*' */
    public $templateTransCat = '';
    /** Translation category common for all module's models */
    public $tcModels = '';
    /** Translation category common for all module's controllers */
    public $tcControllers = '';
    /** Translation category common for all module */
    public $tcModule = '';
    /** Common module translation category, must be copy of $tcModule */
    public static $tc = 'app';

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
     * @see BaseUniModule - full version
     * @return array of string
     */
    public function getBasePathList()
    {
        if (!isset($this->_pathList)) {
            $this->_pathList = [];
            $this->_namespaceList = [];

            $class = new \ReflectionClass($this);
            $dirname = dirname($class->getFileName());
                
            $this->_pathList[] = $dirname;
            $this->_namespaceList[] = $class->getNamespaceName();

        }//echo __METHOD__.'@'.$this->className();var_dump($this->_pathList);exit;
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
        }//echo"for {$this->uniqueId} modelsPath:{$this->_modelsPath}<br>";
        return $this->_modelsPath;
    }

    /**
     * Add module routes defined in config.
     * System will use only latest routes, it not mix them with parents routes.
     */
    public function addRoutes()
    {//echo static::className()."[{$this->uniqueId}]".'@'.__METHOD__.'()<br>';//var_dump($this->noname);
        $isNoname = $this instanceof UniModule ? $this->noname : false;
        if (!$isNoname && !empty($this->routesConfig)) {//var_dump($this->routesConfig);
            //$routesSubdir = $this->getRoutesPath();//var_dump($routesSubdir);
            foreach ($this->routesConfig as $type => $config) {
                if (is_string($config)) { // $config may be '' as urlPrefix
                    $routeConfig = ['urlPrefix' => $config];
                } elseif (is_array($config) && count($config) > 0) {
                    $routeConfig = $config;
                } else {
                    continue;
                }

                $file = static::getRoutesFilename($this, $type);//echo"file='$file'<br>";
                if (is_file($file)) {
                    $routeConfig['fileRoutes'] = $file;
                    $routeConfig['routesType'] = $type;
                    $routeConfig['class']      = empty($config['class']) ? GroupUrlRule::className() : $config['class'];
                    $routeConfig['moduleUid']  = $this->uniqueId;
                    $routeConfig['urlPrefix']  = $this->collectUrlPrefix($routeConfig['urlPrefix'], $type);
                    if (!isset($routeConfig['append'])) {
                        $routeConfig['append'] = false;
                    }//echo $type;var_dump($routeConfig);
                    RoutesBuilder::buildRoutes($routeConfig);

                    $this->setStartLink($routeConfig);
                } else {
                    throw new \Exception("Routes list file '{$file}' not found!");
                }
            }
        }//echo RoutesInfo::showRoutes($this->uniqueId);exit;
    }

    /**
     * Create routes filename of routes $type for $module.
     * @param Module $module
     * @param string $type
     * @return string|false
     */
    public static function getRoutesFilename($module, $type)
    {//echo __METHOD__."({$module->className()})".'@'.static::className().'<br>';
        //if (! $module instanceof self) return '';
        //$routesSubdir = $module->getRoutesPath(); // not correct

        // find routes from parents modules dirs chain
        $pathList = $module->getBasePathList();//var_dump($pathList);
        $baseFileName = sprintf(static::$patternRoutesFilename, $type);
        foreach ($pathList as $path) {
            $routesSubdir = $path . DIRECTORY_SEPARATOR . static::$configsSubdir;
            $file = $routesSubdir . '/' . $baseFileName;//echo "file='$file'<br>";
            if (is_file($file)) {//echo "- found routes config: '$file'<br>";
                return $file;
            }
        }
        if ($module instanceof self) {//exit;
            throw new \Exception("Routes list file '{$baseFileName}' not found in config(s) folder(s) for module " . $module->className());
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
    {//echo __METHOD__."($urlPrefix, $type) for {$this->uniqueId}<br>";
        $module = $this;
        while ($module = $module->module) {
            if (! $module instanceof self) break;//echo"parent:{$module->uniqueId}<br>";var_dump($module->routesConfig);
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
        }//echo"result prefix:{$urlPrefix}<br>";
        return $urlPrefix;
    }

    /**
     * Start links to module - differen for every routesType.
     * array[module->uniqueId][routesType] => array[link, label]
     */
    protected static $_startLinks = [];
    /**
     * Set start link for module.
     */
    protected function setStartLink($routeConfig)
    {//echo __METHOD__."@{$this->uniqueId}";var_dump($routeConfig);

        if (empty($routeConfig['startLink']) && !empty($routeConfig['startLinkLabel'])) {
            $routeConfig['startLink'] = [
                'label' => $routeConfig['startLinkLabel'],
                'link'  => '', // default
            ];
        }
        if (!empty($routeConfig['startLink'])) {//var_dump($routeConfig['startLink']);echo RoutesInfo::showRoutes($this->uniqueId);
            if (!empty($routeConfig['startLink']['action'])) {
                $action = '/' . $routeConfig['moduleUid'] . '/' . $routeConfig['startLink']['action'];//var_dump($action);
                $url = Url::toRoute($action);
            } elseif (isset($routeConfig['startLink']['link'])) {
                $link = $routeConfig['startLink']['link'];//var_dump($link);
                $link = '/' . $routeConfig['urlPrefix']
                       . ( ($link == '' || $link == '?') ? '' : ('/' . $link) )
                       ;//var_dump($link);
                $url = Url::toRoute($link);
            } else {
                throw new \Exception("Insufficient 'link' or 'action' in 'startLink' paremeter of routeConfig");
            }
            
            $tc = TranslationsBuilder::getBaseTransCategory($this) . '/module';//var_dump($tc);
            $linkData = [
                'label' => Yii::t($tc, $routeConfig['startLink']['label']),
                'link'  => $url,
            ];
            static::$_startLinks[$this->uniqueId][$routeConfig['routesType']] = $linkData;//var_dump(static::$_startLinks);
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
            return static::$_startLinks[$moduleUid][$routesType];
        }
        return false;
    }

}
