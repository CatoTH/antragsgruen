<?php

namespace app\models\sectionTypes;

use app\components\Tools;
use app\models\exceptions\Internal;
use yii\helpers\Html;

class TabularDataType implements \JsonSerializable
{
    public const TYPE_STRING  = 1;
    public const TYPE_INTEGER = 2;
    public const TYPE_DATE    = 3;

    public $rowId;
    public $title;
    public $type;

    /**
     * @return string[]
     */
    public static function getDataTypes(): array
    {
        return [
            static::TYPE_STRING  => \Yii::t('admin', 'tabulardatatype_string'),
            static::TYPE_INTEGER => \Yii::t('admin', 'tabulardatatype_integer'),
            static::TYPE_DATE    => \Yii::t('admin', 'tabulardatatype_date'),
        ];
    }

    /**
     * @param array{rowId: string, title: string, type: int} $arr
     */
    public function __construct(array $arr)
    {
        $this->rowId = $arr['rowId'];
        $this->title = $arr['title'];
        $this->type  = $arr['type'];
    }

    public function jsonSerialize(): array
    {
        return [
            'rowId' => $this->rowId,
            'title' => $this->title,
            'type'  => $this->type
        ];
    }

    public function getFormField(string $nameId, string $value, bool $required): string
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
     * @return int|string
     * @throws Internal
     */
    public function parseFormInput(string $value)
    {
        switch ($this->type) {
            case TabularDataType::TYPE_STRING:
                return $value;
            case TabularDataType::TYPE_INTEGER:
                return intval($value);
            case TabularDataType::TYPE_DATE:
                return Tools::dateBootstrapdate2sql($value);
        }
        throw new Internal('Unsupported data type');
    }

    public function formatRow(string $value): string
    {
        switch ($this->type) {
            case TabularDataType::TYPE_STRING:
            case TabularDataType::TYPE_INTEGER:
                return $value;
            case TabularDataType::TYPE_DATE:
                return Tools::formatMysqlDate($value);
        }
        throw new Internal('Unsupported data type');
    }
}
