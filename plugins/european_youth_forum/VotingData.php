<?php

namespace app\plugins\european_youth_forum;

use app\models\db\VotingBlock;

class VotingData extends \app\models\settings\VotingData {
    public $nycUsers;
    public $ingyoUsers;

    public $nycYes;
    public $nycYesMultiplied;
    public $nycNo;
    public $nycNoMultiplied;
    public $nycAbstention;
    public $nycTotal;
    public $nycTotalMultiplied;

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
    public $totalAbstentionMultiplied;
    public $totalTotal;
    public $totalTotalMultiplied;

    public function augmentWithResults(VotingBlock $voting, array $votes): \app\models\settings\VotingData
    {
        $results = Module::calculateVoteResultsForApi($voting, $votes);

        $this->nycUsers = $voting->getUserPresentByOrganization('nyc');
        $this->ingyoUsers = $voting->getUserPresentByOrganization('ingyo');

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
