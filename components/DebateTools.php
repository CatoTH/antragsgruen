<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, DebateItem, Motion, SpeechQueue, User, VotingBlock, VotingQuestion};
use app\models\api\debate\DebateState;
use app\models\api\SpeechQueue as SpeechQueueApi;
use app\models\api\SpeechUser;
use app\models\majorityType\IMajorityType;
use app\models\quorumType\IQuorumType;
use app\models\settings\Privileges;
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
     * Makes the given free text the consultation's currently debated item, ending any ongoing debate.
     * Free-text debates are not tied to any motion, amendment, or agenda item.
     */
    public static function startFreeTextDebate(Consultation $consultation, string $text): DebateItem
    {
        $transaction = DebateItem::getDb()->beginTransaction();
        try {
            self::endDebate($consultation, skipLiveUpdate: true);

            $debate = new DebateItem();
            $debate->consultationId = $consultation->id;
            $debate->motionId = null;
            $debate->amendmentId = null;
            $debate->agendaItemId = null;
            $debate->freeText = $text;
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
     * Finds the speech queue for the debated item, creating a fresh, inactive one (with the consultation's
     * configured subqueues) if none exists yet. Motions, amendments, and agenda items get their own queue;
     * free-text debates share the generic fallback queue (not assigned to any item). The admin activates it
     * afterwards through the regular speech-admin endpoints.
     */
    public static function getOrCreateSpeechQueue(DebateItem $debate): SpeechQueue
    {
        $consultation = $debate->getMyConsultation();

        $isFreeText = ($debate->motionId === null && $debate->amendmentId === null && $debate->agendaItemId === null && $debate->freeText !== null);

        if ($debate->motionId === null && $debate->amendmentId === null && $debate->agendaItemId === null && !$isFreeText) {
            throw new \RuntimeException('A speech queue can only be attached to a debated motion, amendment, agenda item, or free text');
        }

        foreach ($consultation->speechQueues as $queue) {
            if ($debate->motionId !== null && $queue->motionId === $debate->motionId) {
                return $queue;
            }
            if ($debate->amendmentId !== null && $queue->amendmentId === $debate->amendmentId) {
                return $queue;
            }
            if ($debate->agendaItemId !== null && $queue->agendaItemId === $debate->agendaItemId) {
                return $queue;
            }
            if ($isFreeText && $queue->motionId === null && $queue->amendmentId === null && $queue->agendaItemId === null) {
                return $queue;
            }
        }

        $queue = SpeechQueue::createWithSubqueues($consultation, false);
        $queue->motionId = $debate->motionId;
        $queue->amendmentId = $debate->amendmentId;
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
     * Gets (or creates) the speaking list for the debated item and marks it active, enabling the speech
     * feature for the consultation and deactivating other non-agenda lists (mirrors the speech admin's
     * activation behaviour, where only one non-agenda list is active at a time).
     */
    public static function activateSpeechQueue(DebateItem $debate): SpeechQueue
    {
        $queue = self::getOrCreateSpeechQueue($debate);
        $consultation = $debate->getMyConsultation();

        $queue->isActive = 1;
        $queue->save();

        $settings = $consultation->getSettings();
        if (!$settings->hasSpeechLists) {
            $settings->hasSpeechLists = true;
            $consultation->setSettings($settings);
            $consultation->save();
        }

        if ($queue->agendaItemId === null) {
            foreach ($consultation->speechQueues as $otherQueue) {
                if ($otherQueue->id !== $queue->id && $otherQueue->agendaItemId === null && $otherQueue->isActive) {
                    $otherQueue->isActive = 0;
                    $otherQueue->save();
                }
            }
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
     * motion/amendment the item itself becomes the single voting item; for an agenda item or free-text debate,
     * the given question is used as the voting title and question. The admin opens it afterwards through the
     * regular voting admin.
     */
    public static function createVotingForDebate(DebateItem $debate, ?string $question): VotingBlock
    {
        $consultation = $debate->getMyConsultation();
        $target = $debate->getDebateTarget();
        if ($target === null && $debate->freeText === null) {
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

    /**
     * All data the user-facing "Currently debated" widget (CurrentDebateWidget.vue) needs to bootstrap.
     * Shared by the inline homepage widget (_index_debate.php) and the fullscreen projector
     * (_fullscreen_toggle.php), so both stay in sync.
     *
     * The debate state itself is only included when $includeState is true. The fullscreen projector
     * passes false: by the time it is opened the page-load snapshot would be out of date anyway, so the
     * widget loads the current state from the backend on open instead (see CurrentDebateWidget.vue).
     *
     * @return array<string, mixed>
     */
    public static function getUserWidgetInitData(Consultation $consultation, bool $includeState = true): array
    {
        $user = User::getCurrentUser();
        $cookieUser = ($user ? null : CookieUser::getFromCookieOrCache());

        // Voting: the embedded widget reuses the existing session-based /voting endpoints, which require a
        // logged-in user. Anonymous visitors therefore get empty URLs and no voting is embedded.
        if ($user) {
            $votingPollUrl   = UrlHelper::createUrl(['/voting/get-open-voting-blocks', 'assignedToMotionId' => '', 'showAllOpen' => 1]);
            $votingVoteUrl   = UrlHelper::createUrl(['/voting/post-vote', 'votingBlockId' => 'VOTINGBLOCKID', 'assignedToMotionId' => '', 'showAllOpen' => 1]);
            $votingAdminLink = $user->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null)
                ? UrlHelper::createUrl(['/consultation/admin-votings'])
                : '';
        } else {
            $votingPollUrl   = '';
            $votingVoteUrl   = '';
            $votingAdminLink = '';
        }

        return [
            'init_state'          => $includeState
                ? Tools::getSerializer()->serialize(DebateState::fromConsultation($consultation), 'json')
                : null,
            'poll_url'            => UrlHelper::createUrl(['/rest/debate/index']),
            'motion_types_url'    => UrlHelper::createUrl(['/rest/motion-type/index']),
            'create_motion_url'   => UrlHelper::createUrl(['/rest/motion/create']),
            'speech_poll_url'     => UrlHelper::createUrl(['/rest/speech/get-queue', 'queueIds' => 'QUEUEIDS']),
            'speech_register_url' => UrlHelper::createUrl(['/rest/speech/register', 'queueId' => 'QUEUEID']),
            'speech_unregister_url' => UrlHelper::createUrl(['/rest/speech/unregister', 'queueId' => 'QUEUEID']),
            'speech_user'         => new SpeechUser($user, $cookieUser),
            'voting_poll_url'     => $votingPollUrl,
            'voting_vote_url'     => $votingVoteUrl,
            'voting_admin_link'   => $votingAdminLink,
            'voting_constants'    => include(\Yii::getAlias('@app/views/voting/_constants.php')),
            'current_user'        => $user ? [
                'name'         => $user->name,
                'organization' => $user->organization,
                'email'        => $user->email,
            ] : null,
        ];
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
