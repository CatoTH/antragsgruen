<?php

declare(strict_types=1);

namespace app\models\api;

use app\models\db\ConsultationAgendaItem;

class AgendaItemSettings
{
    public bool $hasSpeakingList;
    public bool $inProposedProcedures;

    /** @var int[] */
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
