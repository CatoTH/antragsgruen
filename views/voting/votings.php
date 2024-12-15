<?php

use app\components\UrlHelper;
use app\models\db\User;
use app\models\layoutHooks\Layout;
use app\models\proposedProcedure\Factory;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
$layout->addBreadcrumb(Yii::t('voting', 'votings_bc'));
$this->title = Yii::t('voting', 'page_title');

$sidebarMode = 'open';
include(__DIR__ . DIRECTORY_SEPARATOR . '_sidebar.php');

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/_voting_common_mixins.vue.php');
$layout->addVueTemplate('@app/views/voting/_voting_vote_list.vue.php');
$layout->addVueTemplate('@app/views/voting/voting-block.vue.php');
Layout::registerAdditionalVueVotingTemplates($consultation, $layout);

$apiData = [];
foreach (Factory::getOpenVotingBlocks($consultation, true, null) as $votingBlockToRender) {
    $apiData[] = $votingBlockToRender->getUserVotingApiObject(User::getCurrentUser());
}

$pollUrl   = UrlHelper::createUrl(['/voting/get-open-voting-blocks', 'assignedToMotionId' => '', 'showAllOpen' => 1]);
$voteUrl   = UrlHelper::createUrl(['/voting/post-vote', 'votingBlockId' => 'VOTINGBLOCKID', 'assignedToMotionId' => '']);

?>
<h1><?= Yii::t('voting', 'page_title') ?></h1>

<?= $layout->getMiniMenu('votingSidebarSmall') ?>

<div class="content votingsNoneIndicator<?= (count($apiData) > 0 ? ' hidden' : '') ?>">
    <div class="alert alert-info">
        <?= Yii::t('voting', 'votings_none') ?>
    </div>
</div>

<section data-url-poll="<?= Html::encode($pollUrl) ?>"
         data-url-vote="<?= Html::encode($voteUrl) ?>"
         data-antragsgruen-widget="frontend/VotingBlock" class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(json_encode($apiData)) ?>"
>
    <div class="currentVoting"></div>
</section>
