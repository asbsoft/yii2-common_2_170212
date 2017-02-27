<?php

namespace asb\yii2\common_2_170212\helpers;

use yii\helpers\ArrayHelper as BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
    /**
     * Merges two or more arrays into one recursively.
     * @see parent::merge()
     *
     * For integer-keyed elements, the elements from the latter array
     * will not be appended to the former array if same value already exists.
     */
    public static function mergeNoDouble($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if ($v instanceof UnsetArrayValue) {
                    unset($res[$k]);
                } elseif ($v instanceof ReplaceArrayValue) {
                    $res[$k] = $v->value;
                } elseif (is_int($k)) {
                    if (isset($res[$k])) {
                        //$res[] = $v; // in origin version

                        $exkey = array_search($next[$k], $res);
                        if ($exkey === false) {
                            $res[] = $v;
                        } // else: no duplicate
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::mergeNoDouble($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

}
