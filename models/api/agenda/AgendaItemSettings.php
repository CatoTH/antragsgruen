<?php

declare(strict_types=1);

namespace app\models\api\agenda;

use app\models\db\ConsultationAgendaItem;

class AgendaItemSettings
{
    public function __construct(
        public bool $hasSpeakingList,
        public bool $inProposedProcedures,
        /** @var int[] */
        public array $motionTypes,
        /** @var int[]|null */
        public ?array $speakingLists = null,
    ) {
    }

    public static function fromEntity(ConsultationAgendaItem $entity): self
    {
        if ($entity->motionTypeId) {
            $motionTypes = [intval($entity->motionTypeId)];
        } else{
            $motionTypes = [];
        }

        return new self(
            hasSpeakingList: count($entity->speechQueues) > 0,
            speakingLists: array_map(fn(\app\models\db\SpeechQueue $queue) => $queue->id, $entity->speechQueues),
            inProposedProcedures: $entity->getSettingsObj()->inProposedProcedures,
            motionTypes: $motionTypes,
        );
    }
}
