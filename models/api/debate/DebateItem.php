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
        public int $targetId,
        public string $title,
        public string $dateStarted,
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

        if (is_a($target, Motion::class)) {
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
            targetId: $target->id,
            title: $title,
            dateStarted: (new \DateTime($entity->dateStarted))->format('c'),
            titleWithPrefix: $titleWithPrefix,
            initiatorsHtml: $initiatorsHtml,
            urlJson: $urlJson,
            urlHtml: $urlHtml,
            speechQueueId: self::getSpeechQueueId($entity),
            votingBlockId: $entity->votingBlockId,
        );
    }

    private static function getSpeechQueueId(DebateItemEntity $entity): ?int
    {
        // Speech queues cannot be attached to amendments yet
        if ($entity->motionId === null && $entity->agendaItemId === null) {
            return null;
        }
        foreach ($entity->getMyConsultation()->speechQueues as $queue) {
            if ($entity->motionId !== null && $queue->motionId === $entity->motionId) {
                return $queue->id;
            }
            if ($entity->agendaItemId !== null && $queue->agendaItemId === $entity->agendaItemId) {
                return $queue->id;
            }
        }

        return null;
    }
}
