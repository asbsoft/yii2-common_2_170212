<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\base\ModulesManager;

use asb\yii2\common_2_170212\helpers\ConfigsBuilder;
use asb\yii2\common_2_170212\i18n\TranslationsBuilder;

use asb\yii2\common_2_170212\models\DataModel;
use asb\yii2\common_2_170212\web\RoutesInfo;

use asb\yii2\common_2_170212\helpers\ArrayHelper;

use Yii;
use yii\base\Module as YiiBaseModule;
use yii\base\BootstrapInterface;
use yii\base\Controller as YiiBaseController;

use Exception;
use ReflectionClass;

/**
 * United module. Base part.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class UniBaseModule extends BaseModule
{
    /** Default config base file name without suffix '.php' */
    public static $configBasefilename = 'config';

    /** Main config, routes, roles and other config files here */
    public $configPath;

    /** Module not attached to any other module or application */
    public $noname = false;

    /** Layouts ids by types. usually ['frontend' => 'layout-main', 'backend' => 'layout-admin'] */
    public $layouts = [];

    /** Dependencies: classes (modules) must be in system */
    public $dependencies = [];
    
    /** 
     * @inheritdoc
     */
    public function __construct($id, $parent = null, $config = [])
    {
        $this->_type = static::MODULE_UNITED;

        $addConfig = ConfigsBuilder::getConfig($this);
        $addConfig = ArrayHelper::merge($addConfig, $config);

        parent::__construct($id, $parent, $addConfig);
    }

    /** 
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // module which contain ModulesManager must be IWithoutUniSubmodules to avoid infinite loop here
        //?? move to getModules():
        if (! $this instanceof IWithoutUniSubmodules) {
            $submodules = ModulesManager::submodules($this);
            $this->modules = ArrayHelper::merge($this->modules, $submodules);
        }

        $this->setConfigPath();

        $this->correctControllerNamespace();

        $this->updateDependencies();

        // $this->params from config may overwrite params from separate files:
        // get params from separate params-files
        $addParams = $this->getParameters();

        $this->params = ArrayHelper::mergeNoDouble($addParams, $this->params);

        if (empty(static::$_bootstrappedModules[$this->uniqueId])) {
            $this->bootstrap(Yii::$app);
        }

        // only after bootstrap:
        $tcCat = TranslationsBuilder::getBaseTransCategory($this);
        $tc = $tcCat . '/module';
        if (!empty($this->params['label'])) {
            if (!empty(Yii::$app->i18n->translations[$tcCat])) {
                $this->params['label'] = Yii::t($tc, $this->params['label']);
            }
        }
        
        // debug show routes
        //var_dump(array_keys(Yii::$app->loadedModules));echo RoutesInfo::showRoutes();
    }

    /**
     * @inheritdoc
     * @return static|null the currently requested instance of this module class, or `null` if the module class is not requested.
     */
    public static function getInstance()
    {
        $result = parent::getInstance();
        if (!empty($result)) {
            return $result;
        } else {
            // check inheritance of loaded modules
            $class = get_called_class();
            $result = [];
            foreach (Yii::$app->loadedModules as $module) {
                if ($module instanceof $class) {
                    $result[] = $module;
                }
            }
            if (count($result) == 1) {
                return $result[0];
            } else {
                $msg = "Problem: found many loaded children of module '{$class}' - can't select proper";
                Yii::error($msg);
                //throw new Exception($msg);
                //?? todo
                //!! need to allow many children of one ancestor
            }
        }
    }
    
    protected $_additionalModules = null;
    /**
     * @inheritdoc
     */
    public function getModules($loadedOnly = false)
    {
        if (!isset($this->_additionalModules)) {
            if ($this instanceof IWithoutUniSubmodules) {
                $this->_additionalModules = [];
            } else {
                $this->_additionalModules = ModulesManager::submodules($this);
                $this->setModules($this->_additionalModules); // can overwrite existent modules
            }
        }
        $result = parent::getModules($loadedOnly);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getModule($id, $load = true)
    {
        $module = parent::getModule($id, $load);
        if ($module instanceof UniModule) {
            // add dynamic sub-modules
            $submodules = $module->getModules();
        }
        return $module;
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        // always make translations
        TranslationsBuilder::initTranslations($this);
        static::$tc = $this->tcModule;

        if (!$this->noname) {
            parent::bootstrap($app);
        }
    }

    /**
     * Return this module instance
     * @param boolean $loadAnonimous set true to always return module object even if not connect to any another modyule (with random id)
     * @return static
     */
    public static function instance($loadAnonimous = false)
    {
        //return static::getInstance(); // built-in - get only from loaded modules
        return static::getModuleByClassname(self::className(), $loadAnonimous);
    }

    /** Set config path first exists in parents modules chain */
    protected function setConfigPath()
    {
        $this->configPath = $this->basePath . '/' . static::$configsSubdir; // default
        $pathList = $this->getBasePathList();
        foreach ($pathList as $path) {
            $confPath = $path . '/' . static::$configsSubdir;
            if (is_dir($confPath)) {
                $this->configPath = $confPath;
                break;
            }
        }
    }

    /**
     * Syncronize and get module's params.
     * @return array
     */
    public function getParameters()
    {
        $params = ConfigsBuilder::getConfig($this, 'params');
        return $params;
    }

    /** Change controllers namespace to first exists in parents modules chain */
    protected function correctControllerNamespace()
    {
        $nsList = $this->getNamespaceList();
        $pathList = $this->getBasePathList();
        foreach ($pathList as $i => $path) {
            $controllersPath = $path . '/' . static::$controllersSubdir;
            if (is_dir($controllersPath)) {
                $this->controllerNamespace = $nsList[$i] . "\\" . static::$controllersSubdir;
                break;
            }
        }
    }

    /** Append to module dependencies parent classes */
    protected function updateDependencies()
    {
        if (!isset($this->_namespaceList)) {
            $this->getBasePathList();
        }
    }

    /** Array of [moduleClassname => [namespaceList...]] */
    protected static $_modulesNs = [];
    /**
     * Find in inheritance chains module's namespaces and return class of module-owner.
     * @param string $moduleNamespace namespace of one of the parents
     * @param boolean $findOnlyParent
     * @return string|null|false module-owner class name or null if not found or false if collision - found more than one module
     */
    public static function findModuleByNamespace($moduleNamespace, $findOnlyParent = true)
    {
        $result = null;
        foreach (static::$_modulesNs as $class => $nsList) {
            foreach ($nsList as $i => $ns) {
                if ($ns == $moduleNamespace) {
                    if ($findOnlyParent && $i == 0) {
                        continue;
                    }
                    if (empty($result)) {
                        $result = $class;
                    } else {
                        throw new Exception("Find duplicate namespace '{$moduleNamespace}' for modules '{$result}' and '{$class}'");
                        //return false;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Get list of base path for all module parents (not container-"parents"!).
     * Also getlist of namespaces for all module parents and update dependencies.
     * @return array of string
     */
    public function getBasePathList()
    {
        if (!isset($this->_pathList)) {
            $this->_pathList = [];
            $this->_namespaceList = [];
            $class = new ReflectionClass($this);
            while (true) {
                $dirname = dirname($class->getFileName());
                if ($dirname === __DIR__) break; // don't use this class
                
                $this->_pathList[] = $dirname;
                $this->_namespaceList[] = $class->getNamespaceName();

                $class = $class->getParentClass();

                if (empty($class)) break;
                if ($class->name == __CLASS__) break;

                // update module's dependencies

                if ($class->name != UniModule::className() && !in_array($class->name, $this->dependencies)) {
                    $this->dependencies[] = $class->name;
                }
            }
        }
        return $this->_pathList;
    }

    /**
     * Get module base namespace
     * @return string
     */
    public function getBaseNamespace()
    {
        $class = new ReflectionClass($this);
        return $class->getNamespaceName();
    }

    /**
     * Get module class name.
     * @return string
     */
    public function getModuleClassname()
    {
        $class = new ReflectionClass($this);
        return $class->getNamespaceName() . '\Module';
    }

    /**
     * Find this module class from loaded modules and return it's full id.
     */
    public static function fullId()
    {
        $module_id = false;
        $modules = Yii::$app->loadedModules;
        foreach($modules as $module) {
            if ($module instanceof static) {
                $module_id = $module->getUniqueId();
                break;
            }
        }
        return $module_id;
    }

    /**
     * Create reverse unique id for module with unique id $moduleFullId
     * For example, for unique id 'sys/user/group' result will be 'group.user.sys'.
     * Such conversion need now for create non-conflict translation categories for nested modules.
     * @param string $moduleFullId
     * @param string $delimiter delimiter for reverse id parts
     * @return string
     */
    public static function reverseUniqueId($moduleFullId, $delimiter = '.')
    {
        $parts = explode('/', $moduleFullId);
        $parts = array_reverse($parts);
        $revfid = implode($delimiter, $parts);
        return $revfid;
    }

    /**
     * Create reverse unique id for this module.
     * @see reverseFullId()
     * @param string $delimiter delimiter for reverse id parts
     * @return string
     */
    public function getReverseUniqueId($delimiter = '.')
    {
        $moduleFullId = $this->getUniqueId();
        return static::reverseFullId($moduleFullId, $delimiter);
    }

    /** Anonymous modules objects cache: [className => module] */
    protected static $_nonameModules = [];
    public static function showNonameModules() {return static::$_nonameModules;}
    /**
     * Find in loaded modules module by $className.
     * If not found create anonymous module - need for united modules to collect configs, etc.
     * @param string $moduleClassName full class name with namespace
     * @param boolean $loadAnonimous set true to always return module object even if not connect to any another module (with random id)
     * Amonimous loading need for example in modules manager to load and analize module not add to system yet.
     * @return Module object
     */
    public static function getModuleByClassname($moduleClassName, $loadAnonimous = false)
    {
        $result = $moduleClassName::getInstance();
        if (!empty($result)) {
            return $result; // found from loaded
        }

        //?? deprecated: never run here ...
        $uid = static::getModuleUidByClassname($moduleClassName);
        if (!empty($uid)) {
            $result = Yii::$app->getModule($uid);
            if (!empty($result)) {
                return $result;
            } else {
                throw new Exception("Module with uniqueId '{$uid}' was not registered in application");
            }
        }

        $message = __METHOD__."($moduleClassName): Can't get module by classname '{$moduleClassName}'.";
        if (!$loadAnonimous) {
            Yii::trace($message);
            //throw new Exception($message);
            return null;
        } else {
            $message .= ' Create anonimous module.';
            Yii::trace($message);

            if (!empty(static::$_nonameModules[$moduleClassName])) {
                $result = static::$_nonameModules[$moduleClassName];
            } else { // create noname module in cache
                try {
                    $result = new $moduleClassName(uniqid(), null, ['noname' => true]); // noname is UniBaseModule attribute
                } catch (\yii\base\UnknownPropertyException $ex) {
                    $result = new $moduleClassName(uniqid(), null); //?! can't set config
                }
                if ($result instanceof static) { // need to prepare default UniBaseModule configs
                    $config = ConfigsBuilder::getConfig($result);
                    Yii::configure($result, $config);
                    $addParams = $result->getParameters(); // from params-files
                    $result->params = ArrayHelper::merge($addParams, $result->params);
                }
                static::$_nonameModules[$moduleClassName] = $result;
                //static::setInstance($result); //?? save this module in Yii::$app->loadedModules
            }
        }
        return $result;
    }

    /**
     * Find uniqueId module by it's className in module/application.
     * Will find only latest className in inheritance chain.
     * @param string $moduleClassName full class name with namespace
     * @param \yii\base\Module $moduleContainer
     * @return string|false module uniqueId
     */
    public static function getModuleUidByClassname($moduleClassName, $moduleContainer = null)
    {
        if ($moduleContainer == null) {
            $moduleContainer = Yii::$app; // start from ancestor-container
        }
        if ($moduleContainer instanceof YiiBaseModule) {
             if ($moduleContainer::className() == $moduleClassName) {
                 return $moduleContainer->uniqueId;
             }
             $subModules = $moduleContainer->getModules($loadedOnly = false);
             foreach ($subModules as $subModule) {
                 $uid = static::getModuleUidByClassname($moduleClassName, $subModule);
                 if ($uid) return $uid;
             }
        }
        return false;
    }

    /** Find module by unique Id from loaded modules */
    public static function getModuleByUniqueId($uniqueId)
    {
        $modules = Yii::$app->loadedModules;
        foreach ($modules as $module) {
            if ($module->uniqueId == $uniqueId) return $module;
        }
        return false;
    }

    /** Find UniModule by unique Id from loaded modules */
    public static function getUniModuleByUniqueId($uniqueId)
    {
        $module = static::getModuleByUniqueId($uniqueId);
        if ($module instanceof UniModule) return $module;
        else return false;
    }

    public static function getModuleByBasePath($modulePath)
    {
        $modules = Yii::$app->loadedModules;
        foreach ($modules as $module) {
            if ($module instanceof UniModule) {
                $class = new ReflectionClass($module);
                $classPath = dirname($class->getFileName());
                if ($modulePath == $classPath) return $module;
            }
        }
        return false;
    }

    /** 
     * @inheritdoc
     * If controller not found in $this->controllerNamespace it will find in inheritance-parent modules chain.
     */
    public function createControllerByID($id)
    {
        $result = parent::createControllerByID($id);
        if (!empty($result)) return $result;

        $saveNs = $this->controllerNamespace;

        $nsList = $this->getNamespaceList();
        foreach ($nsList as $ns) {
            $this->controllerNamespace = $ns . "\\" . static::$controllersSubdir;
            $result = parent::createControllerByID($id);
            if (!empty($result)) break;
        }

        $this->controllerNamespace = $saveNs;
        return $result;
    }

    /** Controllers path */
    protected $_controllersPath;
    /**
     * @inheritdoc
     * Will find first exists controllers directory from current and inheritance modules.
     * @return string directory of controllers files.
     * If not found return null - It is error - module must have at least one controller.
     */
    public function getControllerPath()
    {
        //return Yii::getAlias('@' . str_replace('\\', '/', $this->controllerNamespace)); // was at parent

        if ($this->_controllersPath === null) {
            //$this->_controllersPath = $this->getBasePath() . DIRECTORY_SEPARATOR . static::$controllersSubdir; // default
            $pathList = $this->getBasePathList();
            foreach ($pathList as $path) {
                $resultPath = $path . DIRECTORY_SEPARATOR . static::$controllersSubdir;
                if (is_dir($resultPath)) {
                    $this->_controllersPath = $resultPath;
                    break;
                }
            }
        }
        return $this->_controllersPath;
    }

    /** Views path */
    protected $_viewsPath;
    /**
     * @inheritdoc
     * Returns the directory that contains the view files for this module.
     * @return string directory of view files. Defaults to "[[basePath]]/views".
     */
    public function getViewPath()
    {
        if ($this->_viewsPath === null) {
            $this->_viewsPath = $this->getBasePath() . DIRECTORY_SEPARATOR . static::$viewsSubdir; // default
            $pathList = $this->getBasePathList();
            foreach ($pathList as $path) {
                $resultPath = $path . DIRECTORY_SEPARATOR . static::$viewsSubdir;
                if (is_dir($resultPath)) {
                    $this->_viewsPath = $resultPath;
                    break;
                }
            }
        }
        return $this->_viewsPath;
    }


    /** Data models defined in config params: 'ALIAS' => 'CLASS' */
    protected $_models = [];
    protected function getModels() {return $this->_models;}
    /** Save models classnames with aliases geting from config */
    protected function setModels($config)
    {
        foreach ($config as $alias => $className) {
            $this->_models[$alias] = $className;
        }
    }
    /**
     * Get model's class name by it's alias defined in module's config.
     * @param string $alias
     * @return string class name
     * @throws Exception if the alias not found.
     */
    public function getModelClassname($alias)
    {
        if (empty($this->_models[$alias])) {
            throw new Exception("Shared model '{$alias}' not found in configuration of module " . static::className());
        }
        return $this->_models[$alias];
    }
    /**
     * Get model's class name by it's alias defined in module's config.
     * @param string $alias
     * @return string|null class name
     * @see getModelClassname()
     */
    public static function modelClassname($alias)
    {
        $module = static::instance();
        if (!empty($module)) {
            return $module->getModelClassname($alias);
        }
    }

    /**
     * Get object of data model by alias defined in module's config.
     * @param string $alias model's alias
     * @param array $params constructor parameters
     * @param array $config config parameters
     * @return yii\base\Model
     */
    public function getDataModel($alias, $params = [], $config = [])
    {
        if (!empty($this->_models[$alias])) {
            //$model = new $this->models[$alias](['module' => $this]); // only for asb\yii2\models\DataModel
            $model = Yii::createObject($this->models[$alias], $params);
            if ($model instanceof DataModel) {
                //$model->module = $this;
                //Yii::configure($model, ['module' => $this]);
                $config['module'] = $this;
                Yii::configure($model, $config);
                $model->prepare();
            }
            return $model;
        }

        //return false; // or exception is better
        throw new Exception("Shared model '{$alias}' not found in configuration of module " . static::className());
    }

    /**
     * Search from parents dirs and get model by class name.
     * param string $modelClassBasename model class name without namespace
     * @param array $params constructor parameters
     * @param array $config
     * @return model|null
     */
    public static function dataModelByClass($modelClassBasename, $params = [], $config = [])
    {
        $thisModule = static::instance();

        $pathList = $thisModule->getBasePathList();
        $nsList = $thisModule->getNamespaceList();
        foreach ($pathList as $i => $basePath) {
            $modelPath = $basePath . DIRECTORY_SEPARATOR . static::$modelsSubdir . DIRECTORY_SEPARATOR . $modelClassBasename . '.php';
            if (is_file($modelPath)) {
                $modelFullName = $nsList[$i] . '\\' . static::$modelsSubdir . '\\' . $modelClassBasename;
                break;
            }
        }
        if (!empty($modelFullName)) {
            $model = Yii::createObject($modelFullName, $params);
            if ($model instanceof DataModel) {
                //$model->module = $this;
                //Yii::configure($model, ['module' => $this]);
                $config['module'] = $this;
                Yii::configure($model, $config);
                $model->prepare();
            }
            return $model;
        }
        return null;
    }

    /**
     * Get data model by alias defined in module config.
     * @see getDataModel()
     * @param string $alias
     * @param array $params constructor parameters
     * @param array $config
     * @see getDataModel()
     */
    public static function model($alias, $params = [], $config = [])
    {
        //$module = static::getInstance(); // built-in - get only from loaded modules
        $module = static::instance();
        if (!empty($module)) {
            return $module->getDataModel($alias, $params, $config);
        }
    }
    //public static function dataModel($alias) { return static::model($alias); } // deprecated


    /** Assets defined in config params: 'ALIAS' => 'CLASS' */
    protected $_assets = [];
    protected function getAssets() {return $this->_assets;}
    /** Save assets classnames with aliases geting from config */
    protected function setAssets($config)
    {
        foreach ($config as $alias => $className) {
            $this->_assets[$alias] = $className;
        }
    }
    /**
     * Get asset's class name by it's alias defined in module's config.
     * @param string $alias
     * @return string class name
     * @throws Exception if the alias not found.
     */
    public function getAssetClassname($alias)
    {
        if (empty($this->_assets[$alias])) {
            throw new Exception("Asset '{$alias}' not found in configuration of module " . static::className());
        }
        return $this->_assets[$alias];
    }
    /**
     * Registers this asset bundle with a view.
     * @param string $alias
     * @param yii\web\View $view the view to be registered with
     * @return static|null the registered asset bundle instance
     */
    public static function registerAsset($alias, $view)
    {
        $module = static::instance();
        if (!empty($module)) {
            $class = $module->getAssetClassname($alias);
            return $class::register($view);
        }
    }

    /** Shared widgets array in format alias => string|array|callable object definition */
    protected $_widgets;
    /** Save widgets classnames with aliases geting from config */
    protected function setWidgets($config)
    {
        foreach ($config as $alias => $className) {
            $this->_widgets[$alias] = $className;
        }
    }
    /**
     * Get widget by it's alias defined in module's config.
     * @param string $alias
     * @param array $params the constructor parameters
     * @return Object widget
     * @throws Exception if the alias not found.
     */
    public function getWidget($alias, array $params = [])
    {
        if (empty($this->_widgets[$alias])) {
            throw new Exception("Shared widget '{$alias}' not found in configuration of module " . static::className());
        }
        $widget = Yii::createObject($this->_widgets[$alias], $params);
        return $widget;
    }
    /**
     * Get widget class by alias defined in module config.
     * @param string $alias
     * @return string|false widget class name of false if not found
     * @throws Exception if widget config has illegal format.
     */
    public static function widgetClass($alias)
    {
        $result = false;
        $module = static::instance();
        if (!empty($module) && !empty($module->_widgets[$alias])) {
            $config = $module->_widgets[$alias];
            if (is_string($config)) {
                $result = $config;
            } elseif (!empty($config['class'])) {
                $result = $config['class'];
            } else {
                throw new Exception("Shared widget '{$alias}' has wrong in configuration in module " . static::className());
            }
        }
        return $result;
    }


}
