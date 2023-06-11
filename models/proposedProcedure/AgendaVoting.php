<?php

namespace app\models\proposedProcedure;

use app\components\MotionSorter;
use app\models\policies\UserGroups;
use app\models\quorumType\NoQuorum;
use app\models\settings\Privileges;
use app\models\db\{ConsultationUserGroup, IVotingItem, Motion, User, Vote, VotingBlock};
use app\models\exceptions\Access;
use app\models\IMotionList;
use app\models\policies\IPolicy;

class AgendaVoting
{
    public const API_CONTEXT_PROPOSED_PROCEDURE = 'pp';
    public const API_CONTEXT_VOTING = 'voting';
    public const API_CONTEXT_ADMIN = 'admin';
    public const API_CONTEXT_RESULT = 'result';

    public IMotionList $itemIds;

    /** @var IVotingItem[] */
    public array $items = [];

    public function __construct(
        public string $title,
        public ?VotingBlock $voting
    ) {
        $this->itemIds = new IMotionList();
    }

    public function addItemsFromBlock(bool $includeNotOnPublicProposalOnes): void
    {
        if (!$this->voting) {
            return;
        }
        foreach ($this->voting->questions as $question) {
            $this->items[]   = $question;
            $this->itemIds->addQuestion($question);
        }

        /** @var Motion[] $motions */
        $motions = MotionSorter::getSortedIMotionsFlat($this->voting->getMyConsultation(), $this->voting->motions);
        foreach ($motions as $motion) {
            if (!$motion->isVisibleForAdmins()) {
                continue;
            }
            if ($motion->isProposalPublic() || $includeNotOnPublicProposalOnes) {
                $this->items[]   = $motion;
                $this->itemIds->addMotion($motion);
            }
        }

        $amendments = MotionSorter::getSortedAmendments($this->voting->getMyConsultation(), $this->voting->amendments);
        foreach ($amendments as $vAmendment) {
            if (!$vAmendment->getMyMotion()) {
                continue;
            }
            if (!$vAmendment->isVisibleForAdmins()) {
                continue;
            }
            if ($vAmendment->isProposalPublic() || $includeNotOnPublicProposalOnes) {
                $this->items[]  = $vAmendment;
                $this->itemIds->addAmendment($vAmendment);
            }
        }
    }

    public static function getFromVotingBlock(VotingBlock $votingBlock): self
    {
        $voting = new AgendaVoting($votingBlock->title, $votingBlock);
        $voting->addItemsFromBlock(true);
        return $voting;
    }

    public function getId(): string
    {
        if ($this->voting) {
            return (string)$this->voting->id;
        } else {
            return 'new';
        }
    }

    private function getOverriddenUserGroupCounts(): array
    {
        if (!$this->voting->isClosed()) {
            return [];
        }
        if (count($this->items) === 0) {
            return [];
        }
        if (!$this->items[0]->getVotingData()->eligibilityList) {
            return [];
        }
        $counts = [];
        foreach ($this->items[0]->getVotingData()->eligibilityList as $eligiblity) {
            $counts[$eligiblity->groupId] = count($eligiblity->users);
        }
        return $counts;
    }

    private function getApiObject(?string $title, ?User $user, string $context): array
    {
        $answers = ($this->voting ? $this->voting->getAnswers() : null);
        $votingBlockJson = [
            'id' => ($this->getId() === 'new' ? null : $this->getId()),
            'title' => $title,
            'status' => ($this->voting ? $this->voting->votingStatus : null),
            'votes_public' => ($this->voting ? $this->voting->votesPublic : null),
            'results_public' => ($this->voting ? $this->voting->resultsPublic : null),
            'assigned_motion' => ($this->voting ? $this->voting->assignedToMotionId : null),
            'majority_type' => ($this->voting ? $this->voting->majorityType : null),
            'quorum_type' => ($this->voting ? $this->voting->quorumType : null),
            'user_groups' => [],
            'answers' => $answers,
            'answers_template' => ($this->voting ? $this->voting->getAnswerTemplate() : null),
            'items' => [],
        ];

        if ($this->voting) {
            User::preloadConsultationUserGroups($this->voting->getMyConsultation());

            $settings = $this->voting->getSettings();
            $policy = $this->voting->getVotingPolicy();
            $additionalIds = (is_a($policy, UserGroups::class) ? array_map(function (ConsultationUserGroup $group): int { return $group->id; }, $policy->getAllowedUserGroups()) : []);
            $userGroups = $this->voting->getMyConsultation()->getAllAvailableUserGroups($additionalIds, true);

            $userGroupOverrides = $this->getOverriddenUserGroupCounts();
            foreach ($userGroups as $userGroup) {
                $votingBlockJson['user_groups'][] = $userGroup->getVotingApiObject($userGroupOverrides[$userGroup->id] ?? null);
            }

            $votingBlockJson['current_time'] = (int)round(microtime(true) * 1000); // needs to include milliseconds for accuracy
            $votingBlockJson['voting_time'] = $settings->votingTime;
            $votingBlockJson['opened_ts'] = ($this->voting->votingStatus === VotingBlock::STATUS_OPEN ? $settings->openedTs * 1000 : null);
        }

        if ($context === static::API_CONTEXT_ADMIN) {
            $votingBlockJson['log'] = ($this->voting ? $this->voting->getActivityLogForApi() : []);
            $votingBlockJson['max_votes_by_group'] = ($this->voting ? $this->voting->getSettings()->maxVotesByGroup : null);
        }
        if ($this->voting) {
            list($total, $users) = $this->voting->getVoteStatistics();
            $votingBlockJson['votes_total'] = $total;
            $votingBlockJson['votes_users'] = $users;
            $votingBlockJson['vote_policy'] = $this->voting->getVotingPolicy()->getApiObject();

            $quorumType = $this->voting->getQuorumType();
            if (!is_a($quorumType, NoQuorum::class)) {
                $votingBlockJson['quorum'] = $quorumType->getQuorum($this->voting);
                $votingBlockJson['quorum_custom_target'] = $quorumType->getCustomQuorumTarget($this->voting);
                $votingBlockJson['quorum_eligible'] = $quorumType->getRelevantEligibleVotersCount($this->voting);
            }
        } else {
            $votingBlockJson['vote_policy'] = ['id' => IPolicy::POLICY_NOBODY];
        }

        if ($user && $this->voting && $context === static::API_CONTEXT_VOTING) {
            $votingBlockJson['votes_remaining'] = $this->voting->getUserRemainingVotes($user);
        }

        foreach ($this->items as $item) {
            $data = $item->getAgendaApiBaseObject();

            if ($user && $this->voting && $context === static::API_CONTEXT_VOTING) {
                $vote = $this->voting->getUserSingleItemVote($user, $item);
                $data['voted'] = ($vote ? $vote->getVoteForApi($answers) : null);
                $data['can_vote'] = $this->voting->userIsCurrentlyAllowedToVoteFor($user, $item, $vote);
            }

            if ($this->voting && $context === static::API_CONTEXT_ADMIN) {
                $this->setApiObjectResultData($data, $this->voting, $item, true);
            }
            if ($this->voting && $context === static::API_CONTEXT_RESULT) {
                $this->setApiObjectResultData($data, $this->voting, $item, false);
            }

            $votingBlockJson['items'][] = $data;
        }

        return $votingBlockJson;
    }

