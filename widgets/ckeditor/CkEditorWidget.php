<?php

namespace asb\yii2\common_2_170212\widgets\ckeditor;

use mihaildev\ckeditor\CKEditor;
use mihaildev\elfinder\ElFinder;

use yii\helpers\ArrayHelper;

class CkEditorWidget extends CKEditor
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $toolbarGroups = [];
        foreach ($this->editorOptions['toolbarGroups'] as $group) {
            if ($group == '/') continue; // skip separators
            $toolbarGroups[] = $group;
        }
        $this->editorOptions['toolbarGroups'] = $toolbarGroups;
    }

    /**
     * @inheritdoc
     */
    public static function widget($config = [])
    {
        if (empty($config['managerOptions'])) { // don't use file manager
            $managerOptions = false;
        } else {
            $managerOptions = $config['managerOptions'];
        }
        unset($config['managerOptions']);

        $editorOptions = $config['editorOptions'];

        if ($managerOptions) { // use file manager

            $editorOptions = ArrayHelper::merge($editorOptions, [
                'startPath' => $managerOptions['rootPath'],
                'path' => $managerOptions['rootPath'],
            ]);
            $editorOptions = ElFinder::ckeditorOptions($managerOptions['controller'], $editorOptions);
        } else { // hide filemanager (button 'Image')
            $editorOptions['removeButtons'] = 'Image,ImageButton,Flash';
            switch ($config['editorOptions']['preset']) { // as in parent
                case 'basic':
                    $editorOptions['removeButtons'] .= ',Smiley,Subscript,Superscript,Table,HorizontalRule,SpecialChar,PageBreak,Iframe';
                    break;
                case 'standard':
                    $editorOptions['removeButtons'] .= ',Smiley';
                    break;
            }
        }
        $config['editorOptions'] = $editorOptions;

        return parent::widget($config);
    }

}
