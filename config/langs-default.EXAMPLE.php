<?php
/**
  Return default system languages if not exists special language module.
  @see asb\yii2\common_2_170212\i18n\LangHelper

  Default language will be active language ('is_visible' = true) with minimal 'prio'.
  Languages will order by 'prio' values.

  @author ASB <ab2014box@gmail.com>
*/
return [

    'en-US' => [
        'prio'          => 10,
        'is_visible'    => true,
      //'is_visible'    => false,

        'country_code'  => 'us',
        'code_full'     => 'en-US',
        'code2'         => 'en',
        'code3'         => 'eng',
        'name_en'       => 'English',
        'name_orig'     => 'English',
        'id'            => 1,
    ],

    'ru-RU' => [
        'prio'          => 30,
        'is_visible'    => true,
      //'is_visible'    => false,

        'country_code'  => 'ru',
        'code_full'     => 'ru-RU',
        'code2'         => 'ru',
        'code3'         => 'rus',
        'name_en'       => 'Russian',
        'name_orig'     => 'Русский',
        'id'            => 2,
    ],
];
