<?php

namespace app\plugins\european_youth_forum;

use app\models\db\Vote;
use app\models\db\VotingBlock;

class VotingData extends \app\models\settings\VotingData {
    public $nyoUsers;
    public $ingyoUsers;

    public $nyoYes;
    public $nyoYesMultiplied;
    public $nyoNo;
    public $nyoNoMultiplied;
    public $nyoAbstention;
    public $nyoTotal;
    public $nyoTotalMultiplied;

    public $ingyoYes;
    public $ingyoYesMultiplied;
    public $ingyoNo;
    public $ingyoNoMultiplied;
    public $ingyoAbstention;
    public $ingyoTotal;
    public $ingyoTotalMultiplied;

    public $totalYes;
    public $totalYesMultiplied;
    public $totalNo;
    public $totalNoMultiplied;
    public $totalAbstention;
    public $totalTotal;
    public $totalTotalMultiplied;

    public function augmentWithResults(VotingBlock $voting, array $votes): \app\models\settings\VotingData
    {
        $results = Module::calculateVoteResultsForApi($voting, $votes);

        $this->nyoUsers = $voting->getUserPresentByOrganization('nyo');
        $this->ingyoUsers = $voting->getUserPresentByOrganization('ingyo');

        $this->nyoYes = $results['nyo'][Vote::VOTE_API_YES];
        $this->nyoYesMultiplied = $results['nyo']['yes_multiplied'];
        $this->nyoNo = $results['nyo'][Vote::VOTE_API_NO];
        $this->nyoNoMultiplied = $results['nyo']['no_multiplied'];
        $this->nyoAbstention = $results['nyo'][Vote::VOTE_API_ABSTENTION];
        $this->nyoTotal = $results['nyo']['total'];
        $this->nyoTotalMultiplied = $results['nyo']['total_multiplied'];

        $this->ingyoYes = $results['ingyo'][Vote::VOTE_API_YES];
        $this->ingyoYesMultiplied = $results['ingyo']['yes_multiplied'];
        $this->ingyoNo = $results['ingyo'][Vote::VOTE_API_NO];
        $this->ingyoNoMultiplied = $results['ingyo']['no_multiplied'];
        $this->ingyoAbstention = $results['ingyo'][Vote::VOTE_API_ABSTENTION];
        $this->ingyoTotal = $results['ingyo']['total'];
        $this->ingyoTotalMultiplied = $results['ingyo']['total_multiplied'];

        $this->totalYes = $results['total'][Vote::VOTE_API_YES];
        $this->totalYesMultiplied = $results['total']['yes_multiplied'];
        $this->totalNo = $results['total'][Vote::VOTE_API_NO];
        $this->totalNoMultiplied = $results['total']['no_multiplied'];
        $this->totalAbstention = $results['total'][Vote::VOTE_API_ABSTENTION];
        $this->totalTotal = $results['total']['total'];
        $this->totalTotalMultiplied = $results['total']['total_multiplied'];

        $this->votesYes = $this->totalYes;
        $this->votesNo = $this->totalNo;
        $this->votesAbstention = $this->totalAbstention;

        return $this;
    }

    public function renderDetailedResults(): ?string
    {
        $result = $this;
        ob_start();
        require(__DIR__ . '/views/voting-result-admin-backend.php');
        return ob_get_clean();
    }
}
