<?php

namespace asb\yii2\common_2_170212\widgets\grid;

use Yii;
use yii\helpers\Html;
use yii\grid\ActionColumn;

/**
 * Extension of ActionColumn for the [[GridView]] widget
 * that displays buttons in search cell instead of autosearch.
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ButtonedActionColumn extends ActionColumn
{
    /** @var boolean turn on/off autosearch */
    public $autosearch = false;

    public $tc = 'common';

    public $contentFilterCell ='<span style="white-space: nowrap;">{button-search}&nbsp;{button-clear}</span>';

    public $gridviewWidgetId;

    public $buttonSearch; // assign false to hide button
    public $buttonSearchText;
    public $buttonSearchId = 'search-button';

    public $buttonClear; // assign false to hide button
    public $buttonClearText = 'C';
    public $buttonClearId = 'search-clean';

    /** @var string JS init code */
    public $jsAutosearchOff = '';
    public $jsSearch = '';
    public $jsClear = '';

    public function init()
    {
        parent::init();

        if (empty($this->gridviewWidgetId)) {
            $this->gridviewWidgetId = $this->grid->id;
        }

        if (!$this->autosearch && empty($this->jsAutosearchOff)) {
            $this->jsAutosearchOff = "
                jQuery('#{$this->gridviewWidgetId}').bind('beforeFilter', function (event) { return false; });
            ";
        }
        
        if (!isset($this->buttonSearch)) {
            if (empty($this->buttonSearchText)) {
                $this->buttonSearchText = Yii::t($this->tc, 'Search');
            }
            $this->buttonSearch = Html::submitInput($this->buttonSearchText, [
                'id' => $this->buttonSearchId,
                'class' => 'btn',
                'title' => Yii::t($this->tc, 'Start searching'),
            ]);
        }

        if (!isset($this->buttonClear)) {
            $this->buttonClear = Html::buttonInput($this->buttonClearText, [
                'id' => $this->buttonClearId,
                'class' => 'btn btn-danger',
                'title' => Yii::t($this->tc, 'Clean search fields'),
            ]);
        }

        if (!empty($this->buttonSearch) && empty($this->jsSearch)) {
            $this->jsSearch = "
                jQuery('#{$this->buttonSearchId}').bind('click', function() {
                    jQuery('#{$this->gridviewWidgetId}').unbind('beforeFilter');
                    jQuery('#{$this->gridviewWidgetId}').yiiGridView('applyFilter');
                });
            ";
        }

        if (!empty($this->buttonClear) && empty($this->jsClear)) {
            $this->jsClear = "
                jQuery('#{$this->buttonClearId}').bind('click', function() {
                    jQuery('.form-control').val('');
                    jQuery('#{$this->gridviewWidgetId}').unbind('beforeFilter');
                    jQuery('#{$this->gridviewWidgetId}').yiiGridView('applyFilter');
                });
            ";
        }
    }

    protected function renderFilterCellContent()
    {
        if (empty($this->contentFilterCell)) {
            return $this->grid->emptyCell;
        } else {
            $this->grid->getView()->registerJs($this->jsAutosearchOff);
            $this->grid->getView()->registerJs($this->jsSearch);
            $this->grid->getView()->registerJs($this->jsClear);

            $cell = strtr($this->contentFilterCell, [
                '{button-search}' => $this->buttonSearch,
                '{button-clear}' => $this->buttonClear,
            ]);
            return $cell;
        }
    }
}
