<?php

namespace app\models\sectionTypes;

use app\components\Tools;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\exceptions\Internal;
use yii\helpers\Html;

class TabularDataType implements \JsonSerializable
{
    public const TYPE_STRING  = 1;
    public const TYPE_INTEGER = 2;
    public const TYPE_DATE    = 3;
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
            self::TYPE_STRING  => \Yii::t('admin', 'tabulardatatype_string'),
            self::TYPE_INTEGER => \Yii::t('admin', 'tabulardatatype_integer'),
            self::TYPE_DATE    => \Yii::t('admin', 'tabulardatatype_date'),
            self::TYPE_SELECT    => \Yii::t('admin', 'tabulardatatype_select'),
        ];
    }

    /**
     * @param array{rowId: string, title: string, type: int, options?: string[]} $arr
     */
    public function __construct(array $arr)
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

    public function getFormField(string $nameId, string $value, int $required): string
    {
        $requiredHtml = match($required) {
            ConsultationSettingsMotionSection::REQUIRED_YES => ' required',
            ConsultationSettingsMotionSection::REQUIRED_ENCOURAGED => ' data-encouraged="true"',
            default => '',
        };

        $str = '';
        switch ($this->type) {
            case TabularDataType::TYPE_STRING:
                $str .= '<input type="text" ' . $nameId . ' value="' . Html::encode($value) . '"' . $requiredHtml . ' class="form-control">';
                break;
            case TabularDataType::TYPE_INTEGER:
                $str .= '<input type="number" ' . $nameId . ' value="' . Html::encode($value) . '"' . $requiredHtml . ' class="form-control">';
                break;
            case TabularDataType::TYPE_SELECT:
                $str .= '<select ' . $nameId . $requiredHtml . ' class="stdDropdown"><option></option>';
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
                        <input type="text" class="form-control" ' . $nameId . ' value="' . Html::encode($date) . '"';
                $str .= $requiredHtml . ' data-locale="' . Html::encode($locale) . '">
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
            case TabularDataType::TYPE_SELECT:
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
            case TabularDataType::TYPE_SELECT:
                return $value;
            case TabularDataType::TYPE_DATE:
                return Tools::formatMysqlDate($value);
        }
        throw new Internal('Unsupported data type');
    }
}
