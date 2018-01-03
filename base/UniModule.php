<?php

namespace asb\yii2\common_2_170212\base;

use Yii;
use yii\base\InvalidConfigException;

/**
 * United module.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class UniModule extends UniBaseModule
{
    public static $tc = 'common'; // default, can change in base\BaseModule::bootstrap()

    /** Get some module info
     *  @param string $cmd
     *  @param array $params
     *  @return mix|false|null
     */
    public function inform($cmd, $params = [])
    {
        switch ($cmd) {
          case 'label': // module's short name for menu
            if (!empty($this->params['label'])) {
                return $this->params['label'];
            } else {
                //return false;
                try {
                    $ms = Yii::$app->i18n->getMessageSource(static::$tc);
                } catch(InvalidConfigException $ex) {
                    $ms = false;
                }
                return ($ms ? Yii::t(static::$tc, 'Module') : 'Module') . ' ' . $this->uniqueId;
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
