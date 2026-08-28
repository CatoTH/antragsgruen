<?php

declare(strict_types=1);

namespace app\models\api\debate;

use app\components\UrlHelper;
use app\models\api\LocalizedString;
use app\models\db\{Amendment, ConsultationAgendaItem, DebateItem as DebateItemEntity, Motion, SpeechQueue, VotingBlock};

class DebateItem
{
    public function __construct(
        public int $id,
        public DebateItemTargetType $targetType,
        public \app\models\api\LocalizedString $title,
        public string $dateStarted,
        public ?int $targetId = null,
        public ?\app\models\api\LocalizedString $titleWithPrefix = null,
        public ?\app\models\api\LocalizedString $initiatorsHtml = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
        public ?DebateItemSpeechQueue $speechQueue = null,
        public ?DebateItemVotingBlock $votingBlock = null,
    ) {
    }

    /**
     * Hint: every string that depends on the language the reader is browsing the site in has to be a
     * LocalizedString here, as this payload is not only returned to the requesting user, but also
     * pushed to all readers of the consultation at once - in whatever language each of them is using
     * (see LocalizedString).
     */
    public static function fromEntity(DebateItemEntity $entity): self
    {
        $consultation = $entity->getMyConsultation();
        $target = $entity->getDebateTarget();
        $targetId = $target?->id;

        if ($entity->freeText !== null) {
            $targetType = DebateItemTargetType::FREE_TEXT;
            $title = LocalizedString::fromString($consultation, $entity->freeText);
            $titleWithPrefix = null;
            $initiatorsHtml = null;
            $urlJson = null;
            $urlHtml = null;
            $targetId = null;
        } elseif (is_a($target, Motion::class)) {
            $motion = $target;
            $targetType = DebateItemTargetType::MOTION;
            $title = LocalizedString::build($consultation, fn () => $motion->getTitleForDisplay());
            $titleWithPrefix = LocalizedString::build($consultation, fn () => $motion->getTitleWithPrefixForDisplay());
            $initiatorsHtml = self::buildInitiators($motion);
            $urlJson = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($target, 'rest'));
            $urlHtml = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($target));
        } elseif (is_a($target, Amendment::class)) {
            $amendment = $target;
            $targetType = DebateItemTargetType::AMENDMENT;
            // Hint: an amendment's title is derived from its motion's canonical title; only the
            // wording around it ("Amendment to ...") depends on the reader's language.
            $title = LocalizedString::build($consultation, fn () => $amendment->getTitle());
            $titleWithPrefix = LocalizedString::build($consultation, fn () => $amendment->getTitleWithPrefix());
            $initiatorsHtml = self::buildInitiators($amendment);
            $urlJson = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($target, 'rest'));
            $urlHtml = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($target));
        } elseif (is_a($target, ConsultationAgendaItem::class)) {
            $agendaItem = $target;
            $targetType = DebateItemTargetType::AGENDA_ITEM;
            $title = LocalizedString::fromString($consultation, $agendaItem->title);
            $code = $agendaItem->getShownCode(true);
            $titleWithPrefix = ($code !== '' ? LocalizedString::fromString($consultation, $code . ' ' . $agendaItem->title) : null);
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
            speechQueue: self::buildSpeechQueue($entity),
            votingBlock: self::buildVotingBlock($entity),
        );
    }

    /**
     * Hint: IMotion::getInitiatorsStr() memoizes its result without keying it by language, so it
     * must not be used here - the second language rendered would get the first one's string.
     */
    private static function buildInitiators(Motion|Amendment $imotion): LocalizedString
    {
        return LocalizedString::build(
            $imotion->getMyConsultation(),
            fn () => $imotion->getInitiatorsStrFromArray($imotion->getInitiators())
        );
    }

    private static function buildVotingBlock(DebateItemEntity $entity): ?DebateItemVotingBlock
    {
        $block = self::resolveVotingBlock($entity);
        if ($block === null) {
            return null;
        }

        return new DebateItemVotingBlock(
            id: $block->id,
            status: $block->votingStatus,
            title: $block->title,
        );
    }

    private static function resolveVotingBlock(DebateItemEntity $entity): ?VotingBlock
    {
        // Explicit assignment wins; otherwise, for a debated motion/amendment that is a voting item,
        // fall back to the block it belongs to. Agenda items only ever have an explicit assignment.
        $consultation = $entity->getMyConsultation();
        if ($entity->votingBlockId !== null) {
            return $consultation->getVotingBlock($entity->votingBlockId);
        }
        $target = $entity->getDebateTarget();
        if (($target instanceof Motion || $target instanceof Amendment) && $target->votingBlockId !== null) {
            return $consultation->getVotingBlock($target->votingBlockId);
        }

        return null;
    }

    private static function buildSpeechQueue(DebateItemEntity $entity): ?DebateItemSpeechQueue
    {
        $queue = self::resolveSpeechQueue($entity);
        if ($queue === null) {
            return null;
        }

        return new DebateItemSpeechQueue(
            id: $queue->id,
            isActive: (bool)$queue->isActive,
            title: LocalizedString::build($entity->getMyConsultation(), fn () => $queue->getTitle()),
        );
    }

    private static function resolveSpeechQueue(DebateItemEntity $entity): ?SpeechQueue
    {
        if ($entity->motionId === null && $entity->amendmentId === null && $entity->agendaItemId === null) {
            if ($entity->freeText !== null) {
                // Free-text debates use the generic fallback speaking list (not assigned to any item)
                foreach ($entity->getMyConsultation()->speechQueues as $queue) {
                    if ($queue->motionId === null && $queue->amendmentId === null && $queue->agendaItemId === null) {
                        return $queue;
                    }
                }
            }
            return null;
        }
        foreach ($entity->getMyConsultation()->speechQueues as $queue) {
            if ($entity->motionId !== null && $queue->motionId === $entity->motionId) {
                return $queue;
            }
            if ($entity->amendmentId !== null && $queue->amendmentId === $entity->amendmentId) {
                return $queue;
            }
            if ($entity->agendaItemId !== null && $queue->agendaItemId === $entity->agendaItemId) {
                return $queue;
            }
        }

        return null;
    }
}
