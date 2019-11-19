<?php

namespace app\models\mergeAmendments;

use app\models\db\Motion;

class DraftParagraph
{
    /** @var int[] */
    public $amendmentToggles;

    /** @var string[] */
    public $textVersions;

    /** @var string */
    public $text;

    /** @var string */
    public $unchanged;

    /** @var int[] */
    public $handledCollisions;

    public static function fromJson(array $json): DraftParagraph
    {
        $para                    = new DraftParagraph();
        $para->amendmentToggles  = $json['amendmentToggles'];
        $para->textVersions      = (isset($json['textVersions']) ? $json['textVersions'] : []);
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
    public static function fromJsonArr(array $arr): array
    {
        return array_map(function ($entry) {
            return static::fromJson($entry);
        }, $arr);
    }

    /**
     * @param Motion $motion
     *
     * @return int[]
     */
    public function getActiveResolvedAmendmentIds(Motion $motion): array
    {
        $resolvedIds = [];
        foreach ($this->amendmentToggles as $amendmentId) {
            $resolved = false;
            if (isset($this->textVersions[$amendmentId]) && $this->textVersions[$amendmentId] === Init::TEXT_VERSION_PROPOSAL) {
                foreach ($motion->amendments as $amendment) {
                    if ($amendment->id === $amendmentId && $amendment->proposalReference) {
                        $resolvedIds[] = $amendment->proposalReference->id;
                        $resolved = true;
                    }
                }
            }
            if (!$resolved) {
                $resolvedIds[] = $amendmentId;
            }
        }
        return $resolvedIds;
    }
}
