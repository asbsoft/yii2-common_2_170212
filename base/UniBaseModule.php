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
//use yii\helpers\ArrayHelper;

/**
 * United module. Base part.
 *
 * @author ASB <ab2014box@gmail.com>
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
    {//echo static::ClassName().'@'.__METHOD__."(id='$id'), config:";var_dump($config);
        $this->_type = static::MODULE_UNITED;

        $addConfig = ConfigsBuilder::getConfig($this);
        $addConfig = ArrayHelper::merge($addConfig, $config);//echo"module($id) result config:";var_dump($addConfig);

        parent::__construct($id, $parent, $addConfig);
    }

    /** 
     * @inheritdoc
     */
    public function init()
    {//echo __METHOD__.'@'.static::className()."[{$this->uniqueId}]".'<br>';
        parent::init();

        // module which contain ModulesManager must be IWithoutUniSubmodules to avoid infinite loop here
//*?? move to getModules():
        if (! $this instanceof IWithoutUniSubmodules) {
            $submodules = ModulesManager::submodules($this);//echo"for {$this::className()}={$this->uniqueId} add submodules ";var_dump(array_keys($submodules));
            $this->modules = ArrayHelper::merge($this->modules, $submodules);//var_dump($this->modules);
        }
/**/
        $this->setConfigPath();

        $this->correctControllerNamespace();

        $this->updateDependencies();

        // $this->params from config may overwrite params from separate files:
        // get params from separate params-files
        $addParams = $this->getParams();//var_dump($addParams);var_dump($this->params);
/*
        $diff = @array_diff($addParams, $this->params);//var_dump($diff);
        if (!empty($diff)) {
            $this->params = ArrayHelper::merge($addParams, $this->params); //?! duplication elements with number keys detected
        }//var_dump($this->params);
*/
        $this->params = ArrayHelper::mergeNoDouble($addParams, $this->params);

        // move to bootstrap:
        //if (!$this->noname) TranslationsBuilder::initTranslations($this);//var_dump($this->templateTransCat);
        //$this->addRoutes();

        if (empty(static::$_bootstrappedModules[$this->uniqueId])) {
            $this->bootstrap(Yii::$app);
        }
        
        // debug show routes
        //var_dump(array_keys(Yii::$app->loadedModules));echo RoutesInfo::showRoutes();//exit;
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
            $result = [];//var_dump(array_keys(Yii::$app->loadedModules));
            foreach (Yii::$app->loadedModules as $module) {
                if ($module instanceof $class) {
                    $result[] = $module;
                }
            }//echo __METHOD__."@$class";var_dump($result);exit;
            if (count($result) == 1) {
                return $result[0];
            } else {
                $msg = "Problem: found many loaded children of module '{$class}' - can't select proper";
                Yii::error($msg);
                //throw new \Exception($msg);
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
        $result = parent::getModules($loadedOnly);//echo __METHOD__."@{$this->className()}<br>";var_dump(array_keys($result));
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getModule($id, $load = true)
    {//echo __METHOD__."($id)<br>";
        $module = parent::getModule($id, $load);
        if ($module instanceof UniModule) {//var_dump($module->uniqueId);
            // add dynamic sub-modules
            $submodules = $module->getModules();//echo'submodules:';var_dump(array_keys($submodules));
        }
        return $module;
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
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
    {//echo $this::ClassName().'@'.__METHOD__.":basePath='{$this->basePath}'<br>";
        $this->configPath = $this->basePath . '/' . static::$configsSubdir; // default
        $pathList = $this->getBasePathList();//var_dump($pathList);
        foreach ($pathList as $path) {
            $confPath = $path . '/' . static::$configsSubdir;
            if (is_dir($confPath)) {
                $this->configPath = $confPath;
                break;
            }
        }//echo"- result conf path: {$this->configPath}<br>";
    }

    /**
     * Syncronize and get module's params.
     * @return array
     */
    public function getParams()
    {//echo $this::className().'::'.__FUNCTION__.'()<br>';
        return $this->params = ConfigsBuilder::getConfig($this, 'params');
    }

    /** Change controllers namespace to first exists in parents modules chain */
    protected function correctControllerNamespace()
    {//echo $this::ClassName().'@'.__METHOD__.":ns='{$this->controllerNamespace}'<br>";
        $nsList = $this->getNamespaceList();//var_dump($nsList);
        $pathList = $this->getBasePathList();//var_dump($pathList);
        foreach ($pathList as $i => $path) {
            $controllersPath = $path . '/' . static::$controllersSubdir;
            if (is_dir($controllersPath)) {
                $this->controllerNamespace = $nsList[$i] . "\\" . static::$controllersSubdir;
                break;
            }
        }//echo"- result ns: {$this->controllerNamespace}<br>";
    }

    /** Append to module dependencies parent classes */
    protected function updateDependencies()
    {//echo __METHOD__."() for {$this->uniqueId}<br>";
        if (!isset($this->_namespaceList)) {
            $this->getBasePathList();//var_dump($this->dependencies);
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
    {//echo __METHOD__."($moduleNamespace)<br>";var_dump(static::$_modulesNs);
        $result = null;
        foreach (static::$_modulesNs as $class => $nsList) {
            foreach ($nsList as $i => $ns) {
                if ($ns == $moduleNamespace) {//echo "found {$class}[{$i}] => {$ns}<br>";
                    if ($findOnlyParent && $i == 0) {//echo "skip<br>";
                        continue;
                    }
                    if (empty($result)) {//echo "save {$class}<br>";
                        $result = $class;
                    } else {
                        throw new \Exception("Find duplicate namespace '{$moduleNamespace}' for modules '{$result}' and '{$class}'");//!! for debug
                        //return false;
                    }
                }
            }
        }//var_dump($result);exit;
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
            $class = new \ReflectionClass($this);//var_dump($class);
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
        }//echo __METHOD__.'@'.$this->className();var_dump($this->_pathList);exit;
        return $this->_pathList;
    }

    /**
     * Get module base namespace
     * @return string
     */
    public function getBaseNamespace()
    {
        $class = new \ReflectionClass($this);
        return $class->getNamespaceName();
    }

    /**
     * Get module class name.
     * @return string
     */
    public function getModuleClassname()
    {
        $class = new \ReflectionClass($this);
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
        }//var_dump($module_id);exit;
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
        $revfid = implode($delimiter, $parts);//echo"reverseFullId='$revfid'<br>";exit;
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
     * @param boolean $loadAnonimous set true to always return module object even if not connect to any another modyule (with random id)
     * @return Module object
     */
    public static function getModuleByClassname($moduleClassName, $loadAnonimous = false)
    {//echo __METHOD__."($moduleClassName)<br>";
        $result = $moduleClassName::getInstance();//var_dump($result);exit;
        if (!empty($result)) {
            return $result; // found from loaded
        }

        //?? deprecated: never run here ...
//*
        $uid = static::getModuleUidByClassname($moduleClassName);//var_dump($uid);
        if (!empty($uid)) {
            $result = Yii::$app->getModule($uid);//var_dump($result);
            if (!empty($result)) {
                return $result;
            } else {
                throw new \Exception("Module with uniqueId '{$uid}' was not registered in application");
            }
        }
/**/

        $message = __METHOD__."($moduleClassName): Can't get module by classname '{$moduleClassName}'.";
        if (!$loadAnonimous) {
            Yii::trace($message);
            //throw new \Exception($message);
            return null;
        } else {
            $message .= ' Create anonimous module.';//echo $message;exit;
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
                    $config = ConfigsBuilder::getConfig($result);//var_dump($result->config);exit;
                    Yii::configure($result, $config);
                    $addParams = $result->getParams(); // from params-files
                    $result->params = ArrayHelper::merge($addParams, $result->params);
                }
                static::$_nonameModules[$moduleClassName] = $result;//var_dump(static::$_nonameModules[$moduleClassName]);
                //static::setInstance($result); //?? save this module in Yii::$app->loadedModules
            }//var_dump($result);
        }
        return $result;
    }

//?? deprecated
//!! This method will load all modules in system if need.
//todo cache
    /**
     * Find uniqueId module by it's className in module/application.
     * Will find only latest className in inheritance chain.
     * @param string $moduleClassName full class name with namespace
     * @param \yii\base\Module $moduleContainer
     * @return string|false module uniqueId
     */
    public static function getModuleUidByClassname($moduleClassName, $moduleContainer = null)
    {//echo __METHOD__."($moduleClassName,".(empty($moduleContainer)?'null':$moduleContainer->uniqueId).')<br>';
        if ($moduleContainer == null) {
            $moduleContainer = Yii::$app; // start from ancestor-container
        }
/*
        if (is_array($moduleContainer)) {//
echo __METHOD__."($moduleClassName,".(empty($moduleContainer)?'null':$moduleContainer->uniqueId).')<br>';
var_dump($moduleContainer);exit;

//todo... load

        }
*/
        if ($moduleContainer instanceof YiiBaseModule) {
             if ($moduleContainer::className() == $moduleClassName) {
                 return $moduleContainer->uniqueId;
             }
             $subModules = $moduleContainer->getModules($loadedOnly = false);//var_dump(array_keys($subModules));
             foreach ($subModules as $subModule) {
                 $uid = static::getModuleUidByClassname($moduleClassName, $subModule);
                 if ($uid) return $uid;
             }
        }
        return false;
    }

    /** Find module by unique Id from loaded modules */
    public static function getModuleByUniqueId($uniqueId)
    {//echo __METHOD__."($uniqueId)<br>";
        $modules = Yii::$app->loadedModules;//var_dump(array_keys($modules));
        foreach ($modules as $module) {//var_dump($module->uniqueId);
            if ($module->uniqueId == $uniqueId) return $module;
        }
        return false;
    }

    /** Find UniModule by unique Id from loaded modules */
    public static function getUniModuleByUniqueId($uniqueId)
    {//echo __METHOD__."($uniqueId)<br>";
        $module = static::getModuleByUniqueId($uniqueId);
        if ($module instanceof UniModule) return $module;
        else return false;
    }

    public static function getModuleByBasePath($modulePath)
    {//echo __METHOD__;echo"('{$modulePath}'):<br>";
        $modules = Yii::$app->loadedModules;//echo'loadedModules:';var_dump(array_keys($modules));
        foreach ($modules as $module) {
            if ($module instanceof UniModule) {
                $class = new \ReflectionClass($module);
                $classPath = dirname($class->getFileName());//var_dump($classPath);
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
    {//echo __METHOD__."($id)<br>";
        $result = parent::createControllerByID($id);
        if (!empty($result)) return $result;

        $saveNs = $this->controllerNamespace;

        $nsList = $this->getNamespaceList();//var_dump($nsList);
        foreach ($nsList as $ns) {
            $this->controllerNamespace = $ns . "\\" . static::$controllersSubdir;
            $result = parent::createControllerByID($id);
            if (!empty($result)) break;
        }//var_dump($result);exit;

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
        }//echo"for {$this->uniqueId} ctrPath:{$this->_controllersPath}<br>";
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
        }//echo"for {$this->uniqueId} ctrPath:{$this->_viewsPath}<br>";
        return $this->_viewsPath;
    }

//--- BEGIN: Some functionality to access models of module

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
            throw new \Exception("Shared model '{$alias}' not found in configuration of module " . static::className());
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
    {//echo __METHOD__."({$alias})@".$this::className().'<br>';var_dump($this->_models);
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
        }//echo static::ClassName();var_dump($this->_models);var_dump($this->params);exit;

        //return false; // or exception is better
        throw new \Exception("Shared model '{$alias}' not found in configuration of module " . static::className());
    }

    /**
     * Search from parents dirs and get model by class name.
     * param string $modelClassBasename model class name without namespace
     * @param array $params constructor parameters
     * @param array $config
     * @return model|null
     */
    public static function dataModelByClass($modelClassBasename, $params = [], $config = [])
    {//echo __METHOD__."('$modelClassBasename')<br>";
        $thisModule = static::instance();

        $pathList = $thisModule->getBasePathList();//var_dump($pathList);
        $nsList = $thisModule->getNamespaceList();
        foreach ($pathList as $i => $basePath) {
            $modelPath = $basePath . DIRECTORY_SEPARATOR . static::$modelsSubdir . DIRECTORY_SEPARATOR . $modelClassBasename . '.php';//var_dump($modelPath);
            if (is_file($modelPath)) {
                $modelFullName = $nsList[$i] . '\\' . static::$modelsSubdir . '\\' . $modelClassBasename;
                break;
            }
        }
        if (!empty($modelFullName)) {//echo"found:'$modelFullName'<br>";
            //return new $modelFullName();
/*
            return Yii::createObject($modelFullName
              , ['module' => $thisModule] // ignore if model not instanceof asb\yii2\models\DataModel
            );
*/
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
//--- END of functionality to access models of module

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
            throw new \Exception("Asset '{$alias}' not found in configuration of module " . static::className());
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
}
