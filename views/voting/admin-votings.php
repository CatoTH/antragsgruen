<?php

use app\components\UrlHelper;
use app\models\db\{Amendment, Motion};
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
$layout->addBreadcrumb(Yii::t('voting', 'admin_bc'));
$this->title = Yii::t('voting', 'admin_title');

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/admin-votings.vue.php');

$apiData = [];
foreach (Factory::getAllVotingBlocks($consultation) as $votingBlock) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $apiData[] = $votingBlock->getAdminApiObject();
}

$pollUrl = UrlHelper::createUrl(['/voting/get-admin-voting-blocks']);
$voteSettingsUrl = UrlHelper::createUrl(['/voting/post-vote-settings', 'votingBlockId' => 'VOTINGBLOCKID']);

$addableMotionsData = [];
foreach ($consultation->getVisibleIMotionsSorted(false) as $IMotion) {
    if (is_a($IMotion, Amendment::class)) {
        $addableMotionsData[] = [
            'type' => 'amendment',
            'id' => $IMotion->id,
            'title' => $IMotion->getTitleWithPrefix(),
        ];
    } else {
        /** @var Motion $IMotion */
        $amendments = [];
        foreach ($IMotion->getVisibleAmendmentsSorted(false, false) as $amendment) {
            $amendments[] = [
                'type' => 'amendment',
                'id' => $amendment->id,
                'title' => $amendment->titlePrefix,
            ];
        }
        $addableMotionsData[] = [
            'type' => 'motion',
            'id' => $IMotion->id,
            'title' => $IMotion->getTitleWithPrefix(),
            'amendments' => $amendments,
        ];
    }
}

?>
<h1><?= Yii::t('voting', 'admin_title') ?></h1>
<div class="content">
    ...
</div>
<div class="manageVotings votingCommon"
     data-url-vote-settings="<?= Html::encode($voteSettingsUrl) ?>"
     data-url-poll="<?= Html::encode($pollUrl) ?>"
     data-antragsgruen-widget="backend/VotingAdmin"
     data-addable-motions="<?= Html::encode(json_encode($addableMotionsData)) ?>"
     data-voting="<?= Html::encode(json_encode($apiData)) ?>">
    <div class="votingAdmin"></div>
</div>
<!--
<div class="content votingAdderForm">
    <button class="btn btn-link btnAddOpener" type="button">
        <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
        Create a new voting
    </button>
</div>
-->
