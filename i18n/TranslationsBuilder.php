<?php

namespace asb\yii2\common_2_170212\i18n;

use asb\yii2\common_2_170212\i18n\UniPhpMessageSource as MessageSource;

use Yii;
use yii\base\Component;
use yii\helpers\Inflector;

/**
 * Module translations builder
 *
 * @author ASB <ab2014box@gmail.com>
 */
class TranslationsBuilder extends Component
{
    public static $transCatPrefix = 'app';

    /** Info about modules correspond to translation category */
    public static $transCatToModule = [];
    
    public static function getBaseTransCategory($module)
    {
        if (!empty($module->baseTransCategory)) {
            $baseTransCategory = $module->baseTransCategory;
        } else {
//            $baseTransCategory = $module->getReverseUniqueId(); //?? deprecated but work
            //!! problems can be for modules: 'a/z/a/z' -> 'z.a.z.a*', 'z/a/z' -> 'z.a.z*'
            // after krsort(Yii::$app->i18n->translations) category 'z.a.z*' stay first and catch requests with 'z.a.z.a*'
//*
            //!? need but problems
            $baseTransCategory = $module->uniqueId;
            $baseTransCategory = static::$transCatPrefix . '/' . $module->uniqueId;
/**/
        }//var_dump($baseTransCategory);
        return $baseTransCategory;
    }

    /**
     * Build default initial module translations, file maps, etc.
     * @param asb\yii2\common_2_170212\base\UniModule $module module instance
     */
    public static function initTranslations($module)
    {
        $baseTransCategory = static::getBaseTransCategory($module);
        $module->templateTransCat = $baseTransCategory . '*';//echo"templateTransCat:'{$module->templateTransCat}'<br>";

        if (empty(static::$transCatToModule[$module->templateTransCat]))
        {
            static::$transCatToModule[$module->templateTransCat] = $module;//foreach(static::$transCatToModule as $tc => $m) echo "$tc => {$m::ClassName()}<br>";

            // check module with all parents for exist messages subdir, stop on first found:
            $basePathList = $module->getBasePathList();//var_dump($basePathList);
            foreach ($basePathList as $basePath) {
                $messagesBasePath = $basePath . '/' . $module::$messagesSubdir;//echo"isdir?messagesBasePath:{$messagesBasePath}<br>";
                if (is_dir($messagesBasePath)) {//echo"isdir!messagesBasePath:{$messagesBasePath}<br>";
                    break;
                //} else { $messagesBasePath = $module->basePath . '/' . $module::$messagesSubdir; } // default is subdir of initial module
                }
            }//echo"for {$module->uniqueId}: templateTransCat:'{$module->templateTransCat}', messagesBasePath:'{$messagesBasePath}'<br>";//exit;
            
            $module->tcModule         = $baseTransCategory . '/module';
            $module->tcModels         = $baseTransCategory . '/models';
            $module->tcControllers    = $baseTransCategory . '/controllers';

            $fileMap = [
                $module->tcModule      => 'module.php',
                $module->tcModels      => 'models.php',
                $module->tcControllers => 'controllers.php',
            ];
            
            $prefix = 'controller-';
            $path = $module->getControllerPath();
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $file) {
                    $tail = 'Controller.php';
                    if (!empty($file) && substr_compare($file, $tail, -strlen($tail), strlen($tail)) === 0) {
                        $name = Inflector::camel2id(substr(basename($file), 0, -strlen($tail)));
                        $fileMap["{$baseTransCategory}/{$prefix}{$name}"] = "{$prefix}{$name}.php";
                    }
                }
            }

            $prefix = 'model-';
            $path = $module->getModelsPathList();
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $file) {
                    $tail = '.php';
                    if (!empty($file) && substr_compare($file, $tail, -strlen($tail), strlen($tail)) === 0) {
                        $name = Inflector::camel2id(substr(basename($file), 0, -strlen($tail)));
                        $fileMap["{$baseTransCategory}/{$prefix}{$name}"] = "{$prefix}{$name}.php";
                    }
                }
            }//echo"templateTransCat:'{$module->templateTransCat}',fileMap:";var_dump($fileMap);

            Yii::$app->getI18n()->translations[$module->templateTransCat] = [
                'class' => MessageSource::ClassName(),
                'basePath' => $messagesBasePath,
                'fileMap' => $fileMap,
                'sourceLanguage' => $module->sourceLanguage,
                'on missingTranslation' => [TranslationEventHandler::className(), 'handleMissingTranslation'],
            ];//echo"{$module->uniqueId}:";var_dump(Yii::$app->getI18n()->translations[$module->templateTransCat]);

            krsort(Yii::$app->i18n->translations); //!! category 'sys/content*' must be before 'sys*'
            krsort(static::$transCatToModule);     // same problem
        }
    }

}
