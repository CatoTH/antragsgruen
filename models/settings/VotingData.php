<?php

namespace app\models\settings;

use app\models\votings\Answer;
use app\models\votings\AnswerTemplates;
use app\models\db\{Vote, VotingBlock};

class VotingData implements \JsonSerializable
{
    const ORGANIZATION_DEFAULT = '0';

    use JsonConfigTrait;

    /** @var null|string - casting a "yes" for Item 1 implies a "yes" for Item 2 of the same item group */
    public $itemGroupSameVote = null;
    /** @var null|string */
    public $itemGroupName = null;

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
        if (isset($votes['present']) && is_numeric($votes['present'])) {
            $this->votesPresent = intval($votes['present']);
        }
        if (isset($votes['comment'])) {
            $this->comment = $votes['comment'];
        }
    }

    public function augmentWithResults(VotingBlock $voting, array $votes): self
    {
        $results = Vote::calculateVoteResultsForApi($voting, $votes);
        $orga = self::ORGANIZATION_DEFAULT;
        if (isset($results[$orga])) {
            $this->votesYes = $results[$orga]['yes'] ?? null;
            $this->votesNo = $results[$orga]['no'] ?? null;
            $this->votesAbstention = $results[$orga]['abstention'] ?? null;
            $this->votesPresent = $results[$orga]['present'] ?? null;
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
}