    private function setApiObjectResultData(array &$data, VotingBlock $voting, IVotingItem $item, bool $isAdmin): void
    {
        $quorumType = $voting->getQuorumType();
        if (!is_a($quorumType, NoQuorum::class)) {
            $data['quorum_votes'] = $quorumType->getRelevantVotedCount($voting, $item);
            $data['quorum_custom_current'] = $quorumType->getCustomQuorumCurrent($voting, $item);
        }

        if ($voting->resultsPublic === VotingBlock::RESULTS_PUBLIC_YES) {
            $canSeeResults = true;
        } else {
            $canSeeResults = $isAdmin;
        }

        if ($voting->votesPublic === VotingBlock::VOTES_PUBLIC_ALL) {
            $canSeeVotes = true;
        } elseif ($voting->votesPublic === VotingBlock::VOTES_PUBLIC_ADMIN) {
            $canSeeVotes = $isAdmin;
        } else {
            $canSeeVotes = false;
        }
        if (!$canSeeVotes && !$canSeeResults) {
            return;
        }

        $answers = $voting->getAnswers();
        $votes = $voting->getVotesForVotingItem($item);
        if ($canSeeResults) {
            if ($this->voting->isClosed()) {
                $data['vote_results'] = $item->getVotingData()->mapToApiResults($this->voting);
                $data['vote_eligibility'] = $item->getVotingData()->getEligibilityList();
            } else {
                $data['vote_results'] = Vote::calculateVoteResultsForApi($this->voting, $votes);
                $data['vote_eligibility'] = $voting->getVotingPolicy()->getEligibilityByGroup();
            }
        }
        if ($canSeeVotes) {
            // Extra safeguard to prevent accidental exposure of votes, even if this case should not be triggerable through the interface
            $singleVotes = array_filter($votes, function (Vote $vote) use ($isAdmin): bool {
                if ($vote->public === VotingBlock::VOTES_PUBLIC_ALL) {
                    return true;
                } elseif ($vote->public === VotingBlock::VOTES_PUBLIC_ADMIN) {
                    return $isAdmin;
                } else {
                    return false;
                }
            });
            // Filter out deleted users
            $singleVotes = array_filter($singleVotes, function (Vote $vote): bool {
                return !!$vote->getUser();
            });
            $singleVotes = array_values($singleVotes);
            $data['votes'] = array_map(function (Vote $vote) use ($answers, $voting): array {
                return [
                    'vote' => $vote->getVoteForApi($answers),
                    'user_id' => $vote->userId,
                    'user_name' => ($vote->getUser() ? $vote->getUser()->getAuthUsername() : null),
                    'user_groups' => ($vote->getUser() ? $vote->getUser()->getConsultationUserGroupIds($voting->getMyConsultation()) : null),
                ];
            }, $singleVotes);
        }
    }

    public function getProposedProcedureApiObject(bool $hasMultipleVotingBlocks): array
    {
        $title = ($hasMultipleVotingBlocks || $this->voting ? $this->title : null);

        return $this->getApiObject($title, null, AgendaVoting::API_CONTEXT_PROPOSED_PROCEDURE);
    }

    public function getAdminApiObject(): array
    {
        if (!$this->voting->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_VOTINGS, null)) {
            throw new Access('No voting admin permissions');
        }
        return $this->getApiObject($this->title, null, AgendaVoting::API_CONTEXT_ADMIN);
    }

    public function getUserVotingApiObject(?User $user): array
    {
        return $this->getApiObject($this->title, $user, AgendaVoting::API_CONTEXT_VOTING);
    }

    public function getUserResultsApiObject(?User $user): array
    {
        return $this->getApiObject($this->title, $user, AgendaVoting::API_CONTEXT_RESULT);
    }
}
