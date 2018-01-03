<?php

namespace asb\yii2\common_2_170212\web;

use yii\web\UrlRuleInterface;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
interface UniUrlRuleInterface extends UrlRuleInterface
{
    /**
     * Show route info.
     * Use in RoutesInfo::showRoute().
     * @return string
     */
    public function showRouteInfo();

}
