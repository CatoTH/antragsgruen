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
<section aria-labelledby="votingTitle"
         data-antragsgruen-widget="frontend/VotingBlock" class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(json_encode($votingBlockToRender->getApiObject(true))) ?>"
>
    <h2 class="green" id="votingTitle">Current Voting</h2>
    <div class="content">
        <?php
        $user = User::getCurrentUser();
        if ($user && $user->hasPrivilege($consultation, User::PRIVILEGE_VOTINGS)) {
            $url = UrlHelper::createUrl(['consultation/admin-votings']);
            echo '<a href="' . Html::encode($url) . '" class="votingsAdminLink">';
            echo '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
            echo 'Administrate votings';
            echo '</a>';
        }
        ?>
        <div class="currentVoting"></div>
    </div>
</section>

