
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

* Register namespace \asb\yii2\common_2_170212 in system by composer or manually by define alias
  Yii::setAlias('@asb/yii2/common_2_170212', '@vendor/asbsoft/yii2-common_2_170212');
  This definition you can place in index.php, most common config(s)
  or better way in @vendor/yiisoft/extensions.php to provide work
  in any of basic/frontend/backend/console application.

* Run migrations.

* Make you own config files in config folder.
  Use *.EXAMPLES.php files as examples.

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

* Other functionality is optional.
