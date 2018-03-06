<?php

namespace asb\yii2\common_2_170212\base;

use asb\yii2\common_2_170212\behaviors\ParamsAccessBehaviour;
use asb\yii2\common_2_170212\i18n\TranslationsBuilder;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * United module.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class UniModule extends UniBaseModule
{
    public static $tc = 'common'; // default, can change in base\BaseModule::bootstrap()

    /**
     * Add to modules' behaviors from its' $params['behaviors']
     * and default 'params-access' behavior.
     * @return array the behavior configurations.
     */
    public function behaviors()
    {
        $behaviors = ArrayHelper::merge(parent::behaviors(), [
            'params-access' => [
                'class' => ParamsAccessBehaviour::className(),
                'defaultRole' => 'roleRoot',
                'readonlyParams' => [
                    'behaviors', // parameters which nobody can edit, will overwrite another rules
                ],
            ],
        ]);
        if (isset($this->params['behaviors'])) {
            $behaviors = ArrayHelper::merge($behaviors, $this->params['behaviors']);
        }
        return $behaviors;
    }

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
                $label = $this->params['label'];
                // try to translate label
                $tcCat = TranslationsBuilder::getBaseTransCategory($this);
                $tc = "{$tcCat}/module";
                if (!empty(Yii::$app->i18n->translations["{$tcCat}*"])) {
                    $label = Yii::t($tc, $this->params['label']);
                }
                return $label;
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
