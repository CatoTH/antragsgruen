<?php

declare(strict_types=1);

namespace app\models\api\debate;

use app\components\MotionSorter;
use app\models\db\{Consultation, ConsultationAgendaItem, Motion};

/**
 * Hand-written DTO (not part of docs/openapi.yaml): response of GET /rest/{site}/{con}/debate/selectable,
 * listing all items that a debate moderator can select as "currently debated".
 */
class DebateSelectables
{
    /**
     * @param DebateSelectableItem[] $motions
     * @param DebateSelectableItem[] $amendments
     * @param DebateSelectableItem[] $agendaItems
     */
    public function __construct(
        public array $motions,
        public array $amendments,
        public array $agendaItems,
    ) {
    }

    public static function fromConsultation(Consultation $consultation): self
    {
        $motionDtos = [];
        $amendmentDtos = [];

        $visibleMotions = array_filter($consultation->motions, fn (Motion $motion) => $motion->isVisible() && !$motion->isDeleted());
        /** @var Motion[] $sortedMotions */
        $sortedMotions = MotionSorter::getSortedIMotionsFlat($consultation, $visibleMotions);
        foreach ($sortedMotions as $motion) {
            $motionDtos[] = new DebateSelectableItem(
                targetType: DebateItemTargetType::MOTION,
                targetId: $motion->id,
                title: $motion->title,
                titleWithPrefix: $motion->getTitleWithPrefix(),
                initiatorsHtml: $motion->getInitiatorsStr(),
            );

            foreach ($motion->getVisibleAmendmentsSorted() as $amendment) {
                $amendmentDtos[] = new DebateSelectableItem(
                    targetType: DebateItemTargetType::AMENDMENT,
                    targetId: $amendment->id,
                    title: $amendment->getTitle(),
                    titleWithPrefix: $amendment->getTitleWithPrefix(),
                    initiatorsHtml: $amendment->getInitiatorsStr(),
                );
            }
        }

        $agendaItemDtos = [];
        foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $agendaItem) {
            $code = $agendaItem->getShownCode(true);
            $agendaItemDtos[] = new DebateSelectableItem(
                targetType: DebateItemTargetType::AGENDA_ITEM,
                targetId: $agendaItem->id,
                title: $agendaItem->title,
                titleWithPrefix: ($code !== '' ? $code . ' ' . $agendaItem->title : null),
            );
        }

        return new self(
            motions: $motionDtos,
            amendments: $amendmentDtos,
            agendaItems: $agendaItemDtos,
        );
    }
}
