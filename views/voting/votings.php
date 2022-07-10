<?php

use app\components\UrlHelper;
use app\models\db\User;
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
$layout->addBreadcrumb(Yii::t('voting', 'bc'));
$this->title = html_entity_decode(Yii::t('voting', 'results_title'), ENT_COMPAT, 'UTF-8');

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/_voting_common_mixins.vue.php');
$layout->addVueTemplate('@app/views/voting/_voting_vote_list.vue.php');
$layout->addVueTemplate('@app/views/voting/voting-block.vue.php');

$apiData = [];
foreach (Factory::getOpenVotingBlocks($consultation, null) as $votingBlockToRender) {
    $apiData[] = $votingBlockToRender->getUserResultsApiObject(User::getCurrentUser());
}

$pollUrl   = UrlHelper::createUrl(['/voting/get-open-voting-blocks', 'assignedToMotionId' => '']);
$voteUrl   = UrlHelper::createUrl(['/voting/post-vote', 'votingBlockId' => 'VOTINGBLOCKID', 'assignedToMotionId' => '']);

?>
<h1><?= Yii::t('voting', 'page_title') ?></h1>

<section data-url-poll="<?= Html::encode($pollUrl) ?>"
         data-url-vote="<?= Html::encode($voteUrl) ?>"
         data-antragsgruen-widget="frontend/VotingBlock" class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(json_encode($apiData)) ?>"
>
    <div class="currentVoting"></div>
</section>
