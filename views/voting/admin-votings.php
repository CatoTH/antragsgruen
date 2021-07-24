<?php

use app\models\db\VotingBlock;
use app\models\proposedProcedure\Factory;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
/** @var \app\models\db\Consultation */
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
$layout->addBreadcrumb('Votings');

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/admin-votings.vue.php');

$proposalFactory = new Factory($consultation, false);
$apiData = [];
foreach ($proposalFactory->getAllVotingBlocks() as $votingBlock) {
    $apiData[] = $votingBlock->getAdminApiObject();
}

?>
<h1>Voting administration</h1>
<div class="content">
    ...
</div>
<section >

                </section>
<div class="manageVotings votingCommon"
     data-antragsgruen-widget="backend/VotingAdmin"
     data-voting="<?= Html::encode(json_encode($apiData)) ?>">
    <div class="votingAdmin"></div>
</div>
<div class="content votingAdderForm">
    <button class="btn btn-link btnAddOpener" type="button">
        <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
        Create a new voting
    </button>
</div>
