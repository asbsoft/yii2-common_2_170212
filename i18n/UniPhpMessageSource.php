<?php

namespace asb\yii2\common_2_170212\i18n;

use asb\yii2\common_2_170212\base\UniModule;

use asb\yii2\common_2_170212\i18n\TranslationEventHandler;
use asb\yii2\common_2_170212\i18n\TranslationsBuilder;

use yii\i18n\PhpMessageSource;
use yii\helpers\ArrayHelper;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class UniPhpMessageSource extends PhpMessageSource
{
    /** Loaded messages cache */
    protected static $_messages = [];

    public function init()
    {
        parent::init();

        $this->sourceLanguage = 'en-US';
        
        $this->on(self::EVENT_MISSING_TRANSLATION
          , [TranslationEventHandler::className(), 'handleMissingTranslation']);
    }

    /** Check to exists message translation */
    public function existsMessage($language, $category, $message)
    {
        $key = $language . '/' . $category;
        if (!isset(static::$_messages[$key])) {
            static::$_messages[$key] = $this->loadMessages($category, $language);
        }
        if (isset(static::$_messages[$key][$message]) && static::$_messages[$key][$message] !== '') return true;
        else return false;
    }

    /** Get module by translation category */
    protected function getModuleByTransCategory($category)
    {
        foreach(TranslationsBuilder::$transCatToModule as $pattern => $module) {
            if (0 === strpos($category, rtrim($pattern, '*'))) {
                return $module;
            }
        }
    }

    /**
     * @inheritdoc
     * Find messages files in inherited (not container) modules.
     */
    protected function loadMessages($category, $language)
    {
        $key = $language . '/' . $category;
        if (!isset(static::$_messages[$key])) {
            $module = $this->getModuleByTransCategory($category);
            if (empty($module) || ! $module instanceof UniModule) {
                return parent::loadMessages($category, $language);
            } else {
                $langs = [$language, substr($language, 0, 2), substr($this->sourceLanguage, 0, 2)]; // first lang has higher priority
                $messageBaseFilename = basename($this->getMessageFilePath($category, $language));
                $pathList = $module->getBasePathList();
                $messagesFallback = [];
                foreach ($langs as $lang) {
                    $messagesFallback[$lang] = [];
                    foreach ($pathList as $path) {
                        $filename = sprintf("%s/%s/%s/%s", $path, UniModule::$messagesSubdir, $lang, $messageBaseFilename);
                        if (is_file($filename)) {
                            $moreMessages = $this->loadMessagesFromFile($filename);
                            $messagesFallback[$lang] = ArrayHelper::merge($moreMessages, $messagesFallback[$lang]); // first in inheritance path has higher priority
                        }
                    }
                }
                foreach ($langs as $lang) { // first lang has higher priority
                    if (!empty($messagesFallback[$lang])) {
                        static::$_messages[$key] = $messagesFallback[$lang];
                        break;
                    }
                }
            }
        }
        if (isset(static::$_messages[$key])) {
            return static::$_messages[$key];
        } else {
            return [];
        }
    }

}
