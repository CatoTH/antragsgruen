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
            $proposal = null;
            foreach ($motion->amendments as $amendment) {
                if ($amendment->id === $amendmentId) {
                    $proposal = Init::resolveProposalToUse($amendment, $this->textVersions[$amendmentId] ?? null);
                }
            }
            if ($proposal) {
                $resolvedIds[] = $proposal->getMyProposalReference()->id;
            } else {
                $resolvedIds[] = $amendmentId;
            }
        }
        return $resolvedIds;
    }
}
