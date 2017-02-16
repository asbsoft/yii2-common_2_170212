
Common useful Yii2 extensions for united modules in ASB-projects
================================================================

I like Yii2 framework, but I would like more tools
for build independent, reusable and inheritable modules in this framework.

Here is some useful extensions for my projects provided by "united modules".

Its only my invented bicycles, no warranties.

Unfortunately, Yii2 has two applications template - basic and advanced.
To satisfy both need more complicated configs which too many in Yii2.
I will prepare examples of applications...

Development in progress...

Notes
-----
* Don't forget to make you own config files in config/ folder.
  See *EXAMPLES.php files.

* To work with views inherinance:
  - add to application config in 'components' => [ //...
      'view' => [
          'class' => 'asb\yii2\common_2_170212\web\UniView',
      ],
  - controllers must extend asb\yii2\common_2_170212\controllers\BaseController or BaseAdminController

//...toDo
