<?php

namespace app\models\proposedProcedure;

use app\models\db\{Amendment, IMotion, Motion, User, Vote, VotingBlock};
use app\components\UrlHelper;
use app\models\exceptions\Access;
use app\models\IMotionList;

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

    /** @var IMotion[] */
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
        $votingBlockJson = [
            'id' => ($this->getId() === 'new' ? null : $this->getId()),
            'title' => $title,
            'status' => ($this->voting ? $this->voting->votingStatus : null),
            'votes_public' => ($this->voting ? $this->voting->votesPublic : null),
            'results_public' => ($this->voting ? $this->voting->resultsPublic : null),
            'assigned_motion' => ($this->voting ? $this->voting->assignedToMotionId : null),
            'majority_type' => ($this->voting ? $this->voting->majorityType : null),
            'answers' => ($this->voting ? $this->voting->getAnswers() : null),
            'items' => [],
        ];
        if ($context === static::API_CONTEXT_ADMIN) {
            $votingBlockJson['user_organizations'] = [];
            foreach (User::getSelectableUserOrganizations(true) as $organization) {
                $votingBlockJson['user_organizations'][] = [
                    'id' => $organization->id,
                    'title' => $organization->title,
                    'members_present' => ($this->voting ? $this->voting->getUserPresentByOrganization($organization->id) : null),
                ];
            }
            $votingBlockJson['log'] = ($this->voting ? $this->voting->getActivityLogForApi() : []);
        }
        if ($this->voting) {
            list($total, $users) = $this->voting->getVoteStatistics();
            $votingBlockJson['votes_total'] = $total;
            $votingBlockJson['votes_users'] = $users;
        }

        foreach ($this->items as $item) {
            if ($item->isProposalPublic()) {
                $procedure = Agenda::formatProposedProcedure($item, Agenda::FORMAT_HTML);
            } elseif ($item->status === IMotion::STATUS_MOVED && is_a($item, Motion::class)) {
                /** @var Motion $item */
                $procedure = \app\views\consultation\LayoutHelper::getMotionMovedStatusHtml($item);
            } else {
                $procedure = null;
            }

            if (is_a($item, Amendment::class)) {
                /** @var Amendment $item */
                $data = [
                    'type' => 'amendment',
                    'id' => $item->id,
                    'prefix' => $item->titlePrefix,
                    'title_with_prefix' => $item->getTitleWithPrefix(),
                    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($item, 'rest')),
                    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($item)),
                    'initiators_html' => $item->getInitiatorsStr(),
                    'procedure' => $procedure,
                    'item_group_same_vote' => $item->getVotingData()->itemGroupSameVote,
                    'item_group_name' => $item->getVotingData()->itemGroupName,
                    'voting_status' => $item->votingStatus,
                ];
            } else {
                /** @var Motion $item */
                $data = [
                    'type' => 'motion',
                    'id' => $item->id,
                    'prefix' => $item->titlePrefix,
                    'title_with_prefix' => $item->getTitleWithPrefix(),
                    'url_json' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($item, 'rest')),
                    'url_html' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($item)),
                    'initiators_html' => $item->getInitiatorsStr(),
                    'procedure' => $procedure,
                    'item_group_same_vote' => $item->getVotingData()->itemGroupSameVote,
                    'item_group_name' => $item->getVotingData()->itemGroupName,
                    'voting_status' => $item->votingStatus,
                ];
            }

            if ($user && $this->voting && $context === static::API_CONTEXT_VOTING) {
                $vote = $this->voting->getUserSingleItemVote($user, $item);
                $data['voted'] = ($vote ? $vote->getVoteForApi() : null);
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

    private function setApiObjectResultData(array &$data, VotingBlock $voting, IMotion $item, bool $isAdmin): void
    {
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

        if (is_a($item, Amendment::class)) {
            $votes = $voting->getVotesForAmendment($item);
        } else {
            /** @var Motion $item */
            $votes = $voting->getVotesForMotion($item);
        }
        if ($canSeeResults) {
            $data['vote_results'] = Vote::calculateVoteResultsForApi($this->voting, $votes);
        }
        if ($canSeeVotes) {
            // Extra safeguard to prevent accidental exposure of votes, even if this case should not be triggerable through the interface
            $singleVotes = array_values(array_filter($votes, function (Vote $vote) use ($isAdmin) {
                if ($vote->public === VotingBlock::VOTES_PUBLIC_ALL) {
                    return true;
                } elseif ($vote->public === VotingBlock::VOTES_PUBLIC_ADMIN) {
                    return $isAdmin;
                } else {
                    return false;
                }
            }));
            $data['votes'] = array_map(function (Vote $vote): array {
                return [
                    'vote' => $vote->getVoteForApi(),
                    'user_id' => $vote->userId,
                    'user_name' => ($vote->user ? $vote->user->getAuthUsername() : null),
                    'user_organizations' => ($vote->user ? $vote->user->getMyOrganizationIds() : null),
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
        if (!$this->voting->getMyConsultation()->havePrivilege(User::PRIVILEGE_VOTINGS)) {
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
