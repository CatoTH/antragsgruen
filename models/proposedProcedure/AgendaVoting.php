<?php

namespace app\models\proposedProcedure;

use app\models\db\{Amendment, IMotion, Motion, User, Vote, VotingBlock};
use app\components\UrlHelper;
use app\models\exceptions\Access;

class AgendaVoting
{
    /** @var string */
    public $title;

    /** @var VotingBlock|null */
    public $voting;

    /** @var IMotion[] */
    public $items = [];

    public function __construct(string $title, ?VotingBlock $voting)
    {
        $this->title  = $title;
        $this->voting = $voting;
    }

    public function getId(): string
    {
        if ($this->voting) {
            return (string)$this->voting->id;
        } else {
            return 'new';
        }
    }

    public function getHandledMotionIds(): array
    {
        $ids = [];
        foreach ($this->items as $item) {
            if (is_a($item, Motion::class)) {
                $ids[] = $item->id;
            }
        }
        return $ids;
    }

    public function getHandledAmendmentIds(): array
    {
        $ids = [];
        foreach ($this->items as $item) {
            if (is_a($item, Amendment::class)) {
                $ids[] = $item->id;
            }
        }
        return $ids;
    }

    private function getApiObject(?string $title, ?User $user, bool $adminFields): array
    {
        $votingBlockJson = [
            'id' => ($this->getId() === 'new' ? null : $this->getId()),
            'title' => $title,
            'status' => ($this->voting ? $this->voting->votingStatus : null),
            'items' => [],
        ];
        if ($adminFields) {
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
                ];
                if ($user && $this->voting) {
                    $vote = $this->voting->getUserSingleItemVote($user, $item);
                    $data['voted'] = ($vote ? $vote->getVoteForApi() : null);
                    $data['can_vote'] = $this->voting->userIsAllowedToVoteFor($user, $item);
                }
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
                ];
                if ($user && $this->voting) {
                    $vote = $this->voting->getUserSingleItemVote($user, $item);
                    $data['voted'] = ($vote ? $vote->getVoteForApi() : null);
                    $data['can_vote'] = $this->voting->userIsAllowedToVoteFor($user, $item);
                }
            }

            if ($adminFields && $this->voting) {
                if (is_a($item, Amendment::class)) {
                    $votes = $this->voting->getVotesForAmendment($item);
                } else {
                    $votes = $this->voting->getVotesForMotion($item);
                }
                $data['votes'] = array_map(function (Vote $vote): array {
                    return [
                        'vote' => $vote->getVoteForApi(),
                        'user_id' => $vote->userId,
                        'user_name' => ($vote->user ? $vote->user->getAuthUsername() : null),
                        'user_organizations' => ($vote->user ? $vote->user->getMyOrganizationIds() : null),
                    ];
                }, $votes);
                $data['vote_results'] = Vote::calculateVoteResultsForApi($this->voting, $votes);
            }

            $votingBlockJson['items'][] = $data;
        }

        return $votingBlockJson;
    }

    public function getProposedProcedureApiObject(bool $hasMultipleVotingBlocks): array
    {
        $title = ($hasMultipleVotingBlocks || $this->voting ? $this->title : null);

        return $this->getApiObject($title, null, false);
    }

    public function getAdminApiObject(): array
    {
        if (!$this->voting->getMyConsultation()->havePrivilege(User::PRIVILEGE_VOTINGS)) {
            throw new Access('No voting admin permissions');
        }
        return $this->getApiObject($this->title, null, true);
    }

    public function getUserApiObject(?User $user): array
    {
        return $this->getApiObject($this->title, $user, true);
    }
}
