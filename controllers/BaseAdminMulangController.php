<?php

namespace asb\yii2\common_2_170212\controllers;

use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;

/**
 * Base admin multilang controller.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class BaseAdminMulangController extends BaseAdminController
{
    /** Current language in format 'll-CC' */
    public $langCodeMain;

    /** Array with all active languages infos */
    public $languages;

    public function init()
    {
        parent::init();

        $this->langCodeMain = LangHelper::normalizeLangCode(Yii::$app->language);
        $this->languages = LangHelper::activeLanguages();
    }
}
