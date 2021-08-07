<?php

/** @var \app\controllers\Base $controller */

use app\components\UrlHelper;
use app\models\proposedProcedure\Factory;
use app\models\db\User;
use yii\helpers\Html;

$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/voting-block.vue.php');

$votingBlocksToRender = Factory::getOpenVotingBlocks($consultation);
$apiData = [];
foreach ($votingBlocksToRender as $votingBlockToRender) {
    $apiData[] = $votingBlockToRender->getUserApiObject(User::getCurrentUser());
}

$pollUrl   = UrlHelper::createUrl(['/voting/get-open-voting-blocks']);
$voteUrl   = UrlHelper::createUrl(['/voting/post-vote', 'votingBlockId' => 'VOTINGBLOCKID', 'itemType' => 'ITEMTYPE', 'itemId' => 'ITEMID']);
?>
<section aria-labelledby="votingTitle"
         data-url-poll="<?= Html::encode($pollUrl) ?>"
         data-url-vote="<?= Html::encode($voteUrl) ?>"
         data-antragsgruen-widget="frontend/VotingBlock" class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(json_encode($apiData)) ?>"
>
    <div class="currentVoting"></div>
</section>

