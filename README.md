
Common useful Yii2 extensions for united modules in ASB-projects
================================================================

I like Yii2 framework, but I would like more tools
for build independent, reusable and inheritable modules in this framework.

Here is some experimental extensions for my projects provided by "united modules".

Yii2 has two applications template - basic and advanced.
To satisfy both need more complicated configs which too many in Yii2.
I will prepare examples of applications configs...


Notes
-----
* Add to system all dependencies defined in "require" part of composer.json.

* Use autoload.php here to register additional autoloads.

* Register namespace \asb\yii2\common_2_170212 in system by composer or manually by define alias
  f.e. Yii::setAlias('@asb/yii2/common_2_170212', '@vendor/asbsoft/yii2-common_2_170212');
  This definition you can place in index.php, most common config(s)
  or better way in @vendor/yiisoft/extensions.php to provide work
  in any of basic/frontend/backend/console application.

* Make you own config files in config folder.
  Use *.EXAMPLES.php files as examples.

* Run migrations.

* To use additional functionality need to extends modules from \asb\yii2\common_2_170212\base\UniModule,
  controllers - from \asb\yii2\common_2_170212\controllers\BaseController and BaseAdminController,
  and active records models - from asb\yii2\common_2_170212\models\DataModel.
  Also add to application config in 'components' => [ //...
      'view' => [
          'class' => 'asb\yii2\common_2_170212\web\UniView',
      ], // to work with views inherinance
  This operationd provide mechanism of modules inheritance:
  configs and messages will merged, views and actions will get from latest child.

* Extension also has some functionality for switching multilanguage support provide by
  asb\yii2\common_2_170212\i18n\LangHelper and bootstraping by asb\yii2\common_2_170212\base\CommonBootstrap.


Using modules inheritance
-------------------------
* Extends your ancestor module from base\UniModule.
  You can create another module with module class in root directory extends of ancestor module class.
  Than add this module as submodule to config of application or another module-container.

* Now work such features:
  - configs and params will merge with ancestors' data
  - messages will merge
  - for route(s) get latest file(s) 
  - for view(s) get latest file(s) - possible to redefine only required file(s)
  - controllers and models - traditional inheritance
  - to use models inheritans you have to (re)define using models in config/config.php of module
    in format alias => class name or object array:
      'models' => [..., 'ALIAS' => 'VENDOR\yii2\modules\MODULE_NAME\models\News', ...]
    for example
      'News' => 'asb\yii2\modules\news_1\models\News', // in module-ancestor
      'News' => 'asb\yii2\modules\news_2\models\News', // in module-child
    and for access to model you have to use everywhere (only for models in modules extends UniModule):
    - $module->getDataModel($alias, $params = [], $config = []) // get and init module object
      or same static method: ModuleClassName::model($alias, $params = [], $config = [])
  - to use assets inheritans you have to (re)define using assetss in config/config.php of module
    in format alias => class name:
      'models' => [..., 'ALIAS' => 'CLASSNAME', ...]
    and in view use $assets = $this->context->module->registerAsset('MyAsset', $this);
    instead of $assets = MyAsset::register($this);
    and in new asset better use old asset as 'depends' not as a child -
    new CSS-files will include after and will redefine old styles


