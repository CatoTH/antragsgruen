<?php

namespace app\models\mergeAmendments;

use app\models\db\Motion;

class DraftParagraph
{
    /** @var int[] */
    public array $amendmentToggles;

    /** @var string[] */

    public array $textVersions;
    public string $text;
    public ?string $unchanged;

    /** @var int[] */
    public array $handledCollisions;

    public static function fromJson(array $json): DraftParagraph
    {
        $para                    = new DraftParagraph();
        $para->amendmentToggles  = $json['amendmentToggles'];
        $para->textVersions      = $json['textVersions'] ?? [];
        $para->text              = $json['text'];
        $para->unchanged         = $json['unchanged'];
        $para->handledCollisions = $json['handledCollisions'] ?? [];

        return $para;
    }

    /**
     * @return DraftParagraph[]
     */
    public static function fromJsonArr(array $arr): array
    {
        return array_map(function ($entry) {
            return static::fromJson($entry);
        }, $arr);
    }

    /**
     * @return int[]
     */
    public function getActiveResolvedAmendmentIds(Motion $motion): array
    {
        $resolvedIds = [];
        foreach ($this->amendmentToggles as $amendmentId) {
            $resolved = false;
            if (isset($this->textVersions[$amendmentId]) && $this->textVersions[$amendmentId] === Init::TEXT_VERSION_PROPOSAL) {
                foreach ($motion->amendments as $amendment) {
                    if ($amendment->id === $amendmentId && $amendment->getMyProposalReference()) {
                        $resolvedIds[] = $amendment->getMyProposalReference()->id;
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
