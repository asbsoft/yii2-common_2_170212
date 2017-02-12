<?php

namespace asb\yii2\common_2_170212\web;

use asb\yii2\common_2_170212\web\CmsUrlRule;

/**
 * @author ASB <ab2014box@gmail.com>
 */
class RedirectUrlRule extends CmsUrlRule
{
    public $link;

    public function parseRequest($manager, $request)
    {
        $result = parent::parseRequest($manager, $request);
        if (is_array($result)) {
            list($route, $params) = $result;
            $params['link'] = $this->link;
            $result = [$route, $params];
        }
        return $result;
    }
}