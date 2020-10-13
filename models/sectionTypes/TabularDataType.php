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

    public $rowId;
    public $title;
    public $type;

    /**
     * @return string[]
     */
    public static function getDataTypes()
    {
        return [
            static::TYPE_STRING  => \yii::t('admin', 'tabulardatatype_string'),
            static::TYPE_INTEGER => \yii::t('admin', 'tabulardatatype_integer'),
            static::TYPE_DATE    => \yii::t('admin', 'tabulardatatype_date'),
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
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'rowId' => $this->rowId,
            'title' => $this->title,
            'type'  => $this->type
        ];
    }

    /**
     * @param string $nameId
     * @param string $value
     * @param bool $required
     * @return string
     */
    public function getFormField($nameId, $value, $required)
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
                return $value;
            case TabularDataType::TYPE_INTEGER:
                return $value;
            case TabularDataType::TYPE_DATE:
                return Tools::formatMysqlDate($value);
        }
        throw new Internal('Unsupported data type');
    }
}
