<?php

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
$agenda = $proposalFactory->create();
$votingBlockToRender = null;
foreach ($agenda as $agendaItem) {
    foreach ($agendaItem->votingBlocks as $votingBlock) {
        if ($votingBlock->voting && $votingBlockToRender === null) {
            $votingBlockToRender = $votingBlock;
        }
    }
}

?>
<h1>Voting administration</h1>
<div class="content">
    ...
</div>
<div class="manageVotings votingCommon">
    <section data-antragsgruen-widget="backend/VotingAdmin"
             data-voting="<?= Html::encode(json_encode($votingBlockToRender->getApiObject(true))) ?>">
        <div class="votingAdmin"></div>
    </section>
</div>
