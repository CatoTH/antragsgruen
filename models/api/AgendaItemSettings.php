<?php

declare(strict_types=1);

namespace app\models\api;

use app\models\db\ConsultationAgendaItem;
use Symfony\Component\Serializer\Annotation\SerializedName;

class AgendaItemSettings
{
    /** @SerializedName("has_speaking_list") */
    public bool $hasSpeakingList;

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
        $apiItem->inProposedProcedures = $entity->getSettingsObj()->inProposedProcedures;

        if ($entity->motionTypeId) {
            $apiItem->motionTypes = [intval($entity->motionTypeId)];
        } else{
            $apiItem->motionTypes = [];
        }

        return $apiItem;
    }
}
