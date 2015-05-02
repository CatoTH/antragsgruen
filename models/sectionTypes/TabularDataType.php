<?php

namespace app\models\sectionTypes;


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
            static::TYPE_STRING  => \yii::t('backend', 'tabulardatatype_string'),
            static::TYPE_INTEGER => \yii::t('backend', 'tabulardatatype_integer'),
            static::TYPE_DATE    => \yii::t('backend', 'tabulardatatype_date'),
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
}
