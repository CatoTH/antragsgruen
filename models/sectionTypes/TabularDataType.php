<?php

namespace app\models\sectionTypes;

use app\components\Tools;
use app\models\exceptions\Internal;
use yii\helpers\Html;

class TabularDataType implements \JsonSerializable
{
    const TYPE_STRING  = 1;
    const TYPE_INTEGER = 2;
    const TYPE_DATE    = 3;
    public const TYPE_SELECT  = 4;

    public $rowId;
    public $title;
    public $type;
    public array $options = [];

    /**
     * @return string[]
     */
    public static function getDataTypes(): array
    {
        return [
            static::TYPE_STRING  => \Yii::t('admin', 'tabulardatatype_string'),
            static::TYPE_INTEGER => \Yii::t('admin', 'tabulardatatype_integer'),
            static::TYPE_DATE    => \Yii::t('admin', 'tabulardatatype_date'),
            self::TYPE_SELECT    => \Yii::t('admin', 'tabulardatatype_select'),
        ];
    }

    /**
     * @param array $arr
     */
    public function __construct($arr)
    {
        $this->rowId = $arr['rowId'];
        $this->title = $arr['title'];
        $this->type  = $arr['type'];
        $this->options = $arr['options'] ?? [];
    }

    public function jsonSerialize(): array
    {
        return [
            'rowId' => $this->rowId,
            'title' => $this->title,
            'type'  => $this->type,
            'options' => $this->options,
        ];
    }

    /**
     * @param string $nameId
     * @param string $value
     * @param bool $required
     */
    public function getFormField($nameId, $value, $required): string
    {
        $str = '';
        switch ($this->type) {
            case TabularDataType::TYPE_STRING:
                $str .= '<input type="text" ' . $nameId . ' value="' . Html::encode($value) . '"';
                if ($required) {
                    $str .= ' required';
                }
                $str .= ' class="form-control">';
                break;
            case TabularDataType::TYPE_INTEGER:
                $str .= '<input type="number" ' . $nameId . ' value="' . Html::encode($value) . '"';
                if ($required) {
                    $str .= ' required';
                }
                $str .= ' class="form-control">';
                break;
            case TabularDataType::TYPE_SELECT:
                $str .= '<select ' . $nameId . ' ';
                if ($required) {
                    $str .= ' required';
                }
                $str .= ' class="stdDropdown"><option></option>';
                foreach ($this->options as $option) {
                    $str .= '<option value="' . Html::encode($option) . '"';
                    if ($value === $option) {
                        $str .= ' selected';
                    }
                    $str .= '>' . Html::encode($option) . '</option>';
                }
                $str .= '</select>';
                break;
            case TabularDataType::TYPE_DATE:
                $locale = Tools::getCurrentDateLocale();
                $date   = ($value ? Tools::dateSql2bootstrapdate($value, $locale) : '');
                $str .= '<div class="input-group date">
                        <input type="text" class="form-control" ' . $nameId . ' value="' . Html::encode($date) . '" ';
                if ($required) {
                    $str .= ' required';
                }
                $str .= 'data-locale="' . Html::encode($locale) . '">
                        <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
                      </div>';
                break;
        }
        return $str;
    }

    /**
     * @param string $value
     * @return int|string
     * @throws Internal
     */
    public function parseFormInput($value)
    {
        switch ($this->type) {
            case TabularDataType::TYPE_STRING:
                return $value;
            case TabularDataType::TYPE_INTEGER:
                return IntVal($value);
            case TabularDataType::TYPE_DATE:
                return Tools::dateBootstrapdate2sql($value);
            case TabularDataType::TYPE_SELECT:
                return $value;
        }
        throw new Internal('Unsupported data type');
    }

    /**
     * @param string $value
     * @return string
     * @throws Internal
     */
    public function formatRow($value)
    {
        switch ($this->type) {
            case TabularDataType::TYPE_STRING:
            case TabularDataType::TYPE_INTEGER:
            case TabularDataType::TYPE_SELECT:
                return $value;
            case TabularDataType::TYPE_DATE:
                return Tools::formatMysqlDate($value);
        }
        throw new Internal('Unsupported data type');
    }
}
