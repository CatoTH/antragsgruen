<?php

declare(strict_types=1);

namespace app\models\api;

use app\models\db\ConsultationAgendaItem;
use Symfony\Component\Serializer\Annotation\SerializedName;

class AgendaItemSettings
{
    /** @SerializedName("has_speaking_list") - only used for saving */
    public bool $hasSpeakingList;

    /**
     * @var int[]
     * @SerializedName("speaking_lists") - used to create links to lists
     */
    public array $speakingLists;

    /** @SerializedName("in_proposed_procedures") */
    public bool $inProposedProcedures;

    /**
     * @var int[]
     * @SerializedName("motion_types")
     */
    public array $motionTypes;

    public static function fromEntity(ConsultationAgendaItem $entity): self
    {
        $apiItem = new self();
        $apiItem->hasSpeakingList = count($entity->speechQueues) > 0;
        $apiItem->speakingLists = array_map(fn(\app\models\db\SpeechQueue $queue) => $queue->id, $entity->speechQueues);
        $apiItem->inProposedProcedures = $entity->getSettingsObj()->inProposedProcedures;

        if ($entity->motionTypeId) {
            $apiItem->motionTypes = [intval($entity->motionTypeId)];
        } else{
            $apiItem->motionTypes = [];
        }

        return $apiItem;
    }
}
