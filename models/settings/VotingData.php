<?php

namespace app\models\settings;

use app\models\db\{Vote, VotingBlock};

class VotingData implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var null|string - casting a "yes" for Item 1 implies a "yes" for Item 2 of the same item group */
    public $itemGroupSameVote = null;
    /** @var null|string */
    public $itemGroupName = null;

    /** @var null|int */
    public $votesYes = null;
    /** @var null|int */
    public $votesNo = null;
    /** @var null|int */
    public $votesAbstention = null;
    /** @var null|int */
    public $votesInvalid = null;

    /** @var null|string */
    public $comment = null;

    public function hasAnyData(): bool
    {
        return $this->votesYes || $this->votesNo || $this->votesInvalid || $this->votesAbstention || $this->comment;
    }

    /**
     * @param array $votes
     */
    public function setFromPostData($votes)
    {
        if (isset($votes['yes']) && is_numeric($votes['yes'])) {
            $this->votesYes = intval($votes['yes']);
        }
        if (isset($votes['no']) && is_numeric($votes['no'])) {
            $this->votesNo = intval($votes['no']);
        }
        if (isset($votes['abstention']) && is_numeric($votes['abstention'])) {
            $this->votesAbstention = intval($votes['abstention']);
        }
        if (isset($votes['invalid']) && is_numeric($votes['invalid'])) {
            $this->votesInvalid = intval($votes['invalid']);
        }
        if (isset($votes['comment'])) {
            $this->comment = $votes['comment'];
        }
    }

    public function augmentWithResults(VotingBlock $voting, array $votes): self
    {
        $results = Vote::calculateVoteResultsForApi($voting, $votes);
        $orga = \app\models\db\User::ORGANIZATION_DEFAULT;
        if (isset($results[$orga])) {
            $this->votesYes = $results[$orga][Vote::VOTE_API_YES];
            $this->votesNo = $results[$orga][Vote::VOTE_API_NO];
            $this->votesAbstention = $results[$orga][Vote::VOTE_API_ABSTENTION];
        }

        return $this;
    }

    public function renderDetailedResults(): ?string
    {
        return null;
    }
}
