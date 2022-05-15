<?php

namespace app\plugins\european_youth_forum;

use app\models\db\IVotingItem;
use app\models\db\VotingBlock;
use app\models\quorumType\NoQuorum;

class VotingData extends \app\models\settings\VotingData {
    /** @var int|null */
    public $nycUsers;
    /** @var int|null */
    public $ingyoUsers;

    /** @var int|null */
    public $nycYes;
    /** @var int|null */
    public $nycYesMultiplied;
    /** @var int|null */
    public $nycNo;
    /** @var int|null */
    public $nycNoMultiplied;
    /** @var int|null */
    public $nycAbstention;
    /** @var int|null */
    public $nycTotal;
    /** @var int|null */
    public $nycTotalMultiplied;

    /** @var int|null */
    public $ingyoYes;
    /** @var int|null */
    public $ingyoYesMultiplied;
    /** @var int|null */
    public $ingyoNo;
    /** @var int|null */
    public $ingyoNoMultiplied;
    /** @var int|null */
    public $ingyoAbstention;
    /** @var int|null */
    public $ingyoTotal;
    /** @var int|null */
    public $ingyoTotalMultiplied;

    /** @var int|null */
    public $totalYes;
    /** @var int|null */
    public $totalYesMultiplied;
    /** @var int|null */
    public $totalNo;
    /** @var int|null */
    public $totalNoMultiplied;
    /** @var int|null */
    public $totalAbstention;
    /** @var int|null */
    public $totalAbstentionMultiplied;
    /** @var int|null */
    public $totalTotal;
    /** @var int|null */
    public $totalTotalMultiplied;

    public function augmentWithResults(VotingBlock $voting, IVotingItem $votingItem): \app\models\settings\VotingData
    {
        if (!VotingHelper::isSetUpAsYfjVoting($voting)) {
            return parent::augmentWithResults($voting, $votingItem);
        }
        $votes = $voting->getVotesForVotingItem($votingItem);
        $results = Module::calculateVoteResultsForApi($voting, $votes);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->nycUsers = VotingHelper::getEligibleUserCountByGroup($voting, VotingHelper::GROUP_NYC);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->ingyoUsers = VotingHelper::getEligibleUserCountByGroup($voting, VotingHelper::GROUP_INGYO);

        $this->nycYes = $results['nyc']['yes'];
        $this->nycYesMultiplied = $results['nyc']['yes_multiplied'];
        $this->nycNo = $results['nyc']['no'];
        $this->nycNoMultiplied = $results['nyc']['no_multiplied'];
        $this->nycAbstention = $results['nyc']['abstention'];
        $this->nycTotal = $results['nyc']['total'];
        $this->nycTotalMultiplied = $results['nyc']['total_multiplied'];

        $this->ingyoYes = $results['ingyo']['yes'];
        $this->ingyoYesMultiplied = $results['ingyo']['yes_multiplied'];
        $this->ingyoNo = $results['ingyo']['no'];
        $this->ingyoNoMultiplied = $results['ingyo']['no_multiplied'];
        $this->ingyoAbstention = $results['ingyo']['abstention'];
        $this->ingyoTotal = $results['ingyo']['total'];
        $this->ingyoTotalMultiplied = $results['ingyo']['total_multiplied'];

        $this->totalYes = $results['total']['yes'];
        $this->totalYesMultiplied = $results['total']['yes_multiplied'];
        $this->totalNo = $results['total']['no'];
        $this->totalNoMultiplied = $results['total']['no_multiplied'];
        $this->totalAbstention = $results['total']['abstention'];
        $this->totalAbstentionMultiplied = $results['total']['abstention_multiplied'];
        $this->totalTotal = $results['total']['total'];
        $this->totalTotalMultiplied = $results['total']['total_multiplied'];

        $this->votesYes = $this->totalYesMultiplied;
        $this->votesNo = $this->totalNoMultiplied;
        $this->votesAbstention = $this->totalAbstentionMultiplied;

        $quorum = $voting->getQuorumType();
        if (is_a($quorum, NoQuorum::class)) {
            $this->quorumReached = null;
        } else {
            $this->quorumReached = $quorum->hasReachedQuorum($voting, $votingItem);
        }

        return $this;
    }

    public function mapToApiResults(VotingBlock $voting): array
    {
        if (!VotingHelper::isSetUpAsYfjVoting($voting)) {
            return parent::mapToApiResults($voting);
        }

        return [
            'nyc' => [
                'yes' => $this->nycYes,
                'yes_multiplied' => $this->nycYesMultiplied,
                'no' => $this->nycNo,
                'no_multiplied' => $this->nycNoMultiplied,
                'abstention' => $this->nycAbstention,
                'abstention_multiplied' => $this->nycAbstention * $this->ingyoUsers,
                'total' => $this->nycTotal,
                'total_multiplied' => $this->nycTotalMultiplied,
            ],
            'ingyo' => [
                'yes' => $this->ingyoYes,
                'yes_multiplied' => $this->ingyoYesMultiplied,
                'no' => $this->ingyoNo,
                'no_multiplied' => $this->ingyoNoMultiplied,
                'abstention' => $this->ingyoAbstention,
                'abstention_multiplied' => $this->ingyoAbstention * $this->nycUsers,
                'total' => $this->ingyoTotal,
                'total_multiplied' => $this->ingyoTotalMultiplied,
            ],
            'total' => [
                'yes' => $this->totalYes,
                'yes_multiplied' => $this->totalYesMultiplied,
                'no' => $this->totalNo,
                'no_multiplied' => $this->totalNoMultiplied,
                'abstention' => $this->totalAbstention,
                'abstention_multiplied' => $this->totalAbstentionMultiplied,
                'total' => $this->totalTotal,
                'total_multiplied' => $this->totalTotalMultiplied,
            ],
        ];
    }

    public function renderDetailedResults(): ?string
    {
        $result = $this;
        ob_start();
        require(__DIR__ . '/views/voting-result-admin-backend.php');
        return ob_get_clean();
    }
}
