<?php

namespace asb\yii2\common_2_170212\base;

use Yii;

/**
 * United module.
 *
 * @author ASB <ab2014box@gmail.com>
 */
class UniModule extends UniBaseModule
{
    static $tc = 'app/sys/module';

    /** Get some module info
     *  @param string $cmd
     *  @param array $params
     *  @return mix|false|null
     */
    public function inform($cmd, $params = [])
    {//echo __METHOD__."($cmd)<br>";var_dump($params);
        switch ($cmd) {
          case 'label': // module's short name for menu
            if (!empty($this->params['label'])) {
                return $this->params['label'];
            } else {
                //return false;
                return Yii::t(static::$tc, 'Module') . ' ' . $this->uniqueId;
            }
            break;
          case 'sitetree-params-action': // return action in route format
            //if (empty($params['module_full_id'])) return false;
            if (!empty($this->params['sitetree-params-action'])) return $this->params['sitetree-params-action'];
            break;
//todo
        }
        return null;
    }

}
