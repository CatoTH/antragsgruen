<?php

namespace app\models\settings;

use app\models\quorumType\NoQuorum;
use app\models\votings\Answer;
use app\models\votings\AnswerTemplates;
use app\models\db\{IVotingItem, Vote, VotingBlock};

class VotingData implements \JsonSerializable
{
    const ORGANIZATION_DEFAULT = '0';

    use JsonConfigTrait;

    /** @var null|string - casting a "yes" for Item 1 implies a "yes" for Item 2 of the same item group */
    public $itemGroupSameVote = null;
    /** @var null|string */
    public $itemGroupName = null;

    /** @var null|bool */
    public $quorumReached = null;

    // @TODO Migrate this to the more flexible answer system
    /** @var null|int */
    public $votesYes = null;
    /** @var null|int */
    public $votesNo = null;
    /** @var null|int */
    public $votesAbstention = null;
    /** @var null|int */
    public $votesInvalid = null;
    /** @var null|int */
    public $votesPresent = null;

    /** @var null|string */
    public $comment = null;

    public function hasAnyData(): bool
    {
        return $this->votesYes || $this->votesNo || $this->votesInvalid ||
               $this->votesAbstention || $this->votesPresent || $this->comment;
    }

    /**
     * @param array $votes
     */
    public function setFromPostData($votes): void
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
        if (isset($votes['present']) && is_numeric($votes['present'])) {
            $this->votesPresent = intval($votes['present']);
        }
        if (isset($votes['comment'])) {
            $this->comment = $votes['comment'];
        }
    }

    public function augmentWithResults(VotingBlock $voting, IVotingItem $votingItem): self
    {
        $votes = $voting->getVotesForVotingItem($votingItem);
        $results = Vote::calculateVoteResultsForApi($voting, $votes);
        $orga = self::ORGANIZATION_DEFAULT;
        if (isset($results[$orga])) {
            $this->votesYes = $results[$orga]['yes'] ?? null;
            $this->votesNo = $results[$orga]['no'] ?? null;
            $this->votesAbstention = $results[$orga]['abstention'] ?? null;
            $this->votesPresent = $results[$orga]['present'] ?? null;
        }

        $quorum = $voting->getQuorumType();
        if (is_a($quorum, NoQuorum::class)) {
            $this->quorumReached = null;
        } else {
            $this->quorumReached = $quorum->hasReachedQuorum($voting, $votingItem);
        }

        return $this;
    }

    public function renderDetailedResults(): ?string
    {
        return null;
    }

    public function getTotalVotesForAnswer(Answer $answer): ?int
    {
        switch ($answer->dbId) {
            case AnswerTemplates::VOTE_YES:
                return $this->votesYes;
            case AnswerTemplates::VOTE_NO:
                return $this->votesNo;
            case AnswerTemplates::VOTE_ABSTENTION:
                return $this->votesAbstention;
            case AnswerTemplates::VOTE_PRESENT:
                return $this->votesPresent;
        }
        return null;
    }

    public function mapToApiResults(VotingBlock $voting): array
    {
        $results = [
            VotingData::ORGANIZATION_DEFAULT => [],
        ];
        if ($this->votesYes !== null) {
            $results[VotingData::ORGANIZATION_DEFAULT]['yes'] = $this->votesYes;
        }
        if ($this->votesNo !== null) {
            $results[VotingData::ORGANIZATION_DEFAULT]['no'] = $this->votesNo;
        }
        if ($this->votesAbstention !== null) {
            $results[VotingData::ORGANIZATION_DEFAULT]['abstention'] = $this->votesAbstention;
        }
        if ($this->votesPresent !== null) {
            $results[VotingData::ORGANIZATION_DEFAULT]['present'] = $this->votesPresent;
        }

        return $results;
    }
}
