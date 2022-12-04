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
$layout->addBreadcrumb(Yii::t('voting', 'votings_bc'), UrlHelper::createUrl('/consultation/votings'));
$layout->addBreadcrumb(Yii::t('voting', 'results_bc'));
$this->title = html_entity_decode(Yii::t('voting', 'results_title'), ENT_COMPAT, 'UTF-8');

$sidebarMode = 'results';
include(__DIR__ . DIRECTORY_SEPARATOR . '_sidebar.php');

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/_voting_common_mixins.vue.php');
$layout->addVueTemplate('@app/views/voting/_voting_vote_list.vue.php');
$layout->addVueTemplate('@app/views/voting/voting-block.vue.php');
Layout::registerAdditionalVueVotingTemplates($consultation, $layout);

$apiData = [];
foreach (Factory::getPublishedClosedVotingBlocks($consultation) as $votingBlockToRender) {
    $apiData[] = $votingBlockToRender->getUserResultsApiObject(User::getCurrentUser());
}

$pollUrl   = UrlHelper::createUrl(['/voting/get-closed-voting-blocks']);

$fullscreenButton = '<button type="button" title="' . Yii::t('motion', 'fullscreen') . '" class="btn btn-link btnFullscreen"
        data-antragsgruen-widget="frontend/FullscreenToggle">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
        <span class="sr-only">' . Yii::t('motion', 'fullscreen') . '</span>
    </button>';

?>
<div class="primaryHeader">
    <h1><?= Yii::t('voting', 'results_title') ?></h1>
    <?= $fullscreenButton ?>
</div>

<?= $layout->getMiniMenu('votingSidebarSmall') ?>

<div class="content votingsNoneIndicator<?= (count($apiData) > 0 ? ' hidden' : '') ?>">
    <div class="alert alert-info">
        <?= Yii::t('voting', 'results_none') ?>
    </div>
</div>

<section data-antragsgruen-widget="frontend/VotingBlock" class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(json_encode($apiData)) ?>"
>
    <div class="currentVoting"></div>
</section>
