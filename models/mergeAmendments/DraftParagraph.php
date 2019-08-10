<?php

namespace app\models\mergeAmendments;

class DraftParagraph
{
    /** @var int[] */
    public $amendmentToggles;

    /** @var string */
    public $text;

    /** @var string */
    public $unchanged;

    public static function fromJson($json)
    {
        $para                   = new static();
        $para->amendmentToggles = $json['amendmentToggles'];
        $para->text             = $json['text'];
        $para->unchanged        = $json['unchanged'];

        return $para;
    }

    /**
     * @param array $arr
     *
     * @return DraftParagraph[]
     */
    public static function fromJsonArr($arr) {
        return array_map(function($entry) {
            return static::fromJson($entry);
        }, $arr);
    }
}
