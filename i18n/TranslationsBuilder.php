<?php

namespace asb\yii2\common_2_170212\i18n;

use asb\yii2\common_2_170212\i18n\UniPhpMessageSource as MessageSource;

use Yii;
use yii\base\Component;
use yii\helpers\Inflector;

/**
 * Module translations builder.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
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
            // may be problem - category 'sys/content*' must be before 'sys*' - need krsort(Yii::$app->i18n->translations)
            $baseTransCategory = $module->uniqueId;
            $baseTransCategory = static::$transCatPrefix . '/' . $module->uniqueId;
        }
        return $baseTransCategory;
    }

    /**
     * Build default initial module translations, file maps, etc.
     * @param asb\yii2\common_2_170212\base\UniModule $module module instance
     * @param boolean $rewrite if true necessary to rewrite translations
     */
    public static function initTranslations($module, $rewrite = false)
    {
        $baseTransCategory = static::getBaseTransCategory($module);
        $module->templateTransCat = $baseTransCategory . '*';

        if ($rewrite || empty(static::$transCatToModule[$module->templateTransCat]))
        {
            static::$transCatToModule[$module->templateTransCat] = $module;

            // check module with all parents for exist messages subdir, stop on first found:
            $basePathList = $module->getBasePathList();
            foreach ($basePathList as $basePath) {
                $messagesBasePath = $basePath . '/' . $module::$messagesSubdir;
                if (is_dir($messagesBasePath)) {
                    break;
                }
            }
            
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
            }

            Yii::$app->getI18n()->translations[$module->templateTransCat] = [
                'class' => MessageSource::ClassName(),
                'basePath' => $messagesBasePath,
                'fileMap' => $fileMap,
                'sourceLanguage' => $module->sourceLanguage,
                'on missingTranslation' => [TranslationEventHandler::className(), 'handleMissingTranslation'],
            ];

            krsort(Yii::$app->i18n->translations); //!! category 'sys/content*' must be before 'sys*'
            krsort(static::$transCatToModule);     // same problem

            $msg = __FUNCTION__ . "(): loaded translations for module '{$module->uniqueId}': " . $module::className();
            Yii::trace($msg);
        }
    }

}
