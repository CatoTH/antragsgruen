<?php

namespace app\models\proposedProcedure;

use app\models\policies\UserGroups;
use app\models\quorumType\NoQuorum;
use app\models\db\{ConsultationUserGroup, IVotingItem, User, Vote, VotingBlock};
use app\models\exceptions\Access;
use app\models\IMotionList;
use app\models\policies\IPolicy;

class AgendaVoting
{
    const API_CONTEXT_PROPOSED_PROCEDURE = 'pp';
    const API_CONTEXT_VOTING = 'voting';
    const API_CONTEXT_ADMIN = 'admin';
    const API_CONTEXT_RESULT = 'result';

    /** @var string */
    public $title;

    /** @var VotingBlock|null */
    public $voting;

    /** @var IVotingItem[] */
    public $items = [];

    /** @var IMotionList */
    public $itemIds;

    public function __construct(string $title, ?VotingBlock $voting)
    {
        $this->title  = $title;
        $this->voting = $voting;
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
        foreach ($this->voting->motions as $motion) {
            if (!$motion->isVisibleForAdmins()) {
                continue;
            }
            if ($motion->isProposalPublic() || $includeNotOnPublicProposalOnes) {
                $this->items[]   = $motion;
                $this->itemIds->addMotion($motion);
            }
        }
        foreach ($this->voting->amendments as $vAmendment) {
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
            $policy = $this->voting->getVotingPolicy();
            $additionalIds = (is_a($policy, UserGroups::class) ? $policy->getAllowedUserGroups() : []);
            foreach (ConsultationUserGroup::findByConsultation($this->voting->getMyConsultation(), $additionalIds) as $userGroup) {
                $votingBlockJson['user_groups'][] = $userGroup->getVotingApiObject();
            }
        }

        if ($context === static::API_CONTEXT_ADMIN) {
            $votingBlockJson['log'] = ($this->voting ? $this->voting->getActivityLogForApi() : []);
            $votingBlockJson['admin_setup_hint_html'] = ($this->voting ? $this->voting->getAdminSetupHintHtml() : null);
        }
        if ($this->voting) {
            list($total, $users) = $this->voting->getVoteStatistics();
            $votingBlockJson['votes_total'] = $total;
            $votingBlockJson['votes_users'] = $users;
            $votingBlockJson['vote_policy'] = $this->voting->getVotingPolicy()->getApiObject();

            $quorumType = $this->voting->getQuorumType();
            if (!is_a($quorumType, NoQuorum::class)) {
                $votingBlockJson['quorum'] = $quorumType->getQuorum($this->voting);
                $votingBlockJson['quorum_eligible'] = $quorumType->getRelevantEligibleVotersCount($this->voting);
            }
        } else {
            $votingBlockJson['vote_policy'] = ['id' => IPolicy::POLICY_NOBODY];
        }

        foreach ($this->items as $item) {
            $data = $item->getAgendaApiBaseObject();

            if ($user && $this->voting && $context === static::API_CONTEXT_VOTING) {
                $vote = $this->voting->getUserSingleItemVote($user, $item);
                $data['voted'] = ($vote ? $vote->getVoteForApi($answers) : null);
                $data['can_vote'] = $this->voting->userIsCurrentlyAllowedToVoteFor($user, $item);
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
            $quorumVotes = $quorumType->getRelevantVotedCount($voting, $item);
            $data['quorum_votes'] = $quorumVotes;
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
            if ($this->voting->votingStatus === VotingBlock::STATUS_CLOSED) {
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
        if (!$this->voting->getMyConsultation()->havePrivilege(ConsultationUserGroup::PRIVILEGE_VOTINGS)) {
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
