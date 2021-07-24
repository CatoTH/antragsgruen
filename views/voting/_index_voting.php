<?php

/** @var \app\controllers\Base $controller */

use app\models\proposedProcedure\Factory;
use app\models\db\User;
use yii\helpers\Html;

$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/voting-block.vue.php');

$proposalFactory = new Factory($consultation, false);
$votingBlocksToRender = $proposalFactory->getOpenVotingBlocks();
$apiData = [];
foreach ($votingBlocksToRender as $votingBlockToRender) {
    $apiData[] = $votingBlockToRender->getUserApiObject(User::getCurrentUser());
}

?>
<section aria-labelledby="votingTitle"
         data-antragsgruen-widget="frontend/VotingBlock" class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(json_encode($apiData)) ?>"
>
    <div class="currentVoting"></div>
</section>

