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
$voteCreateUrl = UrlHelper::createUrl(['/voting/create-voting-block']);
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

<div class="manageVotings votingCommon"
     data-url-vote-settings="<?= Html::encode($voteSettingsUrl) ?>"
     data-vote-create="<?= Html::encode($voteCreateUrl) ?>"
     data-url-poll="<?= Html::encode($pollUrl) ?>"
     data-antragsgruen-widget="backend/VotingAdmin"
     data-addable-motions="<?= Html::encode(json_encode($addableMotionsData)) ?>"
     data-voting="<?= Html::encode(json_encode($apiData)) ?>">
    <div class="content">
        <button type="button" class="btn btn-default createVotingOpener">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
            <?= Yii::t('voting', 'settings_create') ?>
        </button>

        <?= Yii::t('voting', 'admin_intro') ?>
    </div>

    <section class="createVotingHolder hidden" aria-labelledby="createVotingTitle">
        <h2 class="green" id="createVotingTitle">
            <?= Yii::t('voting', 'settings_create') ?>
        </h2>
        <form method="POST" class="content creatingVoting votingSettings">
            <label class="titleSetting">
                <?= Yii::t('voting', 'settings_title') ?>:<br>
                <input type="text" class="form-control settingsTitle">
            </label>
            <label class="assignedMotion">
                <?= Yii::t('voting', 'settings_motionassign') ?>:
                <?= \app\components\HTMLTools::getTooltipIcon(Yii::t('voting', 'settings_motionassign_h')) ?>
                <br>
                <select class="stdDropdown settingsAssignedMotion">
                    <option value=""> - <?= Yii::t('voting', 'settings_motionassign_none') ?> -</option>
                    <?php
                    foreach ($addableMotionsData as $motion) {
                        if ($motion['type'] !== 'motion') {
                            continue;
                        }
                        echo '<option value="' . intval($motion['id']) . '">' . Html::encode($motion['title']) . '</option>';
                    }
                    ?>
                </select>
            </label>
            <button type="submit" class="btn btn-success">
                <?= Yii::t('voting', 'settings_save') ?>
            </button>
        </form>
    </section>

    <div class="votingAdmin"></div>
</div>
