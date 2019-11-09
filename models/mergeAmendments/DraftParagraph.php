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

    /** @var int[] */
    public $handledCollisions;

    public static function fromJson($json): DraftParagraph
    {
        $para                    = new DraftParagraph();
        $para->amendmentToggles  = $json['amendmentToggles'];
        $para->text              = $json['text'];
        $para->unchanged         = $json['unchanged'];
        $para->handledCollisions = (isset($json['handledCollisions']) ? $json['handledCollisions'] : []);

        return $para;
    }

    /**
     * @param array $arr
     *
     * @return DraftParagraph[]
     */
    public static function fromJsonArr($arr)
    {
        return array_map(function ($entry) {
            return static::fromJson($entry);
        }, $arr);
    }
}
