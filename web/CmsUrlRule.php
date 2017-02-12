<?php

namespace asb\yii2\common_2_170212\web;

use yii\web\UrlRule;

/**
 * @author ASB <ab2014box@gmail.com>
 */
class CmsUrlRule extends UrlRule
{
    public $layout;

    public function parseRequest($manager, $request)
    {
        $result = parent::parseRequest($manager, $request);
        if (is_array($result)) {
            list($route, $params) = $result;
            $params['layout'] = $this->layout;
            $result = [$route, $params];
        }
        return $result;
    }
}