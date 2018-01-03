<?php

namespace asb\yii2\common_2_170212\i18n;

use Sys;

use Yii;
use yii\i18n\MissingTranslationEvent;
use yii\base\Object;

/**
 * Missing translation event handler.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class TranslationEventHandler extends Object
{
    public static function handleMissingTranslation(MissingTranslationEvent $event)
    {
        $msg = '!!! MISSING TRANSLATION'
        . " FOR '{$event->message}'"
        . " IN CATEGORY '{$event->category}'"
        . " FOR LANGUAGE {$event->language}"
        . "<br />\n"
        ;
        //$event->translatedMessage = $msg;
        Yii::info($msg, 'log-translate');
        //Sys::log($msg, 'log-translate');
    }
}
