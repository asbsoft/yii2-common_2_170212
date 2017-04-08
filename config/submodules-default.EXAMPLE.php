<?php

$adminPath = empty(Yii::$app->params['adminPath']) ? 'admin' : Yii::$app->params['adminPath'];

return [ // module_uid => [config]
    'news' => [
        'class' => 'asb\yii2\modules\news_1_160430\Module',
        //'layoutPath' => '@project/modules/sys/views/layouts',
        //'layoutPath' => '@asb/yii2/cms_3_170211/modules/sys/views/layouts',
        'routesConfig' => [ // type => prefix|array
            'main' => 'news',
          //'main' => false, // false if will attach module's frontend at admin-module 'sitetree' to proper place
            'admin' => $adminPath . '/news',
            'rest'  => [
                 'urlPrefix'  => 'newsapi',
                 'sublink' => 'rest-newsold',
            ],
        ],
    ],
];
