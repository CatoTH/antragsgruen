<?php

namespace app\models\settings;

use app\models\policies\EligibilityByGroup;
use app\models\quorumType\NoQuorum;
use app\models\votings\Answer;
use app\models\votings\AnswerTemplates;
use app\models\db\{IVotingItem, Vote, VotingBlock};

class VotingData implements \JsonSerializable
{
    public const ORGANIZATION_DEFAULT = '0';

    use JsonConfigTrait;

    /** casting a "yes" for Item 1 implies a "yes" for Item 2 of the same item group */
    public ?string $itemGroupSameVote = null;
    public ?string $itemGroupName = null;
    public ?bool $quorumReached = null;

    // @TODO Migrate this to the more flexible answer system
    public ?int $votesYes = null;
    public ?int $votesNo = null;
    public ?int $votesAbstention = null;
    public ?int $votesInvalid = null;
    public ?int $votesPresent = null;

    /** @var EligibilityByGroup[]|null */
    public ?array $eligibilityList = null;

    public ?string $comment = null;

    public function __construct(?array $data)
    {
        $this->setPropertiesFromJSONOverride($data);
    }

    protected function setPropertiesFromJSONOverride(?array $data): void
    {
        $this->setPropertiesFromJSON($data);

        $this->eligibilityList = EligibilityByGroup::listFromJsonArray($this->eligibilityList);
    }

    public function hasAnyData(): bool
    {
        return $this->votesYes || $this->votesNo || $this->votesInvalid ||
               $this->votesAbstention || $this->votesPresent || $this->comment;
    }

    public function setFromPostData(array $votes): void
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

        $this->eligibilityList = $voting->getVotingPolicy()->getEligibilityByGroup();

        return $this;
    }

    public function renderDetailedResults(): ?string
    {
        return null;
    }

    public function getTotalVotesForAnswer(Answer $answer): ?int
    {
        return match ($answer->dbId) {
            AnswerTemplates::VOTE_YES => $this->votesYes,
            AnswerTemplates::VOTE_NO => $this->votesNo,
            AnswerTemplates::VOTE_ABSTENTION => $this->votesAbstention,
            AnswerTemplates::VOTE_PRESENT => $this->votesPresent,
            default => null,
        };
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

    /**
     * @return EligibilityByGroup[]|null
     */
    public function getEligibilityList(): ?array
    {
        return $this->eligibilityList;
    }
}
