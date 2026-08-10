<?php

declare(strict_types=1);

namespace app\models\api\debate;

use app\components\UrlHelper;
use app\models\db\{Amendment, ConsultationAgendaItem, DebateItem as DebateItemEntity, Motion};

class DebateItem
{
    public function __construct(
        public int $id,
        public DebateItemTargetType $targetType,
        public string $title,
        public string $dateStarted,
        public ?int $targetId = null,
        public ?string $titleWithPrefix = null,
        public ?string $initiatorsHtml = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
        public ?int $speechQueueId = null,
        public ?int $votingBlockId = null,
    ) {
    }

    public static function fromEntity(DebateItemEntity $entity): self
    {
        $target = $entity->getDebateTarget();
        $targetId = $target?->id;

        if ($entity->freeText !== null) {
            $targetType = DebateItemTargetType::FREE_TEXT;
            $title = $entity->freeText;
            $titleWithPrefix = null;
            $initiatorsHtml = null;
            $urlJson = null;
            $urlHtml = null;
            $targetId = null;
        } elseif (is_a($target, Motion::class)) {
            $targetType = DebateItemTargetType::MOTION;
            $title = $target->title;
            $titleWithPrefix = $target->getTitleWithPrefix();
            $initiatorsHtml = $target->getInitiatorsStr();
            $urlJson = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($target, 'rest'));
            $urlHtml = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($target));
        } elseif (is_a($target, Amendment::class)) {
            $targetType = DebateItemTargetType::AMENDMENT;
            $title = $target->getTitle();
            $titleWithPrefix = $target->getTitleWithPrefix();
            $initiatorsHtml = $target->getInitiatorsStr();
            $urlJson = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($target, 'rest'));
            $urlHtml = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($target));
        } elseif (is_a($target, ConsultationAgendaItem::class)) {
            $targetType = DebateItemTargetType::AGENDA_ITEM;
            $title = $target->title;
            $code = $target->getShownCode(true);
            $titleWithPrefix = ($code !== '' ? $code . ' ' . $target->title : null);
            $initiatorsHtml = null;
            $urlJson = null;
            $urlHtml = null;
        } else {
            throw new \RuntimeException('debateItem ' . $entity->id . ' has no valid debate target');
        }

        return new self(
            id: $entity->id,
            targetType: $targetType,
            targetId: $targetId,
            title: $title,
            dateStarted: (new \DateTime($entity->dateStarted))->format('c'),
            titleWithPrefix: $titleWithPrefix,
            initiatorsHtml: $initiatorsHtml,
            urlJson: $urlJson,
            urlHtml: $urlHtml,
            speechQueueId: self::getSpeechQueueId($entity),
            votingBlockId: self::getVotingBlockId($entity),
        );
    }

    private static function getVotingBlockId(DebateItemEntity $entity): ?int
    {
        // Explicit assignment wins; otherwise, for a debated motion/amendment that is a voting item,
        // fall back to the block it belongs to. Agenda items only ever have an explicit assignment.
        if ($entity->votingBlockId !== null) {
            return $entity->votingBlockId;
        }
        $target = $entity->getDebateTarget();
        if (($target instanceof Motion || $target instanceof Amendment) && $target->votingBlockId !== null) {
            return $target->votingBlockId;
        }

        return null;
    }

    private static function getSpeechQueueId(DebateItemEntity $entity): ?int
    {
        if ($entity->motionId === null && $entity->amendmentId === null && $entity->agendaItemId === null) {
            if ($entity->freeText !== null) {
                // Free-text debates use the generic fallback speaking list (not assigned to any item)
                foreach ($entity->getMyConsultation()->speechQueues as $queue) {
                    if ($queue->motionId === null && $queue->amendmentId === null && $queue->agendaItemId === null) {
                        return $queue->id;
                    }
                }
            }
            return null;
        }
        foreach ($entity->getMyConsultation()->speechQueues as $queue) {
            if ($entity->motionId !== null && $queue->motionId === $entity->motionId) {
                return $queue->id;
            }
            if ($entity->amendmentId !== null && $queue->amendmentId === $entity->amendmentId) {
                return $queue->id;
            }
            if ($entity->agendaItemId !== null && $queue->agendaItemId === $entity->agendaItemId) {
                return $queue->id;
            }
        }

        return null;
    }
}
