<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, DebateItem, Motion, SpeechQueue, VotingBlock, VotingQuestion};
use app\models\api\debate\DebateState;
use app\models\api\SpeechQueue as SpeechQueueApi;
use app\models\majorityType\IMajorityType;
use app\models\quorumType\IQuorumType;
use app\models\votings\AnswerTemplates;

/**
 * Domain logic of the "Currently debated" module. All changes to the debate state go through this class,
 * which enforces the invariant that at most one debateItem per consultation is open (dateStopped IS NULL).
 */
class DebateTools
{
    /**
     * Makes the given motion, amendment, or agenda item the consultation's currently debated item,
     * ending a debate over another item if one is going on.
     * If the given item is already being debated, the open debate is returned unchanged
     * (no new history entry is created).
     */
    public static function startDebate(Consultation $consultation, Motion|Amendment|ConsultationAgendaItem $target): DebateItem
    {
        $current = DebateItem::getCurrentForConsultation($consultation);
        if ($current && self::isDebateOver($current, $target)) {
            return $current;
        }

        $transaction = DebateItem::getDb()->beginTransaction();
        try {
            self::endDebate($consultation, skipLiveUpdate: true);

            $debate = new DebateItem();
            $debate->consultationId = $consultation->id;
            $debate->motionId = (is_a($target, Motion::class) ? $target->id : null);
            $debate->amendmentId = (is_a($target, Amendment::class) ? $target->id : null);
            $debate->agendaItemId = (is_a($target, ConsultationAgendaItem::class) ? $target->id : null);
            $debate->dateStarted = date('Y-m-d H:i:s');
            if (!$debate->save()) {
                throw new \RuntimeException('Could not save the debate item: ' . print_r($debate->getErrors(), true));
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        LiveTools::sendDebate($consultation, DebateState::fromConsultation($consultation));

        return $debate;
    }

    /**
     * Ends the ongoing debate, if there is one.
     */
    public static function endDebate(Consultation $consultation, bool $skipLiveUpdate = false): void
    {
        /** @var DebateItem[] $openDebates */
        $openDebates = DebateItem::find()
            ->where(['consultationId' => $consultation->id, 'dateStopped' => null])
            ->all();
        foreach ($openDebates as $openDebate) {
            $openDebate->dateStopped = date('Y-m-d H:i:s');
            if (!$openDebate->save()) {
                throw new \RuntimeException('Could not end the debate: ' . print_r($openDebate->getErrors(), true));
            }
        }

        if (!$skipLiveUpdate) {
            LiveTools::sendDebate($consultation, DebateState::fromConsultation($consultation));
        }
    }

    /**
     * Finds the speech queue attached to the debated motion or agenda item, creating a fresh, inactive one
     * (with the consultation's configured subqueues) if none exists yet. The admin activates it afterwards
     * through the regular speech-admin endpoints. Amendments cannot carry a speech queue yet.
     */
    public static function getOrCreateSpeechQueue(DebateItem $debate): SpeechQueue
    {
        $consultation = $debate->getMyConsultation();

        if ($debate->motionId === null && $debate->agendaItemId === null) {
            throw new \RuntimeException('A speech queue can only be attached to a debated motion or agenda item');
        }

        foreach ($consultation->speechQueues as $queue) {
            if ($debate->motionId !== null && $queue->motionId === $debate->motionId) {
                return $queue;
            }
            if ($debate->agendaItemId !== null && $queue->agendaItemId === $debate->agendaItemId) {
                return $queue;
            }
        }

        $queue = SpeechQueue::createWithSubqueues($consultation, false);
        $queue->motionId = $debate->motionId;
        $queue->agendaItemId = $debate->agendaItemId;
        if (!$queue->save()) {
            throw new \RuntimeException('Could not attach the speech queue to the debated item: ' . print_r($queue->getErrors(), true));
        }
        $consultation->refresh();

        LiveTools::sendDebate($consultation, DebateState::fromConsultation($consultation));
        LiveTools::sendSpeechQueue($consultation, SpeechQueueApi::fromEntity($queue));

        return $queue;
    }

    /**
     * Resolves the voting block for a debated item: the one explicitly assigned to the debate, or - for a
     * debated motion/amendment that is itself a voting item - the block it belongs to. Agenda items only
     * ever have an explicitly assigned voting.
     */
    public static function getVotingBlockForDebate(DebateItem $debate): ?VotingBlock
    {
        $consultation = $debate->getMyConsultation();

        if ($debate->votingBlockId) {
            return $consultation->getVotingBlock($debate->votingBlockId);
        }

        $target = $debate->getDebateTarget();
        if (($target instanceof Motion || $target instanceof Amendment) && $target->votingBlockId) {
            return $consultation->getVotingBlock($target->votingBlockId);
        }

        return null;
    }

    /**
     * Explicitly assigns an existing voting block as the voting for the debated item.
     */
    public static function assignVotingBlock(DebateItem $debate, VotingBlock $votingBlock): void
    {
        $debate->votingBlockId = $votingBlock->id;
        if (!$debate->save()) {
            throw new \RuntimeException('Could not assign the voting block to the debate: ' . print_r($debate->getErrors(), true));
        }

        $consultation = $debate->getMyConsultation();
        LiveTools::sendDebate($consultation, DebateState::fromConsultation($consultation));
    }

    /**
     * Clears the voting explicitly assigned to the debated item. Does not delete the voting block itself,
     * nor does it detach a motion/amendment from a block it is a voting item of.
     */
    public static function unassignVotingBlock(DebateItem $debate): void
    {
        $debate->votingBlockId = null;
        if (!$debate->save()) {
            throw new \RuntimeException('Could not unassign the voting block from the debate: ' . print_r($debate->getErrors(), true));
        }

        $consultation = $debate->getMyConsultation();
        LiveTools::sendDebate($consultation, DebateState::fromConsultation($consultation));
    }

    /**
     * Creates a fresh voting block (in preparing state) and assigns it to the debated item. For a debated
     * motion/amendment the item itself becomes the single voting item; for an agenda item, the given question
     * is used as the voting title and question. The admin opens it afterwards through the regular voting admin.
     */
    public static function createVotingForDebate(DebateItem $debate, ?string $question): VotingBlock
    {
        $consultation = $debate->getMyConsultation();
        $target = $debate->getDebateTarget();
        if ($target === null) {
            throw new \RuntimeException('Cannot create a voting for a debate without a target');
        }

        $transaction = VotingBlock::getDb()->beginTransaction();
        try {
            $block = new VotingBlock();
            $block->consultationId = $consultation->id;
            $block->position = VotingBlock::getNextAvailablePosition($consultation);
            $block->votesPublic = VotingBlock::VOTES_PUBLIC_NO;
            $block->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
            $block->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
            $block->quorumType = IQuorumType::QUORUM_TYPE_NONE;
            $block->votingStatus = VotingBlock::STATUS_PREPARING;
            $block->setAnswerTemplate(AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION);

            if ($target instanceof Motion || $target instanceof Amendment) {
                $block->setTitle($target->getTitleWithPrefix());
                if (!$block->save()) {
                    throw new \RuntimeException('Could not create the voting block: ' . print_r($block->getErrors(), true));
                }
                $target->addToVotingBlock($block, true);
            } else {
                $title = ($question !== null && trim($question) !== '' ? trim($question) : '-');
                $block->setTitle($title);
                if (!$block->save()) {
                    throw new \RuntimeException('Could not create the voting block: ' . print_r($block->getErrors(), true));
                }
                $votingQuestion = new VotingQuestion();
                $votingQuestion->consultationId = $consultation->id;
                $votingQuestion->title = $title;
                $votingQuestion->votingBlockId = $block->id;
                if (!$votingQuestion->save()) {
                    throw new \RuntimeException('Could not create the voting question: ' . print_r($votingQuestion->getErrors(), true));
                }
            }

            $debate->votingBlockId = $block->id;
            if (!$debate->save()) {
                throw new \RuntimeException('Could not assign the created voting to the debate: ' . print_r($debate->getErrors(), true));
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $consultation->refresh();
        LiveTools::sendDebate($consultation, DebateState::fromConsultation($consultation));

        return $block;
    }

    private static function isDebateOver(DebateItem $debate, Motion|Amendment|ConsultationAgendaItem $target): bool
    {
        if (is_a($target, Motion::class)) {
            return $debate->motionId === $target->id;
        } elseif (is_a($target, Amendment::class)) {
            return $debate->amendmentId === $target->id;
        } else {
            return $debate->agendaItemId === $target->id;
        }
    }
}
